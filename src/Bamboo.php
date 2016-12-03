<?php

namespace Cormy\Server;

use Generator;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Bamboo style PSR-7 middleware pipe.
 */
class Bamboo implements MiddlewareInterface
{
    /**
     * @var (callable|MiddlewareInterface)[]
     */
    protected $nodes = [];

    /**
     * Bamboo style PSR-7 middleware pipe.
     *
     * @param (callable|MiddlewareInterface)[] $nodes the middlewares, which requests pass through
     */
    public function __construct(array $nodes)
    {
        array_map([$this, 'push'], $nodes);
    }

    /**
     * Push a middleware onto the end of $nodes type safe.
     *
     * @param callable|MiddlewareInterface $middleware
     */
    private function push(callable $middleware)
    {
        $this->nodes[] = $middleware;
    }

    /**
     * Process an incoming server request and return the response.
     *
     * @param ServerRequestInterface           $request
     * @param callable|RequestHandlerInterface $finalHandler
     *
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, callable $finalHandler):ResponseInterface
    {
        $dispatcher = new MiddlewareDispatcher($this, $finalHandler);

        return $dispatcher($request);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request):Generator
    {
        return $this->processMiddleware(0, $request);
    }

    /**
     * Process an incoming server request by delegating it to the middleware specified by $index.
     *
     * @param int                    $index   the $nodes index
     * @param ServerRequestInterface $request
     *
     * @return Generator
     */
    protected function processMiddleware(int $index, ServerRequestInterface $request):Generator
    {
        if (!array_key_exists($index, $this->nodes)) {
            $response = (yield $request);

            return $response;
        }

        $current = $this->nodes[$index]($request);
        $nextIndex = $index + 1;

        while ($current->valid()) {
            $nextRequest = $current->current();

            try {
                $nextResponse = yield from $this->processMiddleware($nextIndex, $nextRequest);
                $current->send($nextResponse);
            } catch (Throwable $exception) {
                $current->throw($exception);
            }
        }

        return $current->getReturn();
    }
}
