<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Stream\ThroughStream;

class SseStream
{
    public function __construct(private \SplObjectStorage $subscribers) {}

    public function __invoke(): ResponseInterface
    {
        $stream = new ThroughStream();
        $this->subscribers->offsetSet($stream);

        $keepalive = Loop::addPeriodicTimer(30, static function () use ($stream) {
            $stream->write(": keepalive\n\n");
        });

        $stream->on("close", function () use ($stream, $keepalive) {
            Loop::cancelTimer($keepalive);
            $this->subscribers->offsetUnset($stream);
        });

        return new Response(
            200,
            [
                "Cache-Control" => "no-cache",
                "Content-Type" => "text/event-stream",
            ],
            $stream,
        );
    }
}
