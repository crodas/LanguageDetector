<?php

require __DIR__ . '/../lib/LanguageDetector/autoload.php';

ini_set('memory_limit', '1G');
mb_internal_encoding('UTF-8');

$config = new LanguageDetector\Config;
$config->setSortObject(new LanguageDetector\Sort\Bayes);
$config->useMb(true);

$c = new LanguageDetector\Learn($config);
$i = 0;
foreach (glob(__DIR__ . '/samples/*') as $file) {
    $c->addSample(basename($file), file_get_contents($file));
}
$c->addStepCallback(function($lang, $status) {
    echo "Learning {$lang}: $status\n";
});
$c->save(__DIR__ . '/../data/bayes/languages.php');
$c->save(__DIR__ . '/../data/bayes/languages.ses');
$c->save(__DIR__ . '/../data/bayes/languages.json');
