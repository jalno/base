<?php
namespace packages\base\Image;

class Color {
	
	/**
	 * Construct a RGB color
	 * 
	 * @param int $r red, value: 0-255
	 * @param int $g green, value: 0-255
	 * @param int $b blue, value: 0-255
	 * @return packages\base\Image\Color
	 */
	public static function fromRGB(int $r, int $g, int $b): Color{
		if ($r < 0 or $r > 255) {
			throw new InvalidColorRangeException("red is " . $r);
		}
		if ($g < 0 or $g > 255) {
			throw new InvalidColorRangeException("green is " . $g);
		}
		if ($b < 0 or $b > 255) {
			throw new InvalidColorRangeException("blue is " . $b);
		}
		$color = new Color();
		$color->r = $r;
		$color->g = $g;
		$color->b = $b;
		$color->a = 1;
		return $color;
	}

	/**
	 * Construct a RGB color with alpha channel
	 * 
	 * @param int $r red, value: 0-255
	 * @param int $g green, value: 0-255
	 * @param int $b blue, value: 0-255
	 * @param float $a blue, value: 0-1
	 * @return packages\base\Image\Color
	 */
	public static function fromRGBA(int $r, int $g, int $b, float $a): Color {
		if ($a < 0 or $a > 1) {
			throw new InvalidColorRangeException("alpha is " . $a);
		}
		$color = self::fromRGB($r, $g, $b);
		$color->a = $a;
		return $color;
	}

	/** @var int red, value: 0-255 */
	private $r;

	/** @var int green, value: 0-255 */
	private $g;

	/** @var int blue, value: 0-255 */
	private $b;

	/** @var float alpha, value: 0-1 */
	private $a;
	
	public function toRGB(): array {
		return [$this->r, $this->g, $this->b];
	}
	public function toRGBA(): array {
		return [$this->r, $this->g, $this->b, $this->a];
	}
}
