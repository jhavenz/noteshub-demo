<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use React\Mysql\MysqlClient;
use React\Http\Message\Response;
use function React\Async\await;

class DbHealthCheck
{
    public function __construct(private MysqlClient $db) {}

    public function __invoke(): ResponseInterface
    {
        try {
            await($this->db->query('SELECT 1'));
            return Response::json(['status' => 'ok']);
        } catch (\Throwable) {
            return Response::json(['status' => 'error'])->withStatus(503);
        }
    }
}
