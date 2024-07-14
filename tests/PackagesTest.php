<?php
namespace packages\base\tests;

use packages\base\Packages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Packages::class)]
class PackagesTest extends TestCase
{
	
	public function testSingleton(): void {
		$this->assertFalse(Packages::hasInstance());
		$this->assertInstanceOf(Packages::class, Packages::getInstance());
		$this->assertTrue(Packages::hasInstance());
		Packages::clearInstance();
		$this->assertFalse(Packages::hasInstance());
		$this->assertSame([], Packages::__callStatic('get', []));
	}
}
