<?php
namespace packages\base;
class event implements EventInterface{
	public function trigger(){
        events::trigger($this);
    }
}
