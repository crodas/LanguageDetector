<?php
/**
 * (The MIT License)
 * Copyright (c) 2014 Titus Wormer <tituswormer@gmail.com>
 *
 *  (c) @crodas
 */

function file_get_json($path)
{
    return json_decode(file_get_contents(__DIR__ . "/data/{$path}.json"), true);
}

function listLanguages()
{
    $data = [];
    foreach (file_get_json('information') as $lang) {
        if ($lang['ISO'] &&$lang['hasTXT']) {
            $lang['data'] = file_get_contents(__DIR__ . '/data/udhr-txt/udhr_' . $lang['filename']. '.txt');
            $data[] = $lang;
        }
    }

    return $data;
}

require __DIR__ . '/../vendor/autoload.php';
use LanguageDetector\Config;
use LanguageDetector\AbstractFormat;
use LanguageDetector\Learn;

ini_set('memory_limit', '1G');
mb_internal_encoding('UTF-8');

$config = new LanguageDetector\Config;
$config->useMb(true);

$c = new Learn($config);

foreach (listLanguages() as $lang) {
    $c->addSample($lang['ISO'], $lang['data']);
}

$c->addStepCallback(function($lang, $status) {
    echo "Learning {$lang}: $status\n";
});

$c->save(AbstractFormat::initFormatByPath(__DIR__ . '/datafile.json'));
$c->save(AbstractFormat::initFormatByPath(__DIR__ . '/datafile.ses'));
$c->save(AbstractFormat::initFormatByPath(__DIR__ . '/datafile.php'));
