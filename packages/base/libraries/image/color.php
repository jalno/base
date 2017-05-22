<?php
namespace packages\base\image;
class color{
	private $r;
	private $g;
	private $b;
	private $a;
	public static function fromRGB(int $r,int $g,int $b):color{
		if($r < 0 or $r > 255){
			throw new InvalidColorRangeException("red is ".$r);
		}
		if($g < 0 or $g > 255){
			throw new InvalidColorRangeException("green is ".$g);
		}
		if($b < 0 or $b > 255){
			throw new InvalidColorRangeException("blue is ".$b);
		}
		$color = new color();
		$color->r = $r;
		$color->g = $g;
		$color->b = $b;
		$color->a = 1;
		return $color;
	}
	public static function fromRGBA(int $r,int $g,int $b,int $a):color{
		if($a < 0 or $a > 1){
			throw new InvalidColorRangeException("alpha is ".$a);
		}
		$color = self::fromRGB($r, $g, $b);
		$color->a = $a;
		return $color;
	}
	public function toRGB():array{
		return[$this->r,$this->g, $this->b];
	}
	public function toRGBA():array{
		return[$this->r,$this->g, $this->b, $this->a];
	}
}