<?php

require __DIR__ . '/../lib/LanguageDetector/autoload.php';
set_time_limit(0);
ini_set('memory_limit', '1G');
mb_internal_encoding('UTF-8');

$config = new LanguageDetector\Config;
$config->useMb(true);

$c = new LanguageDetector\Learn($config);
foreach (glob(__DIR__ . '/samples/*') as $file) {
    $c->addSample(basename($file), file_get_contents($file));
}
$c->addStepCallback(function($lang, $status) {
    echo "Learning {$lang}: $status\n";
});
$c->save(__DIR__ . '/datafile.php');
$c->save(__DIR__ . '/datafile.ses');
$c->save(__DIR__ . '/datafile.json');
