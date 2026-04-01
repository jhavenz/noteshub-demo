<?php

declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use App\Controllers\DbHealthCheck;
use App\Controllers\SseStream;
use App\JsonBodyMiddleware;
use App\Controllers\CreateNote;
use App\Controllers\ListNotes;
use App\Controllers\RelatedNotes;
use App\Controllers\DeleteNote;
use App\Controllers\ShowNote;
use App\Controllers\UpdateNote;
use FrameworkX\App;
use FrameworkX\Container;
use FrameworkX\FilesystemHandler;
use React\Mysql\MysqlClient;

$container = new Container([
    MysqlClient::class => static fn(string $DATABASE_URL) => new MysqlClient($DATABASE_URL),
    \SplObjectStorage::class => new \SplObjectStorage(),
]);

$app = new App($container);

$app->get("/health", DbHealthCheck::class);

$app->get("/notes/stream", SseStream::class);
$app->get("/notes", ListNotes::class);
$app->post("/notes", JsonBodyMiddleware::class, CreateNote::class);
$app->get("/notes/{id}", ShowNote::class);
$app->put("/notes/{id}", JsonBodyMiddleware::class, UpdateNote::class);
$app->delete("/notes/{id}", DeleteNote::class);
$app->get("/notes/{id}/related", RelatedNotes::class);

$app->get(
    "/",
    static fn() => React\Http\Message\Response::html(file_get_contents(__DIR__ . "/index.html")),
);

$app->get("/{path:.*}", new FilesystemHandler(__DIR__));

$app->run();
