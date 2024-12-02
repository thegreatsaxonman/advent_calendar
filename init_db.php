<?php
$db = new SQLite3('/var/www/html/advent_calendar.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$db->enableExceptions(true);

if (!$db) {
    throw new Exception("Error while creating database.");
}
    
$res = $db->query(
    'CREATE TABLE IF NOT EXISTS "collection"
    (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "day" VARCHAR NOT NULL UNIQUE,
    "file_location" VARCHAR NOT NULL UNIQUE
    )'
    );

if (!$res) {
    throw new Exception("Error executing creation of table");
}
    
$db->close();

$res = shell_exec("chmod 777 /var/www/html/advent_calendar.sqlite");
if ($res === false){
    echo("There was an error setting file permissions for sqlite.");
}

echo(true);
?>