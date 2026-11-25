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

    /** C1: a callback registered with afterResponse() during request 1 runs once, after request 1 only. */
    public function testAfterResponseRunsOnceAfterItsOwnRequest()
    {
        $app = AppFactory::create();
        $calls = 0;
        $app->afterResponse(function () use (&$calls) { $calls++; });

        $app->terminate();
        $this->assertSame(1, $calls, 'runs after its own request');
        $app->terminate();
        $app->terminate();
        $this->assertSame(1, $calls, 'never again after later requests');
    }

    /** C2: a callback registered with onTerminate() at start-up runs after every request (telemetry relies on it). */
    public function testOnTerminateStillRunsAfterEveryRequest()
    {
        $app = AppFactory::create();
        $calls = 0;
        $app->onTerminate(function () use (&$calls) { $calls++; });
        $app->afterResponse(function () {});

        $app->terminate();
        $app->terminate();
        $app->terminate();
        $this->assertSame(3, $calls);
    }

    /** This request's callbacks run first, then the start-up callbacks, each in the order registered. */
    public function testThisRequestsCallbacksRunBeforeStartUpCallbacks()
    {
        $app = AppFactory::create();
        $calls = [];
        $app->afterResponse(function () use (&$calls) { $calls[] = 'request'; });
        $app->onTerminate(function () use (&$calls) { $calls[] = 'start-up'; });
        $app->afterResponse(function () use (&$calls) { $calls[] = 'request 2'; });

        $app->terminate();
        $this->assertSame(['request', 'request 2', 'start-up'], $calls);
    }

    /**
     * C6: what an afterResponse() callback records is exported by a start-up export callback in the same
     * terminate(), not left for the next request (a quiet worker might never have one).
     */
    public function testAStartUpExportSeesWhatThisRequestsCallbackRecorded()
    {
        $app = AppFactory::create();
        $recorded = [];
        $exported = [];
        $app->onTerminate(function () use (&$recorded, &$exported) {
            array_push($exported, ...$recorded);
            $recorded = [];
        });
        $app->afterResponse(function () use (&$recorded) { $recorded[] = 'upload span'; });

        $app->terminate();
        $this->assertSame(['upload span'], $exported);
    }

    /** C3: an afterResponse() callback that throws does not stop the next one, and nothing is left behind. */
    public function testAThrowingAfterResponseCallbackDoesNotStopTheRestAndLeavesNothingBehind()
    {
        $app = AppFactory::create();
        $calls = [];
        $app->afterResponse(function () { throw new \RuntimeException('boom'); });
        $app->afterResponse(function () use (&$calls) { $calls[] = 'second'; });

        $log = ini_set('error_log', '/dev/null');
        $app->terminate();
        $app->terminate();
        ini_set('error_log', $log === false ? '' : $log);

        $this->assertSame(['second'], $calls);
    }

    /** C4: a callback registered while terminate() runs belongs to the next request, and runs once there. */
    public function testAfterResponseRegisteredDuringTerminateRunsAtTheNextTerminate()
    {
        $app = AppFactory::create();
        $calls = [];
        $app->afterResponse(function () use ($app, &$calls) {
            $calls[] = 'first';
            $app->afterResponse(function () use (&$calls) { $calls[] = 'registered during terminate'; });
        });

        $app->terminate();
        $this->assertSame(['first'], $calls, 'not in the same pass');
        $app->terminate();
        $app->terminate();
        $this->assertSame(['first', 'registered during terminate'], $calls, 'once, at the next terminate');
    }

    public function testTerminateWithoutCallbacksDoesNothing()
    {
        AppFactory::create()->terminate();
        $this->addToAssertionCount(1);
    }
}
