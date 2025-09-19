<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicExample(): void
    {
        $this->assertTrue(true);
    }
    
    public function testEnvironment(): void
    {
        $this->assertEquals('testing', $_ENV['APP_ENV']);
    }
    
    public function testAddition(): void
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result);
    }
}