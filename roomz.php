<?php

require_once "FreeRoomFinder.php";
if (php_sapi_name() != "cli") {
    die("<pre> <h1> Go back to the good ol' terminal, not usable outside of it at the moment. </h1> </pre>");
}


if (!array_key_exists(1, $argv)) {
    echo "Usage: php " . pathinfo(__FILE__, PATHINFO_BASENAME) . " [Room] [Day] [Hour]" . PHP_EOL;
    echo " [Room] \tFull room code or part of it. (NH,NH1,NH101 etc.)" . PHP_EOL;
    echo " [Day]\t\tOptional. Filters free rooms by day. Abbreviations are ok. (T,Tue,W,Wednesday etc.) " . PHP_EOL;
    echo " [Hour]\t\tOptional. Filters free rooms by hour. UTC+2 (Turkey timezone) hour, not lecture hours! (9,10,15 etc.) " . PHP_EOL;
    die();
}
array_walk($argv, function (&$v) {
    return $v = trim($v, "\xC2\xA0\n");
});
$rfinder = new FreeRoomFinder();
if (array_key_exists(2, $argv) && $rfinder->filterByDay($argv[2]) === false) {
    echo "- Day is not valid, continuing without using it." . PHP_EOL;
}
if (array_key_exists(3, $argv) && $rfinder->filterByHour($argv[3]) === false) {
    echo "- Hour is not valid, continuing without using it." . PHP_EOL;
}
if ($rfinder->searchRoom($argv[1]) === false) {
    echo "- Given room couldn't be found, check if it's correct." . PHP_EOL;
}
