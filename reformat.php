<?php
/*
    Take ratings lists from FIDE site
    http://ratings.fide.com/download/players_list_old.zip
    http://ratings.fide.com/download/standard_rating_list.zip
    http://ratings.fide.com/download/rapid_rating_list.zip
    http://ratings.fide.com/download/blitz_rating_list.zip

    Place them into RATING_LISTS_PATH.

    All lists will be filtered, removing all entries
    except containing specified country codes (only RUS by default).

    After that new lists will be formatted to players_list_old format
    for compatibility with Swiss Master's "FIDE list (before 2013)".
*/
declare(strict_types = 1);
namespace ChessUtils;

require_once 'func.php';

const RATING_LISTS_PATH = 'C:\Chess\Ratings';

$players = getPlayerLines(RATING_LISTS_PATH, 'players_list_old');
$playersRus = filterByCountyCodes($players);
$header = $players[0];
$oldFormatList = join(PHP_EOL, [$header, join(PHP_EOL, $playersRus), '']);
$outputFile = join (DIRECTORY_SEPARATOR, [RATING_LISTS_PATH, 'rus.txt']);
file_put_contents($outputFile, $oldFormatList);

$ratingListTypes = ['standard', 'rapid', 'blitz'];

foreach ($ratingListTypes as $type) {
    $players = getPlayerLines(RATING_LISTS_PATH, $type . '_rating_list');
    $playersRus = filterByCountyCodes($players, true);
    $playersOldFormat = array_map(__NAMESPACE__ . '\formatToOld', $playersRus);
    $oldFormatList = join(PHP_EOL, [$header, join(PHP_EOL, $playersOldFormat), '']);
    $outputFile = join(DIRECTORY_SEPARATOR, [RATING_LISTS_PATH, $type . '.txt']);
    file_put_contents($outputFile, $oldFormatList);
}
