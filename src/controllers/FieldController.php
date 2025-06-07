<?php

namespace craftfm\iconify\controllers;

use Craft;
use craft\helpers\Html;
use craft\helpers\Search;
use craft\web\Controller;
use craftfm\iconify\Plugin;
use yii\caching\FileDependency;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FieldController extends Controller
{
    /**
     * @throws BadRequestHttpException
     */
    public function actionPicker(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $perPage = 500;

        $search = $this->request->getRequiredBodyParam('search');
        $set  = $this->request->getRequiredBodyParam('set');
        $page = (int)$this->request->getBodyParam('page') ? (int) $this->request->getBodyParam('page') : 1;
        $noSearch = $search === '';

        if ($noSearch) {
            $cache = Craft::$app->getCache();
            $cacheKey = sprintf('iconify-picker-options-list-html-%s-%s', $set, $page);
            $listHtml = $cache->get($cacheKey);
            if ($listHtml !== false) {
                return $this->asJson([
                    'listHtml' => $listHtml,
                ]);
            }
            $searchTerms = null;
        } else {
            $searchTerms = explode(' ', Search::normalizeKeywords($search));
        }

        $icons = Plugin::getInstance()->icons->getIconsModel([
           'set' => $set
        ], $perPage, $page);

        $output = [];
        $scores = [];
        foreach ($icons as $icon) {
            if ($searchTerms) {
                $score = $this->matchTerms($searchTerms, $icon->name) * 5;
                if ($score === 0) {
                    continue;
                }
                $scores[] = $score;
            }

            $svg = Plugin::getInstance()->icons->getIconSvg($icon->name, $icon->set);
            $output[] = Html::beginTag('li') .
                Html::button($svg, [
                    'class' => 'icon-picker--icon',
                    'title' => $icon->name,
                    'data-handle' => "$set:$icon->name",
                    'aria' => [
                        'label' => $icon->name,
                    ],
                ]) .
                Html::endTag('li');
        }

        if ($searchTerms) {
            array_multisort($scores, SORT_DESC, $output);
        }

        $listHtml = implode('', $output);

        if ($noSearch) {
            /** @phpstan-ignore-next-line */
            $cache->set($cacheKey, $listHtml);
        }

        return $this->asJson([
            'listHtml' => $listHtml,
        ]);
    }

    private function matchTerms(array $searchTerms, string $indexTerms): int
    {
        $score = 0;

        foreach ($searchTerms as $searchTerm) {
            // extra points for whole word matches
            if (str_contains($indexTerms, "$searchTerm")) {
                $score += 10;
            } elseif (str_contains($indexTerms, "$searchTerm")) {
                $score += 1;
            } else {
                return 0;
            }

        }

        return $score;
    }
}