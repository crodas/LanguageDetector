<?php

$data = json_decode(file_get_contents(__DIR__ . '/fixtures-franc.json'), true);

foreach ($data as $lang => $text) {
    $file = __DIR__ . "/$lang/franc.txt";
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file));
    }
    file_put_contents($file, $text);
}
