<?php

namespace Src\Adapter\Repository\Sqlite;

use SQLite3;
use Exception;

class BlingCustomerRepository extends SqliteRepository
{
    private const TABLE_NAME = 'bling_customers';

    public function __construct()
    {
        try {
            $db = $this->getDatabase();
            $this->createTableIfNotExists($db);
            $db->close();
        } catch (\Throwable $ex) {
            $db->close();
            throw $ex;
        }

    }

    public function insertNewBlingCustomerData($clientId, $clientSecret, $state)
    {
        try {
            $db = $this->getDatabase();
            $data = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'client_state' => $state,
                'access_token' => null,
                'refresh_token' => null,
                'token_type' => null,
                'token_expires_in' => null,
                'token_scope' => null,
            ];
            $this->insert($db, self::TABLE_NAME, $data);
            $db->close();
        } catch (\Throwable $ex) {
            $db->close();
            throw $ex;
        }
    }

    public function filterByClientState($clientState) : array
    {
        try {
            $db = $this->getDatabase();
            $data = $this->filter($db, self::TABLE_NAME, ["client_state" => $clientState]);
            $db->close();
            return $data[0];
        } catch (\Throwable $ex) {
            $db->close();
            throw $ex;
        }
    }

    public function update(array $attributes, int $id): array
    {
        try {
            $db = $this->getDatabase();
            $this->updateById($db, self::TABLE_NAME, $attributes, $id);
            $updatedData = $this->getById($db, self::TABLE_NAME, $id);
            $db->close();
            return $updatedData;
        } catch (\Throwable $ex) {
            $db->close();
            throw $ex;
        }
    }

    private function createTableIfNotExists(SQLite3 $conn)
    {
        $exec = "CREATE TABLE IF NOT EXISTS ". self::TABLE_NAME. " (".
            "id INTEGER PRIMARY KEY, ".
            "client_id TEXT, ".
            "client_secret TEXT, ".
            "client_state TEXT, ".
            "access_token TEXT, ".
            "refresh_token INTEGER, ".
            "token_type TEXT, ".
            "token_expires_in INTEGER, ".
            "token_scope TEXT".
            ");";
        $conn->exec($exec);
    }
}