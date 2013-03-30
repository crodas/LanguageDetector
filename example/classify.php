<?php
require __DIR__ . '/../lib/LanguageDetector/autoload.php';

$detect = new LanguageDetector\Detect("datafile.php");

var_dump($detect->detect("hola"));
var_dump($detect->detect("Hi there, this is a tiny text"));
var_dump($detect->detect("* This file implements in memory hash tables with insert/del/replace/find/
             * get-random-element operations. Hash tables will auto resize if needed
              * tables of power of two in size are used, collisions are handled by
               * chaining. See the source code for more information... :)"));
