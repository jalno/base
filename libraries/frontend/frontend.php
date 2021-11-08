<?php
namespace packages\base;
use \packages\base\frontend;
use \packages\base\IO\directory;
use \packages\base\IO\file;
use \packages\base\json;
use \packages\base\frontend\source;
class frontend{
	private static function getWebpackResult():array{
		$base = packages::package('base');
		$privateRepo = new directory\local($base->getFilePath('storage/private/frontend'));
		$result = array();
		$resultFile = $privateRepo->file('result.json');
		if($resultFile->exists()){
			$result = json\decode($resultFile->read());
		}
		if(!is_array($result)){
			$result = array();
		}
		if(!isset($result['handledFiles'])){
			$result['handledFiles'] = [];
		}
		if(!isset($result['outputedFiles'])){
			$result['outputedFiles'] = [];
		}
		return $result;
	}
	public static function checkAssetsForWebpack(array $sources):array{
		$result = self::getWebpackResult();
		$filteredAssets = [];
		$filteredFiles = [];
		$commonAssets = [];
		if(isset($result['outputedFiles']['common'])){
			foreach($result['outputedFiles']['common'] as $file){
				$file = new file\local($file);
				$commonAssets[] = array(
					'type' => $file->getExtension(),
					'file' => "/".$file->getPath()
				);
				$filteredFiles[] = $file->getPath();
			}
		}
		foreach($sources as $source){
			$handledFiles = [];
			$name = $source->getName();
			$assets = $source->getAssets();
			if(isset($result['handledFiles'][$name])){
				$handledFiles = $result['handledFiles'][$name];
				if($commonAssets){
					$filteredAssets = array_merge($filteredAssets, $commonAssets);
					$commonAssets = [];
				}
			}

			if(isset($result['outputedFiles'][$name])){
				foreach($result['outputedFiles'][$name] as $file){
					$file = new file\local($file);
					if(!in_array($file->getPath(), $filteredFiles)){
						$filteredAssets[] = array(
							'type' => $file->getExtension(),
							'file' => "/".$file->getPath()
						);
						$filteredFiles[] = $file->getPath();
					}
				}
			}
			foreach($assets as $asset){
				if(in_array($asset['type'], ['js', 'css', 'less', 'ts'])){
					if(isset($asset['file'])){
						if(!in_array($source->getPath().'/'.$asset['file'], $handledFiles)){
							$asset['file'] = $source->url($asset['file']);
							$filteredAssets[] = $asset;
						}
					}else{
						$filteredAssets[] = $asset;
					}
				}
			}
		}
		if($filteredFiles){
			
		}
		return $filteredAssets;
	}
}
