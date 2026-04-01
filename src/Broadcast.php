<?php

declare(strict_types=1);

namespace App;

class Broadcast
{
    public function __construct(
        /** @var \SplObjectStorage<\React\Stream\ThroughStream> $subscribers */
        private \SplObjectStorage $subscribers,
    ) {}

    public function __invoke(string $event, array $data): void
    {
        if ($this->subscribers->count() === 0) {
            return;
        }

        $ssePayload = "event: {$event}\ndata: " . json_encode($data) . "\n\n";

        $deadSubscribers = [];
        foreach ($this->subscribers as $stream) {
            if (!$stream->isWritable()) {
                $deadSubscribers[] = $stream;
                continue;
            }

            $stream->write($ssePayload);
        }

        foreach ($deadSubscribers as $stream) {
            $this->subscribers->offsetUnset($stream);
        }
    }
}
