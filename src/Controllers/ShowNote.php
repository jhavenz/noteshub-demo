<?php

declare(strict_types=1);

namespace App\Controllers;

use App\NoteRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use function React\Async\await;

final class ShowNote
{
    public function __construct(private NoteRepository $notes) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $note = await($this->notes->findById((int) $request->getAttribute("id")));

        if ($note === null) {
            return Response::json(["error" => "not found"])->withStatus(404);
        }

        return Response::json($note);
    }
}
