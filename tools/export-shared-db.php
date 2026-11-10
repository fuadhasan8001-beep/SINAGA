<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/export-shared-db.php <source.sqlite> <output.sql>\n");
    exit(1);
}

[$script, $source, $destination] = $argv;

if (! is_file($source)) {
    fwrite(STDERR, "SQLite source not found: {$source}\n");
    exit(1);
}

$database = new PDO('sqlite:'.$source);
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

$quote = static function (mixed $value): string {
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    $parts = preg_split('/(\r\n|\r|\n)/', (string) $value, -1, PREG_SPLIT_DELIM_CAPTURE);
    $expressions = [];
    foreach ($parts as $part) {
        $expressions[] = match ($part) {
            "\r\n" => 'char(13)||char(10)',
            "\r" => 'char(13)',
            "\n" => 'char(10)',
            default => "'".str_replace("'", "''", $part)."'",
        };
    }

    return implode('||', $expressions);
};

$handle = fopen($destination, 'wb');
if ($handle === false) {
    throw new RuntimeException("Cannot open output file: {$destination}");
}

fwrite($handle, "-- SINAGA shared content database\n");
fwrite($handle, "-- Generated from ".basename($source)."\n\n");
fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");

$objects = $database->query(
    "SELECT type, name, tbl_name, sql\n"
    . "FROM sqlite_master\n"
    . "WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%'\n"
    . "ORDER BY CASE type WHEN 'table' THEN 1 WHEN 'index' THEN 2 WHEN 'trigger' THEN 3 WHEN 'view' THEN 4 END, name"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($objects as $object) {
    if ($object['type'] !== 'table') {
        continue;
    }

    fwrite($handle, "DROP TABLE IF EXISTS \"{$object['name']}\";\n");
    fwrite($handle, $object['sql'].";\n");

    $columns = $database->query('PRAGMA table_info("'.str_replace('"', '""', $object['name']).'")')->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_map(static fn (array $column): string => '"'.$column['name'].'"', $columns);
    $rows = $database->query('SELECT * FROM "'.str_replace('"', '""', $object['name']).'"');

    while ($row = $rows->fetch(PDO::FETCH_NUM)) {
        $values = [];
        foreach ($row as $value) {
            $values[] = is_string($value) && str_contains($value, "\0")
                ? "X'".bin2hex($value)."'"
                : $quote($value);
        }

        fwrite($handle, 'INSERT INTO "'.$object['name'].'" ('.implode(', ', $columnNames).') VALUES ('.implode(', ', $values).");\n");
    }

    fwrite($handle, "\n");
}

foreach ($objects as $object) {
    if ($object['type'] !== 'table') {
        fwrite($handle, $object['sql'].";\n");
    }
}

fwrite($handle, "\nCOMMIT;\nPRAGMA foreign_keys=ON;\n");
fclose($handle);
