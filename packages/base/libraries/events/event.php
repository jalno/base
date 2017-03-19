<?php
namespace packages\base;
class event{
	public function trigger(){
        events::trigger($this);
    }
}
