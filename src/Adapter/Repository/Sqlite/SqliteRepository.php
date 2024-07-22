<?php

namespace Src\Adapter\Repository\Sqlite;

use SQLite3;
use Exception;

class SqliteRepository
{
    protected function getDatabase(): SQLite3
    {
        return new SQLite3(getenv('SQLITE_PATH'));
    }

    protected function insert(SQLite3 $db, array $token)
    {
        $query = "INSERT INTO token(token) VALUES ($token)";

        try {
            $db->exec($query);
        } catch (Exception $ex) {
            throw new Exception('Error trying insert on database');
        }
    }

    protected function getTokens(SQLite3 $db, string $table, int $id): ?array
    {
        try {
            $stm = $db->prepare("SELECT * FROM token");
            $res = $stm->execute();
        } catch (Exception $ex) {
            throw new Exception('Error trying recover on database');
        }
        $columns = $res->fetchArray(SQLITE3_ASSOC);
        if(! $columns) {
            return null;
        }
        return $columns;
    }
}