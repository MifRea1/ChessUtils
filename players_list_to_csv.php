<?php
declare(strict_types = 1);
namespace ChessUtils;

require_once 'func.php';

const RATING_LISTS_PATH = 'C:\Chess\Ratings';
const RATING_LIST_ZIP_FILE_NAME = 'players_list';
const WHITELIST_FILE_NAME = 'whitelist.txt';

loadWhitelistIds(WHITELIST_FILE_NAME);
$input = getInputFromZip(RATING_LIST_ZIP_FILE_NAME);
$standardLineLength = strlen(fgets($input));
$ratingTypes = ['standard', 'rapid', 'blitz'];
$ratingPositionInLine = ['standard' => 113, 'rapid' => 126, 'blitz' => 139];
$ratingListFiles = array_combine($ratingTypes, array_map(function($type) {
    $outputFile = join(DIRECTORY_SEPARATOR, [RATING_LISTS_PATH, $type . '.csv']);
    return fopen($outputFile, 'w');
}, $ratingTypes));
$ratings = [];
while ($line = fgets($input)) {
    if (isNotWhitelisted($line)) {
        continue;
    }
    // Nsakanya Chanda has so many titles that his rating shifted
    if ($shift = strlen($line) - $standardLineLength) {
        $line = substr_replace($line, '', 113, $shift);
    }
    $id = getId($line);
    $name = getName($line, true);
    $title = convertToOldTitle(getTitle($line, true));
    $federation = getFederation($line, true);
    $birthYear = cut($line, 152, 4, true);
    $flags = cut($line, 158, 2);
    foreach ($ratingTypes as $type) {
        $ratings[$type] = cut($line, $ratingPositionInLine[$type], 4, true);
        $rating = $ratings[$type] ? $ratings[$type] : $ratings['standard'];
        fwrite($ratingListFiles[$type], join(';', [$id, $name, $title, $federation, $rating, '',  $birthYear, $flags]) . "\n");
    }
}
