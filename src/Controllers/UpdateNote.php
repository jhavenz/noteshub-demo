<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Broadcast;
use App\NoteRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use function React\Async\await;

final class UpdateNote
{
    public function __construct(private NoteRepository $notes, private Broadcast $broadcast) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');
        $existing = await($this->notes->findById($id));

        if ($existing === null) {
            return Response::json(['error' => 'not found'])->withStatus(404);
        }

        $data = $request->getAttribute('json_body', []);
        $title = $data['title'] ?? $existing['title'];
        $body = $data['body'] ?? $existing['body'];

        $note = await($this->notes->update($id, $title, $body));
        ($this->broadcast)('note.updated', $note);

        return Response::json($note);
    }
}
