<?php

namespace Cormy\Server;

use Exception;
use Cormy\Server\Helpers\CounterMiddleware;
use Cormy\Server\Helpers\FinalHandler;
use Cormy\Server\Helpers\Response;
use Cormy\Server\Helpers\MultiDelegationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;

class BambooTest extends \PHPUnit_Framework_TestCase
{
    use \VladaHejda\AssertException;

    public function testEmptyBamboosShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');

        $sut = new Bamboo([]);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!', (string) $response->getBody());
    }

    public function testSingleMiddelwareShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = new CounterMiddleware(0);

        $sut = new Bamboo([$middleware]);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0', (string) $response->getBody());
    }

    public function testMiddlewareReuseShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = new CounterMiddleware(0);

        $sut = new Bamboo([$middleware, $middleware]);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!01', (string) $response->getBody());
    }

    public function testMultipleMiddlewaresShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new CounterMiddleware(3),
            new CounterMiddleware(2),
            new CounterMiddleware(1),
            new CounterMiddleware(0),
        ];

        $sut = new Bamboo($middlewares);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0123', (string) $response->getBody());
    }

    public function testMultiDelegationMiddlewaresShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new MultiDelegationMiddleware(42),
            new CounterMiddleware(1),
        ];

        $sut = new Bamboo($middlewares);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!42', (string) $response->getBody());
    }

    public function testCallbackMiddelwareShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = function (ServerRequestInterface $request) {
            static $index = 0;

            $response = yield $request;
            $response->getBody()->write((string) $index++);

            return $response;
        };

        $sut = new Bamboo([$middleware]);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0', (string) $response->getBody());
    }

    public function testMiddlewaresCanHandleFinalHandlerExceptions()
    {
        $finalHandler = function (ServerRequestInterface $request):ResponseInterface {
            throw new Exception('Oops, something went wrong!', 500);
        };
        $middlewares = [
            function (ServerRequestInterface $request) {
                try {
                    $response = yield $request;
                } catch (Exception $e) {
                    return new Response('Catched: '.$e->getMessage(), $e->getCode());
                }
            },
        ];

        $sut = new Bamboo($middlewares);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Catched: Oops, something went wrong!', (string) $response->getBody());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testMiddlewaresCanHandleMiddlewareExceptions()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            function (ServerRequestInterface $request) {
                try {
                    $response = yield $request;
                } catch (Exception $e) {
                    return new Response('Catched: '.$e->getMessage(), $e->getCode());
                }
            },
            function (ServerRequestInterface $request) {
                $response = yield $request;
                throw new Exception('Oops, something went wrong!', 500);
            },
        ];

        $sut = new Bamboo($middlewares);
        $response = $sut->dispatch(new ServerRequest(), $finalHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Catched: Oops, something went wrong!', (string) $response->getBody());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testMiddlewareCallerHaveToHandleFinalHandlerExceptions()
    {
        $finalHandler = function (ServerRequestInterface $request):ResponseInterface {
            throw new Exception('Oops, something went wrong!', 500);
        };
        $middlewares = [
            function (ServerRequestInterface $request) {
                return yield $request;
            },
        ];

        $sut = new Bamboo($middlewares);

        $this->assertException(function () use ($sut, $finalHandler) {
            $sut->dispatch(new ServerRequest(), $finalHandler);
        }, Exception::class, 500, 'Oops, something went wrong!');
    }

    public function testMiddlewareCallerHaveToHandleMiddlewareExceptions()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            function (ServerRequestInterface $request) {
                return yield $request;
            },
            function (ServerRequestInterface $request) {
                $response = yield $request;
                throw new Exception('Oops, something went wrong!', 500);
            },
        ];

        $sut = new Bamboo($middlewares);

        $this->assertException(function () use ($sut, $finalHandler) {
            $sut->dispatch(new ServerRequest(), $finalHandler);
        }, Exception::class, 500, 'Oops, something went wrong!');
    }
}
