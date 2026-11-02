<?php

namespace Kipchak\Core\Components;

use Slim;
use Throwable;

class App extends Slim\App
{
    /** @var list<callable(): void> */
    private array $terminating = [];

    /**
     * Registers work to run after the response has been sent, such as exporting telemetry, so that it does not
     * delay the client. In FrankenPHP worker mode, terminate() is called after each request in the worker loop.
     */
    public function onTerminate(callable $callback): void
    {
        $this->terminating[] = $callback;
    }

    /**
     * Runs the callbacks registered with onTerminate(), in the order registered. Call it after the response has
     * been sent: in FrankenPHP worker mode, after frankenphp_handle_request() returns. A callback that throws
     * does not stop the others; its exception is reported through error_log().
     */
    public function terminate(): void
    {
        foreach ($this->terminating as $callback) {
            try {
                $callback();
            } catch (Throwable $exception) {
                error_log('Kipchak terminate callback failed: ' . $exception->getMessage());
            }
        }
    }
}
