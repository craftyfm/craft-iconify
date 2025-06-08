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
    protected array|bool|int $allowAnonymous = true;
    /**
     * @throws BadRequestHttpException
     */
    public function actionPicker(): Response
    {
//        $this->requireCpRequest();
//        $this->requireAcceptsJson();

        $perPage = 1000;

        $search = $this->request->getRequiredBodyParam('search');
        $set  = $this->request->getRequiredBodyParam('set');
        $page = (int)$this->request->getBodyParam('page') ? (int) $this->request->getBodyParam('page') : 1;
        $noSearch = $search === '';
        $affixId = $this->request->getBodyParam('affix');
        $affixes = Plugin::getInstance()->icons->getIconSetAffixes($set);

        $affixOptions = [
            '' => 'All'
        ];

        foreach ($affixes as $affix) {
            $affixOptions[$affix['id']] = $affix['name'];
        }

        $cache = Craft::$app->getCache();
        $cacheKey = sprintf('iconify-picker-options-list-html-%s-%s', $set, $page);
        if ($noSearch && ($affixId === null || $affixId === '')) {
            $listHtml = $cache->get($cacheKey);
            if ($listHtml !== false) {
                return $this->asJson([
                    'listHtml' => $listHtml,
                    'affixOptions' => $affixOptions,
                    'selectedAffix' => $affixId,
                ]);
            }
            $searchTerms = null;
        } else {
            $searchTerms = explode(' ', Search::normalizeKeywords($search));
        }
        $params = ['set' => $set];
        if ($affixId && $affixId !== '') {
            $params['affixId'] = $affixId;
        }
        $icons = Plugin::getInstance()->icons->getIconsModel($params, $perPage, ($page-1)*$perPage);

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
                    'data-iconName' => "$icon->name",
                    'data-iconSet' => "$icon->set",
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

        if ($noSearch && ($affixId === null || $affixId === '')) {
            /** @phpstan-ignore-next-line */
            $cache->set($cacheKey, $listHtml);
        }

        return $this->asJson([
            'listHtml' => $listHtml,
            'affixOptions' => $affixOptions,
            'selectedAffix' => $affixId,
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