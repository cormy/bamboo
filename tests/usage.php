#!/usr/bin/env php
<?php

namespace Cormy;

require __DIR__.'/../vendor/autoload.php';

use Generator;
use Cormy\Server\Bamboo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

// create your bamboo stem nodes, aka middlewares
$nodes = [
    function (ServerRequestInterface $request):Generator {
        // delegate $request to the next request handler, i.e. the middleware right below
        $response = yield $request;

        // mofify the response
        $response = $response->withHeader('X-PoweredBy', 'Unicorns');

        return $response;
    },
    function (ServerRequestInterface $request):Generator {
        // delegate $request to the next request handler, i.e. the $finalHandler below
        $response = yield $request;

        // mofify the response
        $response = $response->withHeader('content-type', 'application/json; charset=utf-8');

        return $response;
    },
];

// create the middleware pipe
$middlewarePipe = new Bamboo($nodes);

// create a handler for requests which reached the end of the pipe
$finalHandler = function (ServerRequestInterface $request):ResponseInterface {
    return new Response();
};

// and dispatch it
$response = $middlewarePipe->dispatch(new ServerRequest(), $finalHandler);

exit($response->getHeader('X-PoweredBy')[0] === 'Unicorns' ? 0 : 1);
