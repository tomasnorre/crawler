<?php

declare(strict_types=1);

/*
 * Runs SQL against the database of a TYPO3 classic mode installation, used by Build/Scripts/classicModeSetup.sh
 * and by the acceptance tests when they run against that installation.
 *
 * Usage: php Build/Scripts/classicModeSql.php <public directory> [--import|--eval <PHP code>]
 *   (default)  Runs the SQL statement from stdin and prints the rows tab separated, without column names
 *   --import   Runs all SQL statements from stdin, fx. a database dump
 *   --eval     Runs the PHP code with the PDO connection as $pdo
 */

if (!isset($argv[1])) {
    fwrite(STDERR, "Usage: php classicModeSql.php <public directory> [--import|--eval <PHP code>]\n");
    exit(1);
}

$settings = require $argv[1] . '/typo3conf/system/settings.php';
$connection = $settings['DB']['Connections']['Default'];

if (str_contains((string)$connection['driver'], 'sqlite')) {
    $pdo = new PDO('sqlite:' . $connection['path']);
} else {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $connection['host'],
            $connection['port'] ?? 3306,
            $connection['dbname']
        ),
        $connection['user'],
        $connection['password']
    );
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mode = $argv[2] ?? '';
if ($mode === '--eval') {
    eval($argv[3]);
    exit(0);
}

$sql = (string)stream_get_contents(STDIN);
if ($mode === '--import') {
    $pdo->exec($sql);
    exit(0);
}

$statement = $pdo->query($sql);
if ($statement->columnCount() > 0) {
    foreach ($statement->fetchAll(PDO::FETCH_NUM) as $row) {
        echo implode("\t", array_map(static fn(mixed $value): string => $value === null ? 'NULL' : (string)$value, $row)), "\n";
    }
}
