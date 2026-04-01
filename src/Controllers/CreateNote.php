<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Broadcast;
use App\NoteRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use function React\Async\await;

final class CreateNote
{
    public function __construct(private NoteRepository $notes, private Broadcast $broadcast) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        ['title' => $title, 'body' => $body] = $request->getAttribute('json_body', [
            'body' => '',
            'title' => '',
        ]);

        if (empty($title) || empty($body)) {
            return Response::json(['error' => 'title and body are required'])->withStatus(400);
        }

        $note = await($this->notes->create($title, $body));
        ($this->broadcast)('note.created', $note);

        return Response::json($note)->withStatus(201);
    }
}
