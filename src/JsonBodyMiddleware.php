<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class JsonBodyMiddleware
{
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        if (!str_contains($request->getHeaderLine("Content-Type"), "application/json")) {
            return Response::json(["error" => "Content-Type must be application/json"])->withStatus(
                415,
            );
        }

        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body)) {
            return Response::json(["error" => "invalid JSON body"])->withStatus(400);
        }

        return $next($request->withAttribute("json_body", $body));
    }
}
