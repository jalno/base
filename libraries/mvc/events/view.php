<?php
namespace packages\base\view\events;
use \packages\base\view;
use \packages\base\event;
trait viewEvent{
	protected $view;
	function __construct(view $view){
		$this->view = $view;
	}
	public function getView(){
		return $this->view;
	}
}
class beforeLoad extends event{
	use viewEvent;
}
class afterLoad extends event{
	use viewEvent;
}
class afterOutput extends event{
	use viewEvent;
}
