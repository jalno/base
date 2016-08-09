<?php
namespace packages\base;
use \packages\base\frontend\theme;
use \packages\base\frontend\location;
use \packages\base\frontend\source;
class view{
	protected $title = array();
	protected $description;
	protected $file;
	protected $source;
	protected $css = array();
	protected $js = array();
	protected $data = array();
	public function setTitle($title){
		if(is_array($title)){
			$this->title = $title;
			return true;
		}elseif(is_string($title)){
			return $this->setTitle(array($title));
		}
		return false;
	}
	public function getTitle($spliter = ' | '){
		return $spliter ? implode($spliter, $this->title) : $title;
	}
	public function setDescription($description){
		$this->description = $description;
	}
	public function getDescription(){
		return $this->description;
	}
	public function addCSS($code, $name = ''){
		$this->css[] = array(
			'name' => $name,
			'type' => 'inline',
			'code' => $code
		);
	}
	public function addCSSFile($file,$name =''){
		$this->css[] = array(
			'name' => $name,
			'type' => 'file',
			'file' => $file
		);
	}
	protected function loadCSS(){
		foreach($this->css as $css){
			if($css['type'] == 'file'){
				echo("<link rel=\"stylesheet\" type=\"text/css\" href=\"{$css['file']}\" />\n");
			}
		}
		foreach($this->css as $css){
			if($css['type'] == 'inline'){
				echo("<style>\n{$css['code']}\n</style>\n");
			}
		}
	}
	public function addJS($code, $name = ''){
		$this->js[] = array(
			'name' => $name,
			'type' => 'inline',
			'code' => $code
		);
	}
	public function addJSFile($file,$name =''){
		$this->js[] = array(
			'name' => $name,
			'type' => 'file',
			'file' => $file
		);
	}
	protected function loadJS(){
		foreach($this->js as $js){
			if($js['type'] == 'file'){
				echo("<script src=\"{$js['file']}\"></script>\n");
			}
		}
		foreach($this->js as $js){
			if($js['type'] == 'inline'){
				echo("<script>\n{$js['code']}\n</script>\n");
			}
		}
	}
	public function setSource(source $source){
		$this->source = $source;
		theme::setPrimarySource($this->source);
		$assets = $this->source->getAssets();
		foreach($assets as $asset){
			if($asset['type'] == 'css'){
				if(isset($asset['file'])){
					$this->addCSSFile(theme::url($asset['file']));
				}elseif(isset($asset['inline'])){
					$this->addCSS($asset['inline']);
				}
			}elseif($asset['type'] == 'js'){
				if(isset($asset['file'])){
					$this->addJSFile(theme::url($asset['file']));
				}elseif(isset($asset['inline'])){
					$this->addJS($asset['inline']);
				}
			}
		}

	}
	public function setFile($file){
		$this->file = $file;
	}
	static public function byName($viewName){
		$location = theme::locate($viewName);
		if($location instanceof location){
			$location->source->register_autoload();
			$location->source->register_translates(translator::getDefaultLang());
			$view = new $location->view();
			$view->setSource($location->source);
			if($location->file){
				$view->setFile($location->file);
			}
			return $view;
		}
		return false;
	}
	public function setData($data, $key = null){
		if($key){
			$this->data[$key] = $data;
		}else{
			$this->data = $data;
		}
	}
	public function getData($key = null){
		if($key){
			return(isset($this->data[$key]) ? $this->data[$key] : false);
		}else{
			return $this->data;
		}
	}
	public function output(){
		if($this->file){
			if(method_exists($this, '__beforeLoad')){
				$this->__beforeLoad();
			}
			$path = $this->source->getPath()."/".$this->file;
			require_once($path);
		}
	}
}
?>
