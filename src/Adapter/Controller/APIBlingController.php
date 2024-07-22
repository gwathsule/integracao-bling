<?php

namespace Src\Adapter\Controller;

use Src\Adapter\Repository\Sqlite\SqliteRepository;
use Klein\Request;
use Klein\Response;
use Throwable;

class APIBlingController
{
    public function __construct(
        private SqliteRepository $repository = new SqliteRepository()
    ) {
    }

    public function handle(Request $request, Response $response): Response
    {
        try {

            return $response->json(["resultado"]);

        } catch (Throwable $exception) {
            return $response
                ->code(500)
                ->json(['message' => 'Internal server error.']);
        }
    }
}