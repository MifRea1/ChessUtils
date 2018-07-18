<?php
declare(strict_types = 1);
namespace ChessUtils;

const WHITELIST_COUNTRY_CODES = ['RUS'];
const OLD_TITLES = [
    '' => '',
    'CM' => 'c',
    'FM' => 'f',
    'IM' => 'm',
    'GM' => 'g',
    'WCM' => 'wc',
    'WFM' => 'wf',
    'WIM' => 'wm',
    'WGM' => 'wg'
];

function getPlayerLines(string $path, string $filename): array {
    $textFile = join (DIRECTORY_SEPARATOR, [$path, $filename . '.txt']);
    if (file_exists($textFile)) {
        return file($textFile, FILE_IGNORE_NEW_LINES);
    }
    $zipFile = join (DIRECTORY_SEPARATOR, [$path, $filename . '.zip']);
    if (!file_exists($zipFile)) {
        echo join (' ', ['File', $zipFile, 'was not found.']), PHP_EOL;
        exit;
    }
    $archive = zip_open($zipFile);
    $file = zip_read($archive);
    zip_entry_open($archive, $file);
    $text = zip_entry_read($file, zip_entry_filesize($file));
    return explode(PHP_EOL, $text);
}

function filterByCountyCodes(array $players, $isListNew = false, array $codes = WHITELIST_COUNTRY_CODES): array {
    return array_filter($players, function($line) use ($codes, $isListNew) {
        return !empty($line) && in_array(getFederation($line, $isListNew), $codes);
    });
}

function formatToOld($line): string {
    $id = getId($line);
    $name = getName($line);
    $title = convertToOldTitle(getTitle($line));
    $federation = getFederation($line, true);
    $rating = getRating($line);
    $games = getGames($line);
    $birthYear = getBirthYear($line);
    if ($birthYear === 0) {
        $birthYear = '';
    }
    $flag = getFlags($line);
    return sprintf('%9s %-34s%-4s%-5s%-6s%3s  %-6s%-4s', $id, $name, $title, $federation, $rating, $games, $birthYear, $flag);
}

function getFederation($line, $isListNew = false): string {
    return cut($line, $isListNew ? 76 : 48, 3);
}

function getId($line): int {
    return cut($line, 0, 9, true);
}

function getName($line): string {
    return cut($line, 15, 34);
}

function convertToOldTitle($title): string {
    return OLD_TITLES[$title];
}

function getTitle($line): string {
    return cut($line, 84, 3);
}

function getRating($line): int {
    return cut($line, 113, 4, true);
}

function getGames($line): int {
    return cut($line, 119, 2, true);
}

function getBirthYear($line): int {
    return cut($line, 126, 4, true);
}

function getFlags($line): string {
    return cut($line, 132, 2);
}

function cut(string $string, int $from = 0, int $length = null, bool $isInteger = false) {
    $result = substr($string, $from, $length);
    return $isInteger ? intval($result) : rtrim($result);
}
