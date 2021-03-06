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
const MB = 2 ** 20;

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

function filterByCountyCodes(
    array $players,
    bool $isListNew = false,
    array $codes = WHITELIST_COUNTRY_CODES,
    array $whiteListPlayers = []
): array {
    return array_filter($players, function($line) use ($codes, $isListNew, $whiteListPlayers) {
        return !empty($line)
            && (in_array(getFederation($line, $isListNew), $codes) || in_array(getId($line), $whiteListPlayers));
    });
}

function formatToOld(string $line): string {
    $id = getId($line);
    $name = getName($line, true);
    $title = convertToOldTitle(getTitle($line, true));
    $federation = getFederation($line, true);
    $rating = getRating($line, true);
    $games = getGames($line);
    $birthYear = getBirthYear($line, true);
    if ($birthYear === 0) {
        $birthYear = '';
    }
    $flags = getFlags($line, true);
    return sprintf('%9s %-34s%-4s%-5s%-6s%3s  %-6s%-4s', $id, $name, $title, $federation, $rating, $games, $birthYear, $flags);
}

function getFederation(string $line, bool $isListNew = false): string {
    return cut($line, $isListNew ? 76 : 48, 3);
}

function getId(string $line): int {
    return cut($line, 0, 9, true);
}

function getName(string $line, bool $isListNew = false): string {
    return cut($line, $isListNew ? 15 : 10, 34);
}

function convertToOldTitle(string $title): string {
    return OLD_TITLES[$title];
}

function getTitle(string $line, bool $isListNew = false): string {
    return cut($line, $isListNew ? 84 : 44, 3);
}

function getRating(string $line, bool $isListNew = false): int {
    return cut($line, $isListNew ? 113 : 53, 4, true);
}

function getGames(string $line): int {
    return cut($line, 119, 2, true);
}

function getBirthYear(string $line, bool $isListNew = false): int {
    return cut($line, $isListNew ? 126 : 64, 4, true);
}

function getFlags(string $line, bool $isListNew = false): string {
    return cut($line, $isListNew ? 132 : 70, 2);
}

function cut(string $string, int $from = 0, int $length = null, bool $isInteger = false) {
    $result = substr($string, $from, $length ?? strlen($string) - $from);
    return $isInteger ? intval($result) : rtrim($result, ' ');
}

function memPeak(string $message = ''): void {
    echo round(memory_get_peak_usage() / MB), 'Mb ', $message, PHP_EOL;
}

function loadWhitelistIds(string $whitelistFileName): void {
    $whitelistFullFileName = join(DIRECTORY_SEPARATOR, [RATING_LISTS_PATH, $whitelistFileName]);
    $whitelistIds = file_exists($whitelistFullFileName) ? file($whitelistFullFileName, FILE_IGNORE_NEW_LINES) : [];
    define('WHITELIST_PLAYER_IDS', $whitelistIds);
}

function getInputFromZip(string $zipFileName) {
    $archiveFileName = join(DIRECTORY_SEPARATOR, [RATING_LISTS_PATH, $zipFileName]) . '.zip';
    if (!file_exists($archiveFileName)) {
        echo 'File ' . $archiveFileName . ' was not found.';
        exit;
    }
    $archive = zip_open($archiveFileName);
    $file = zip_read($archive);
    $fileName = zip_entry_name($file);
    return fopen('zip://' . $archiveFileName . '#' . $fileName, 'r');
}

function isNotWhitelisted(string $player): bool {
    if (in_array(getId($player), WHITELIST_PLAYER_IDS)) {
        return false;
    }
    if (in_array(getFederation($player, true), WHITELIST_COUNTRY_CODES)) {
        return false;
    }
    return true;
}
