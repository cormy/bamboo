# Cormy Bamboo [![Build Status](https://travis-ci.org/cormy/bamboo.svg?branch=master)](https://travis-ci.org/cormy/bamboo) [![Coverage Status](https://coveralls.io/repos/cormy/bamboo/badge.svg?branch=master&service=github)](https://coveralls.io/github/cormy/bamboo?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cormy/bamboo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/cormy/bamboo/?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/54f1099a-3ff0-4328-836d-e80438ae75dc/big.png)](https://insight.sensiolabs.com/projects/54f1099a-3ff0-4328-836d-e80438ae75dc)

> :bamboo: Bamboo style [PSR-7](http://www.php-fig.org/psr/psr-7) **middleware pipe** using generators


## Install

```
composer require cormy/bamboo
```


## Usage

```php
use Cormy\Server\Bamboo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// create your bamboo stem nodes, aka middlewares
$nodes = [];

$nodes[] = function (ServerRequestInterface $request):\Generator {
    // delegate $request to the next request handler, i.e. the middleware right below
    $response = (yield $request);

    return $response->withHeader('X-PoweredBy', 'Unicorns');
};

$nodes[] = function (ServerRequestInterface $request):\Generator {
    // delegate $request to the next request handler, i.e. the $finalHandler below
    $response = (yield $request);

    return $response->withHeader('content-type', 'application/json; charset=utf-8');
};

// create the middleware pipe
$middlewarePipe = new Bamboo($nodes);

// create a handler for requests which reached the end of the pipe
$finalHandler = function (ServerRequestInterface $request):ResponseInterface {
    return new \Zend\Diactoros\Response();
};

// and dispatch a request
$response = $middlewarePipe->dispatch(new \Zend\Diactoros\ServerRequest(), $finalHandler);
```


## API

### `Cormy\Server\Bamboo implements MiddlewareInterface`

#### `Bamboo::__construct`

```php
/**
 * Bamboo style PSR-7 middleware pipe.
 *
 * @param callable[]|MiddlewareInterface[] $nodes the middlewares, which requests pass through
 */
public function __construct(array $nodes)
```

#### `Bamboo::dispatch`

```php
/**
 * Process an incoming server request and return the response.
 *
 * @param ServerRequestInterface           $request
 * @param callable|RequestHandlerInterface $finalHandler
 *
 * @return ResponseInterface
 */
public function dispatch(ServerRequestInterface $request, callable $finalHandler):ResponseInterface
```

#### Inherited from [`MiddlewareInterface::__invoke`](https://github.com/cormy/server-middleware)

```php
/**
 * Process an incoming server request and return the response, optionally delegating
 * to the next request handler.
 *
 * @param ServerRequestInterface $request
 *
 * @return Generator yields PSR `ServerRequestInterface` instances and returns a PSR `ResponseInterface` instance
 */
public function __invoke(ServerRequestInterface $request):Generator;
```


## Related

* [Cormy\Server\Onion](https://github.com/cormy/onion) – Onion style PSR-7 **middleware stack** using generators
* [Cormy\Server\MiddlewareDispatcher](https://github.com/cormy/cormy/server-middleware-dispatcher) – Cormy PSR-7 server **middleware dispatcher**
* [Cormy\Server\RequestHandlerInterface](https://github.com/cormy/server-request-handler) – Common interfaces for PSR-7 server request handlers
* [Cormy\Server\MiddlewareInterface](https://github.com/cormy/server-middleware) – Common interfaces for Cormy PSR-7 server middlewares


## License

MIT © [Michael Mayer](http://schnittstabil.de)
