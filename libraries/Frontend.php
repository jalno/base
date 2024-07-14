<?php

namespace packages\base;

use packages\base\IO\Directory;
use packages\base\IO\File;

class Frontend
{
    private static function getWebpackResult(): array
    {
        $base = Packages::package('base');
        $privateRepo = new Directory\Local($base->getFilePath('storage/private/frontend'));
        $result = [];
        $resultFile = $privateRepo->file('result.json');
        if ($resultFile->exists()) {
            $result = json\decode($resultFile->read());
        }
        if (!is_array($result)) {
            $result = [];
        }
        if (!isset($result['handledFiles'])) {
            $result['handledFiles'] = [];
        }
        if (!isset($result['outputedFiles'])) {
            $result['outputedFiles'] = [];
        }

        return $result;
    }

    public static function checkAssetsForWebpack(array $sources): array
    {
        $result = self::getWebpackResult();
        $filteredAssets = [];
        $filteredFiles = [];
        $commonAssets = [];
        if (isset($result['outputedFiles']['common'])) {
            foreach ($result['outputedFiles']['common'] as $file) {
                $file = new File\Local($file);
                $commonAssets[] = [
                    'type' => $file->getExtension(),
                    'file' => '/'.$file->getPath(),
                ];
                $filteredFiles[] = $file->getPath();
            }
        }
        foreach ($sources as $source) {
            $handledFiles = [];
            $name = $source->getName();
            $assets = $source->getAssets();
            if (isset($result['handledFiles'][$name])) {
                $handledFiles = $result['handledFiles'][$name];
                if ($commonAssets) {
                    $filteredAssets = array_merge($filteredAssets, $commonAssets);
                    $commonAssets = [];
                }
            }

            if (isset($result['outputedFiles'][$name])) {
                foreach ($result['outputedFiles'][$name] as $file) {
                    $file = new File\Local($file);
                    if (!in_array($file->getPath(), $filteredFiles)) {
                        $filteredAssets[] = [
                            'type' => $file->getExtension(),
                            'file' => '/'.$file->getPath(),
                        ];
                        $filteredFiles[] = $file->getPath();
                    }
                }
            }
            foreach ($assets as $asset) {
                if (in_array($asset['type'], ['js', 'css', 'less', 'ts'])) {
                    if (isset($asset['file'])) {
                        if (!in_array($source->getPath().'/'.$asset['file'], $handledFiles)) {
                            $asset['file'] = $source->url($asset['file']);
                            $filteredAssets[] = $asset;
                        }
                    } else {
                        $filteredAssets[] = $asset;
                    }
                }
            }
        }
        if ($filteredFiles) {
        }

        return $filteredAssets;
    }
}
