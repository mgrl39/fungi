<?php

$locale = 'es_ES.UTF-8';
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain('messages', __DIR__ . '/../locale');
textdomain('messages');
var_dump("i18n")

?>