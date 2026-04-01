<?php

declare(strict_types=1);

namespace App\Controllers;

use App\NoteRepository;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;
use function React\Async\await;

final class ListNotes
{
    public function __construct(private NoteRepository $notes) {}

    public function __invoke(): ResponseInterface
    {
        return Response::json(await($this->notes->findAll()));
    }
}
