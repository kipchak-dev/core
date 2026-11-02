<?php

namespace Kipchak\Core\Tests\Unit;

use Kipchak\Core\Components\Factory\AppFactory;
use PHPUnit\Framework\TestCase;

class TerminateTest extends TestCase
{
    public function testCallbacksRunInOrderAndOneFailureDoesNotStopTheRest()
    {
        $app = AppFactory::create();
        $calls = [];
        $app->onTerminate(function () use (&$calls) { $calls[] = 'first'; });
        $app->onTerminate(function () { throw new \RuntimeException('boom'); });
        $app->onTerminate(function () use (&$calls) { $calls[] = 'third'; });

        $log = ini_set('error_log', '/dev/null');
        $app->terminate();
        ini_set('error_log', $log === false ? '' : $log);

        $this->assertSame(['first', 'third'], $calls);
    }

    public function testTerminateWithoutCallbacksDoesNothing()
    {
        AppFactory::create()->terminate();
        $this->addToAssertionCount(1);
    }
}
