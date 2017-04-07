<?php
namespace packages\base\processes;
use \packages\base\log;
use \packages\base\IO\file\local as file;
use \packages\base\IO\directory\local as directory;
use \packages\base\packages;
use \packages\base\json;
use \packages\base\process;
use \packages\base\loader;
use \packages\base\frontend\theme;
use \packages\base\frontend\source;
class frontend extends process{
	private $repo;
	private $repoConfig;
	public function prepareAssets(){
		$log = log::getInstance();
		$log->debug("get list of frontend sources");
		$sources = theme::get();
		if(!$sources){
			$log->reply("empty");
			$log->debug("try to loading themes");
			loader::themes();
			$log->debug("get list of frontend sources");
			$sources = theme::get();
		}
		if(!$sources){
			$log->reply("empty");
			$log->debug("nothing to do");
			return true;
		}
		foreach($sources as $key => $source){
			if(!in_array($source->getPath(), ['packages/base/frontend', 'packages/userpanel/frontend', 'packages/stats/frontend'])){
				unset($sources[$key]);
			}
		}
		foreach($sources as $source){
			$this->prepareSource($source);
		}
		$log->debug("Webpack");
		$this->webpack($sources);
		
	}
	private function webpackPackages():array{
		return [
			'webpack',
			'typescript',
			'less',
			'less-plugin-clean-css',
			'ts-loader',
			'extract-text-webpack-plugin',
			'css-loader',
			'file-loader',
			'less-loader',
			'style-loader',
			'url-loader'
		];
	}
	private function prepareSource(source $source){
		$log = log::getInstance();
		$log->debug("install needed NPM Packages");
		$this->installNPMPackages($source);
		$log->debug("Copy source to repository");
		$this->copySource($source);
	}
	private function createRepo(){
		$log = log::getInstance();
		if(!$this->repo){
			$this->repo = new directory(packages::package('base')->getFilePath('storage/private/frontend'));
			$log->debug("looking for repository in", $this->repo->getPath());
			if(!$this->repo->exists()){
				$log->reply("notfound");
				$log->debug("creating it");
				if($this->repo->make(true)){
					$log->reply("Success");	
				}else{
					$log->reply()->fatal('Failed');
				}
			}else{
				$log->reply("Found");
			}
		}
		if(!$this->repoConfig){
			$this->repoConfig = $this->repo->file("package.json");

			$log->debug("looking for repository config in", $this->repoConfig->getPath());
			if(!$this->repoConfig->exists()){
				$log->reply("notfound");
				$log->debug("creating it");
				if($this->repoConfig->write(json\encode(array(
					'dependencies' => []
				), json\PRETTY | json\FORCE_OBJECT))){
					$log->reply("Success");
				}else{
					$log->reply()->fatal('Failed');
				}
			}else{
				$log->reply("Found");
			}
		}
	}
	private function getRepoConfig():array{
		$log = log::getInstance();
		$this->createRepo();
		$log->debug("read repository config");
		return json\decode($this->repoConfig->read());
	}
	private function setRepoConfig(array $config){
		$log = log::getInstance();
		$this->createRepo();
		$log->debug("write repository config");
		$this->repoConfig->write(json\encode($config, json\PRETTY));
	}
	private function installNPMPackages(source $source){
		$log = log::getInstance();
		$toinstall = array();
		$tocheck = $this->webpackPackages();

		$log->debug("looking for npm assets in source");
		$assets = $source->getAssets('package');
		if($assets){
			$log->reply(count($assets), 'found');
			$tocheck = array_merge($tocheck, array_column($assets, 'name'));
		}

		$config = $this->getRepoConfig();
		if(!isset($config['dependencies'])){
			$config['dependencies'] = array();
		}
		$dependencies = array_keys($config['dependencies']);
		if(isset($config['devDependencies'])){
			$dependencies = array_merge($dependencies, array_keys($config['devDependencies']));
		}
		foreach($tocheck as $package){
			if(!in_array($package, $dependencies)){
				$log->debug("need to install {$package}");
				$toinstall[] = $package;
			}
		}
		if($toinstall){
			if(!function_exists('shell_exec')){
				throw new notShellAccess();
			}
			$log->debug("install missing packages");
			$output = shell_exec("npm --parseable --silent --no-progress --save --prefix ".$this->repo->getPath()." install ".implode(" ", $toinstall).' > /dev/null 2>&1');
			$log->reply($output);
			$node_modules = $this->repo->directory('node_modules');
			$log->debug("get list of installed node modules");
			$node_modules = $node_modules->directories(false);
			$log->reply(count($node_modules), "found");
			$module_names = array_column($node_modules, 'basename');
			foreach($toinstall as $dependency){
				$log->debug("check installtion of", $dependency);
				$name = explode("/", $dependency, 2);
				$key = array_search($name[0], $module_names);
				if($key === false){
					throw new InstallNpmPackageException($dependency);
				}
				$node_module = $node_modules[$key];
				if(isset($name[1])){
					if(!$node_module->directory($name[1])->exists()){
						throw new InstallNpmPackageException($dependency);
					}
				}
				$log->reply("Success");
			}
		}else{
			$log->debug("nothing to do");
		}
	}
	private function getRelativePathOfSource(source $source):string{
		$sourcePath = $source->getPath();
		if(preg_match("/(packages\/([a-zA-Z0-9|_]+).+)$/", $sourcePath, $matches)){
			$sourcePath = $matches[1];
		}
		return $sourcePath;
	}
	private function getDistinationOfSource(source $source){
		$sourcePath = $source->getPath();
		if(preg_match("/(packages\/([a-zA-Z0-9|_]+).+)$/", $sourcePath, $matches)){
			$sourcePath = $matches[1];
			$log->reply("use relative version:", $sourcePath);
		}
	}
	private function copySource(source $source){
		$log = log::getInstance();
		$sourcePath = $this->getRelativePathOfSource($source);
		$log->debug("source path:", $sourcePath);
		$sourceDir = new directory($sourcePath);
		$dest = $this->repo->directory('src/'.$sourcePath);
		$log->debug("destination path", $dest->getPath());
		if(!$dest->exists()){
			$log->reply("notfound");
			$log->debug("making it");
			if($dest->make(true)){
				$log->reply("Success");	
			}else{
				$log->reply()->fatal('Failed');
				return false;
			}
		}
		$log->debug("get list of files and directories in source");
		$items = $sourceDir->items(true);
		$log->reply(count($items), "items found");
        foreach($items as $item){
            $relativePath = substr($item->getPath(), strlen($sourcePath)+1);
			$log->debug("copy", $relativePath);
			if(strpos($relativePath, 'node_modules') === false){
				if($item instanceof file){
					if($item->getExtension() != 'php'){
						$destFile = $dest->file($relativePath);
						if($item->copyTo($destFile)){
							$log->reply("Success");
						}else{
							$log->reply()->fatal("Failed");
							return false;
						}
					}else{
						$log->reply("skipped, bacause It is php file");
					}
				}else{
					$destDir = $dest->directory($relativePath);
					if(!$destDir->exists()){
						if($destDir->make(true)){
							$log->reply("Success");
						}else{
							$log->reply()->fatal("Failed");
							return false;	
						}
					}
				}
			}else{
				$log->reply("skipped, bacause It is a node module");
			}
        }
		return true;

	}
	private function webpackConfig(array $sources){
		$log = log::getInstance();
		$log->debug("get list of assets");
		$entries = array();
		foreach($sources as $source){
			$name = $source->getName();
			if(!isset($entries[$name])){
				$entries[$name] = array();
			}
			$assets = $source->getAssets();
			foreach($assets as $asset){
				if(in_array($asset['type'], ['js', 'css', 'less', 'ts'])){
					if(isset($asset['file'])){
						$file = "./";
						if(substr($asset['file'], 0, 13) != 'node_modules/'){
							$file .= 'src/'.$this->getRelativePathOfSource($source).'/';
						}
						$file .= $asset['file'];
						if(!in_array($file, $entries[$name])){
							$entries[$name][] = $file;
						}
					}
				}
			}
			if(empty($entries[$name])){
				unset($entries[$name]);
			}
		}
		$webpackConfig = $this->repo->file('webpack.entries.json');
		$log->debug("wrting entry points to", $webpackConfig->getPath());
		$webpackConfig->write(json\encode($entries, json\PRETTY));
	}
	private function webpack(array $sources){
		$log = log::getInstance();
		$log->debug("config webpack");
		$this->webpackConfig($sources);
		$log->debug("get list of handled assets");
		$result = array(
			'handledFiles' => [],
			'outputedFiles' => [],
		);
		foreach($sources as $source){
			$path = $this->getRelativePathOfSource($source);
			$assets = $source->getAssets();
			foreach($assets as $asset){
				if(in_array($asset['type'], ['js', 'css', 'less', 'ts'])){
					if(isset($asset['file'])){
						$result['handledFiles'][$source->getName()][] = $path.'/'.$asset['file'];
					}
				}
			}
		}
		$log->debug("run webpack");
		if(!function_exists('shell_exec')){
			$log->reply()->fatal('no shell access');
			throw new notShellAccess();
		}
		$distDir = new directory(packages::package('base')->getFilePath('storage/public/frontend/dist'));
		$webpack = $this->repo->file('node_modules/.bin/webpack')->getPath();
		$context = $this->repo->getRealPath().'/';
		$config = $this->repo->file('webpack.config.js')->getRealPath();
		$output = shell_exec("{$webpack} --context={$context} --config={$config} --progress=false --colors=false --json --hide-modules");
		$parsedOutput = json\decode($output);
		if(!$parsedOutput){
			$log->reply()->fatal('cannot parse response');
			throw new WebpackException($output);
		}
		if(!isset($parsedOutput['errors']) or !empty($parsedOutput['errors'])){
			$log->reply()->fatal('there is some errors');
			throw new WebpackException($output);
		}
		$log->reply("Success");
		$log->debug("save chunk paths");
		foreach($parsedOutput['chunks'] as $chunk){
			foreach($chunk['files'] as $file){
				$file = $distDir->file($file);
				if(!$file->exists()){
					throw new WebpackChunkFileException($file->getPath());
				}
				foreach($chunk['names'] as $name){
					$result['outputedFiles'][$name][] = $file->getPath();
				}
			}
		}
		$this->repo->file('result.json')->write(json\encode($result, json\PRETTY));
	}
	public function clean(){
		$this->cleanRepository();
		$this->cleanDistFiles();
	}
	public function cleanSourcesRepository(){
		$repo = new directory(packages::package('base')->getFilePath('storage/private/frontend/src'));
		$repo->delete();
	}
	public function cleanNPMPackages(){
		$modules = new directory(packages::package('base')->getFilePath('storage/private/frontend/node_modules'));
		$modules->delete();
	}
	public function cleanRepository(){
		$repo = new directory(packages::package('base')->getFilePath('storage/private/frontend'));
		$repo->delete();
	}
	public function cleanDistFiles(){
		$repo = new directory(packages::package('base')->getFilePath('storage/public/frontend'));
		$repo->delete();
	}
}
class InstallNpmPackageException extends \Exception{
	protected $package;
	public function __construct(string $package){
		$this->package = $package;
		parent::__construct("cannot install {$package} package");
	}
	public function getPackage():string{
		return $this->package;
	}

}
class WebpackException extends \Exception{}
class WebpackChunkFileException extends \Exception{}