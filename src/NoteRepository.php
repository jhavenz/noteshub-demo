<?php

declare(strict_types=1);

namespace App;

use React\Mysql\MysqlClient;
use React\Mysql\MysqlResult;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class NoteRepository
{
    public function __construct(private MysqlClient $db) {}

    /** @return PromiseInterface<list<array<string, mixed>>> */
    public function findAll(): PromiseInterface
    {
        return $this->db
            ->query("SELECT * FROM notes ORDER BY updated_at DESC")
            ->then(fn(MysqlResult $r) => $r->resultRows);
    }

    /** @return PromiseInterface<?array<string, mixed>> */
    public function findById(int $id): PromiseInterface
    {
        return $this->db
            ->query("SELECT * FROM notes WHERE id = ?", [$id])
            ->then(fn(MysqlResult $r) => $r->resultRows[0] ?? null);
    }

    /** @return PromiseInterface<array<string, mixed>> */
    public function create(string $title, string $body): PromiseInterface
    {
        return $this->db
            ->query("INSERT INTO notes (title, body) VALUES (?, ?)", [$title, $body])
            ->then(fn(MysqlResult $r) => $this->findById($r->insertId));
    }

    /** @return PromiseInterface<array<string, mixed>> */
    public function update(int $id, string $title, string $body): PromiseInterface
    {
        return $this->db
            ->query("UPDATE notes SET title = ?, body = ? WHERE id = ?", [$title, $body, $id])
            ->then(fn() => $this->findById($id));
    }

    /** @return PromiseInterface<bool> */
    public function delete(int $id): PromiseInterface
    {
        return $this->db
            ->query("DELETE FROM notes WHERE id = ?", [$id])
            ->then(fn(MysqlResult $r) => $r->affectedRows > 0);
    }

    /** @return PromiseInterface<list<array<string, mixed>>> */
    public function getRelatedByFts(int $noteId, int $limit = 5): PromiseInterface
    {
        return $this->findById($noteId)->then(function (?array $source) use ($noteId, $limit) {
            if (empty($source)) {
                return resolve([]);
            }

            $phrase = $source["title"] . " " . $source["body"];

            return $this->db
                ->query(
                    'SELECT id, title, body, created_at, updated_at,
                        MATCH(title, body) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
                 FROM notes
                 WHERE id != ?
                   AND MATCH(title, body) AGAINST(? IN NATURAL LANGUAGE MODE) > 0
                 ORDER BY relevance DESC
                 LIMIT ?',
                    [$phrase, $noteId, $phrase, $limit],
                )
                ->then(fn(MysqlResult $r) => $r->resultRows);
        });
    }
}
