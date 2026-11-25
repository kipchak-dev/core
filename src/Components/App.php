<?php

namespace Kipchak\Core\Components;

use Slim;
use Throwable;

class App extends Slim\App
{
    /** @var list<callable(): void> */
    private array $terminating = [];

    /** @var list<callable(): void> */
    private array $afterResponse = [];

    /**
     * Registers work to run after every response, such as exporting telemetry, so that it does not delay the
     * client. Register it once, at start-up: in FrankenPHP worker mode, terminate() is called after each request
     * in the worker loop and runs it every time. For work that belongs to one request only, use afterResponse().
     */
    public function onTerminate(callable $callback): void
    {
        $this->terminating[] = $callback;
    }

    /**
     * Registers work to run once, after the current response has been sent, such as an upload the client need not
     * wait for. It runs at the next terminate(), before the onTerminate() callbacks, so that start-up work such as a
     * telemetry export sees what it recorded, and is then forgotten: in FrankenPHP worker mode it never runs again
     * for a later request. A callback registered while terminate() is
     * running belongs to the next request.
     */
    public function afterResponse(callable $callback): void
    {
        $this->afterResponse[] = $callback;
    }

    /**
     * Runs the callbacks registered with afterResponse() for this request, which are forgotten before they run, then
     * those registered with onTerminate(), each list in the order registered. The onTerminate() callbacks run last
     * so that an export among them includes what this request's callbacks recorded. Call it after the response has been
     * sent: in FrankenPHP worker mode, after frankenphp_handle_request() returns. A callback that throws does not
     * stop the others; its exception is reported through error_log().
     */
    public function terminate(): void
    {
        $once = $this->afterResponse;
        $this->afterResponse = [];
        foreach ([...$once, ...$this->terminating] as $callback) {
            try {
                $callback();
            } catch (Throwable $exception) {
                error_log('Kipchak terminate callback failed: ' . $exception->getMessage());
            }
        }
    }
}
