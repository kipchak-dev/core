<?php

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\Components\Http\Codes;
use Kipchak\Core\Tests\TestCase;

class CodesTest extends TestCase
{
    public function testSomething()
    {
        $x = Codes::getCode(100);
        $this->assertEquals('CONTINUE', $x);
    }

}