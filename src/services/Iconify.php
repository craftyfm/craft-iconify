<?php

namespace craftfm\iconify\services;

use Craft;
use craft\base\Component;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Iconify extends Component
{
    /**
     * @throws Exception
     */
    public function loadCollections()
    {
        $path = Craft::getAlias('@craftyfm/iconify/resources/icons/collections.json');
        if (!file_exists($path)) {
            throw new Exception("collections not found");
        }
        $json = file_get_contents($path);
        return json_decode($json, true);
    }

    /**
     * @throws Exception
     */
    public function getIconSets(array $setKeys = null): array
    {
        $collections = $this->loadCollections();
        $sets = [];
        foreach ($collections as $key => $data) {
            if ($setKeys === null || (in_array($key, $setKeys))) {
                $sets[$key] = $data;
            }
        }
        return $sets;
    }
    /**
     * @throws Exception
     */
    public function getCategories(): array
    {

        $data = $this->loadCollections();
        $categories = [];

        foreach ($data as $key => $set) {
            $category = $set['category'] ?? 'Other';
            $categories[$category][$key] = $set;
        }
        return ($categories);
    }

    public function getIconList(string $setKey): array
    {
        $ret = [
            'prefixes' => [],
            'suffixes' => [],
            'icons' => []
        ];

        try {
            $data = $this->_requestApiIconList($setKey);

            if (isset($data['prefixes'])) {
                $ret['prefixes'] = $data['prefixes'];
            }
            if (isset($data['suffixes'])) {
                $ret['suffixes'] = $data['suffixes'];
            }

            $icons = [];

            if (isset($data['uncategorized'])) {
                $icons = $data['uncategorized'];
            }

            if (isset($data['categories'])) {
                foreach ($data['categories'] as $catIcons) {
                    $icons = array_merge($icons, $catIcons);
                }
            }

            // Normalize and deduplicate icons
            $finalIcons = [];

            foreach ($icons as $icon) {
                $variants = [$icon]; // original name

                // Add all variants to the final list
                foreach ($variants as $v) {
                    $finalIcons[$v] = true; // use associative array to avoid duplicates
                }
            }

            // Convert keys back to list
            $ret['icons'] = array_keys($finalIcons);

        } catch (Exception|GuzzleException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return $ret;
        }

        return $ret;
    }

    public function batchIconSet(string $setKey, array $iconKeys): array
    {
        $baseUrl = $this->getIconsApiUrl($setKey);
        $maxLength = 500 - strlen($baseUrl);
        $batch = [];
        $currentBatch = [];
        $currentLength = 0;

        foreach ($iconKeys as $key) {
            $iconLength = strlen($key) + (!empty($currentBatch) ? 1 : 0);
            if ($currentLength + $iconLength > $maxLength) {
                $batch[] = $currentBatch;

                $currentBatch = [$key];
                $currentLength = strlen($key);
            } else {
                // Add icon to current batch and update length
                $currentBatch[] = $key;
                $currentLength += $iconLength;
            }
        }

        if (!empty($currentBatch)) {
            $batch[] = $currentBatch;
        }

        return $batch;
    }

    public function downloadIconSetJson(string $setKey, string $iconDirectory): void
    {
        $baseUrl = $this->getIconsApiUrl($setKey);
        $maxLength = 500 - strlen($baseUrl);
        $currentBatch = [];
        $currentLength = 0;
        $icons = $this->getIconList($setKey);
        $iconData = [];
        try {
            foreach ($icons as $icon) {
                $iconLength = strlen($icon) + (!empty($currentBatch) ? 1 : 0);
                if ($currentLength + $iconLength > $maxLength) {
                    $iconBody = $this->getIconsData($setKey, $currentBatch);
                    $iconData = array_merge($iconData, $iconBody);
                    $currentBatch = [$icon];
                    $currentLength = strlen($icon);
                } else {
                    // add icon to batch and update length
                    $currentBatch[] = $icon;
                    $currentLength += $iconLength;
                }
            }
        } catch (Exception|GuzzleException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        $this->saveJsonIconSet($iconData, $setKey, $iconDirectory);
    }

    public function saveJsonIconSet(array $icons, string $setKey, string $iconDirectory): void
    {
        $wrappedData = [
            'icons' => $icons
        ];
        $json = json_encode($wrappedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filePath = $iconDirectory . DIRECTORY_SEPARATOR . $setKey. '.json';
        file_put_contents($filePath, $json);
    }

    /**
     * @throws GuzzleException
     */
    public function getIconsData(string $iconSet, array $iconList): array
    {
        $icons = implode(',', $iconList);
        try {
            $data = $this->_requestApiIcons($iconSet, $icons);
            return $data['icons'];
        } catch (Exception|GuzzleException $e) {
            Craft::error("Guzzle request getIconsData error: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    public function getIconsApiUrl($setKey): string
    {
        return "https://api.iconify.design/{$setKey}.json?icons=";
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function _requestApiIcons(string $iconSet, string $icons)
    {
        $baseUrl = $this->getIconsApiUrl($iconSet);;
        $url = $baseUrl . $icons;
        $client = new Client();
        $response = $client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to request icons");
        }
        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    /**
     * @throws GuzzleException
     */
    private function _requestApiIconList($setKey)
    {
        $url = "https://api.iconify.design/collection?prefix={$setKey}";
        $client = new Client();
        $response = $client->request('GET', $url);
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to request data");
        }

        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }
}