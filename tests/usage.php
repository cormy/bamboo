#!/usr/bin/env php
<?php

namespace Cormy;

require __DIR__.'/../vendor/autoload.php';

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

// and dispatch it
$response = $middlewarePipe->dispatch(new \Zend\Diactoros\ServerRequest(), $finalHandler);

exit($response->getHeader('X-PoweredBy')[0] === 'Unicorns' ? 0 : 1);
