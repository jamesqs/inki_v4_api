#!/usr/bin/env php
<?php

// Simple SQL importer that handles large files
$host = '127.0.0.1';
$username = 'inki_v4_dev';
$password = 'inki_v4_dev';
$database = 'inki_stage';
$sqlFile = __DIR__ . '/inki_stage.sql';

echo "Connecting to MySQL...\n";
$mysqli = new mysqli($host, $username, $password);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "Creating database if not exists...\n";
$mysqli->query("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($database);

// Set SQL mode to handle invalid dates
$mysqli->query("SET sql_mode = 'NO_ENGINE_SUBSTITUTION'");
$mysqli->query("SET time_zone = '+00:00'");

echo "Importing SQL file: $sqlFile\n";
echo "This may take a few minutes...\n\n";

$templine = '';
$lines = 0;
$queries = 0;
$inMultilineComment = false;

$handle = fopen($sqlFile, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $lines++;

        $trimmed = trim($line);

        // Handle multi-line comments
        if (str_contains($line, '/*')) {
            $inMultilineComment = true;
        }
        if ($inMultilineComment) {
            if (str_contains($line, '*/')) {
                $inMultilineComment = false;
            }
            continue;
        }

        // Skip comments, empty lines, and delimiter commands
        if ($trimmed == '' ||
            substr($trimmed, 0, 2) == '--' ||
            substr($trimmed, 0, 1) == '#' ||
            strtolower(substr($trimmed, 0, 9)) == 'delimiter') {
            continue;
        }

        $templine .= $line;

        if (substr(trim($line), -1, 1) == ';') {
            if (!$mysqli->query($templine)) {
                // Only show errors for important failures
                if (!str_contains($templine, 'DROP TABLE IF EXISTS')) {
                    echo "Error on query: " . substr($templine, 0, 100) . "...\n";
                    echo "MySQL Error: " . $mysqli->error . "\n";
                }
            }
            $templine = '';
            $queries++;

            if ($queries % 1000 == 0) {
                echo "Processed $queries queries ($lines lines)...\n";
            }
        }
    }
    fclose($handle);
}

echo "\n✅ Import complete!\n";
echo "Lines processed: $lines\n";
echo "Queries executed: $queries\n";

$mysqli->close();