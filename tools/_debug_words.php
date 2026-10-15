<?php
$f = dirname(__DIR__) . '/docs/content/zettelkasten-note-taking-simple.html';
$h = file_get_contents($f);
$seg = substr($h, 3390, 120);
echo 'segment: [' . $seg . ']' . PHP_EOL . 'hex:' . PHP_EOL;
for ($i = 0; $i < strlen($seg); $i++) {
    $c = $seg[$i];
    $printable = (ord($c) >= 32 && ord($c) < 127) ? $c : '.';
    printf('%02x(%s) ', ord($c), $printable);
    if (($i + 1) % 16 === 0) echo PHP_EOL;
}
echo PHP_EOL;

// Which state eats everything? find first '"' after 3417? and after 3300?
$q1 = strpos($h, '"', 3417);
echo 'first " after 3417: ' . ($q1 === false ? 'NONE' : $q1) . PHP_EOL;

// strip_tags on prefix ending exactly at interesting points
foreach ([3410, 3413, 3414, 3415, 3416, 3417, 3418, 3420, 3450, 4000, 8000] as $n) {
    echo "prefix $n -> out " . strlen(strip_tags(substr($h, 0, $n)))
        . ' tail: [' . substr(strip_tags(substr($h, 0, $n)), -40) . ']' . PHP_EOL;
}
