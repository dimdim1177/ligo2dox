#!/usr/bin/php
<?php
    $filename = $argv[1];
    $source = file_get_contents($filename);// исходный файл для документирования
    $source = preg_replace('/module\s+([_a-zA-Z0-9]+)\s+is/u', 'class \1', $source);
    $source = preg_replace('/\/\/[A-Z]{2}/u', '///', $source);
    $source = preg_replace('/\(\*(([^*)]*|\*[^)])*)\*\)/u', '/*\1*/', $source);
    $source = preg_replace('/type\s+([_a-zA-Z0-9]+)\s+is\s+(?:\[[^]]+\]\s*)*record\s*\[([^]]*)\]/u', 'typedef struct \1 { \2 };', $source);
    $source = preg_replace('/type\s+([_a-zA-Z0-9]+)\s+is\s+record\s*\[([^]]*)\]/u', 'typedef struct \1 { \2 };', $source);
    echo $source;
