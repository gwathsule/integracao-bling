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

    protected function insert(SQLite3 $db, string $table, array $data)
    {
        $columns = implode(',', array_keys($data));

        $values = array_map(function($value) {
            if(gettype($value) == "string") {
                return "'$value'";
            }
            if(is_null($value)) {
                return "null";
            }
            return $value;
        }, $data);

        $values = implode(', ', $values);
        $query = "INSERT INTO $table($columns) VALUES ($values)";

        try {
            $db->exec($query);
        } catch (Exception) {
            throw new Exception('Error trying insert on database');
        }
    }

    protected function getById(SQLite3 $db, string $table, int $id): ?array
    {
        try {
            $stm = $db->prepare("SELECT * FROM $table WHERE id = ?");
            $stm->bindValue(1, $id, SQLITE3_INTEGER);
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

    public function deleteById(SQLite3 $db, string $table,  int $id)
    {
        return $db->exec("DELETE FROM $table WHERE id = $id");
    }

    public function updateById(SQLite3 $db, string $table, array $newAttributes, int $id)
    {
        $values = array_map(function($value, $index) {
            if(gettype($value) == "string") {
                return "$index = '$value'";
            }
            if(is_null($value)) {
                return "$index = null";
            }
            return "$index = $value";
        }, $newAttributes, array_keys($newAttributes));

        $values = implode(', ', $values);
        $query = "UPDATE $table SET $values WHERE id = $id";

        try {
            $db->exec($query);
        } catch (Exception) {
            throw new Exception('Error trying update on database');
        }
    }

    public function filter(SQLite3 $db, string $table, array $filters): array
    {
        try {
            $filters = array_filter($filters);
            $binds = array_map(fn($index) => !is_null($filters[$index]) ? "$index = ?" : "", array_keys($filters));
            $binds = implode(' AND ', $binds);

            $stm = $db->prepare("SELECT * FROM $table WHERE ($binds)");

            $param = 1;
            foreach ($filters as $filter) {
                $stm->bindValue($param, $filter);
                $param++;
            }

            $res = $stm->execute();
        } catch (Exception) {
            throw new Exception('Error trying recover on database');
        }

        $columns = [];

        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            array_push($columns, $row);
        }
        return $columns;
    }
}