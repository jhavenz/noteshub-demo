<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Broadcast;
use App\NoteRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use function React\Async\await;

final class DeleteNote
{
    public function __construct(private NoteRepository $notes, private Broadcast $broadcast) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');
        $existing = await($this->notes->findById($id));

        if ($existing === null) {
            return Response::json(['error' => 'not found'])->withStatus(404);
        }

        await($this->notes->delete($id));
        ($this->broadcast)('note.deleted', ['id' => $id]);

        return Response::json(['deleted' => true]);
    }
}
