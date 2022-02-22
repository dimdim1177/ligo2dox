#!/usr/bin/php
<?php

    class Ligo2Dox {
        //RU Файл для обработки, '-' - stdin
        //EN File for patch, '-' - stdin
        protected static $filename = '';

        protected static $saved = [];//RU Сохраненные директивы, комментарии

        public static function run(array $argv):bool {
            if (!static::parseArgs($argv)) return false;
            return static::patch();
        }

        //RU Разбор аргументов командной строки
        //EN Parse command line arguments
        protected static function parseArgs(array $argv): bool {
            $errors = [];
            for ($i = 1; $i < count($argv); ++$i) {
                $arg = $argv[$i]; $len = mb_strlen($arg);
                if (!static::$filename) {
                    if ('-' !== $arg) {
                        if (!file_exists($arg)) $errors[] = "Not found file '$arg'";
                    }
                    static::$filename = $arg;
                } else $errors[] = "Try use many files in '$arg'";
            }
            if (!static::$filename) $errors[] = "Filename is required";
            if ($errors) {
                static::stderr(implode("\n", $errors));
                static::usage($argv, true);
                return false;
            }
            return true;
        }

        //RU Вывод справки о параметрах
        //EN Print usage of script
        public static function usage($argv, bool $tostderr = false):void {
            fwrite($tostderr ? STDERR : STDOUT, implode("\n", [
                "Usage: ".basename($argv[0])." FILENAME|-, where",
                "FILENAME - path to *.ligo file for patching, '-' - use stdin",
                "Output always to stdout",
            ])."\n\n");
        }

        //EN Patch file/stdin content
        protected static function patch():bool {
            if ('-' === static::$filename) {
                $content = '';
                while (($buf = fread(STDIN, 128 * 1024 * 1024))) {
                    $content .= $buf;
                    usleep(50000);
                }
            } else $content = file_get_contents(static::$filename);
            $content = preg_replace('/(\[@[^]]+]| +block +)/u', ' ', $content);//RU Зачищаем директивы компилятора и block

            $content = static::saveDecorations($content);

            if ((preg_match('/function\s+main\s*\(/u', $content))) {
                $contract = '-' === static::$filename ? 'Contract' : preg_replace('/\.[^.]+$/', '', basename(static::$filename));
                $namespace = 'namespace '.$contract.';';
                $content = $namespace.$content;
                static::incSavedOfs(0, strlen($namespace));
            }

            $rename = '[_a-zA-Z][_a-zA-Z0-9]*';

            // module
            if (preg_match_all('/(?<=\n)\s*module\s+(?<module>'.$rename.')\s+is\s+{/u', $content, $m, PREG_OFFSET_CAPTURE)) {
                $dofs = 0;
                foreach ($m[0] as $i => $dligo) {
                    $ligo = $dligo[0]; $len = strlen($ligo); $ofs = $dligo[1] + $dofs;
                    $content = static::replace($content, $ligo, $ofs, $len, 'class '.$m['module'][$i][0].' { public:', $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                }
            }

            // type is record
            $refield = ' *(?<name>'.$rename.') *: *(?<type>[^;]+ *;)';
            if (preg_match_all('/(?<before>(?<=\n) *type +(?<type>'.$rename.') +is +record *\[)(?<fields>[^]]*)(?<close>]\s*;?)/u', $content, $m, PREG_OFFSET_CAPTURE)) {
                $dofs = 0;
                foreach ($m[0] as $i => $dligo) {
                    $rfields = $fields = $m['fields'][$i][0];
                    preg_match_all('/'.$refield.'/u', $rfields, $mv, PREG_OFFSET_CAPTURE);
                    $djofs = 0;
                    foreach($mv[0] as $j => $fdata) {
                        $jofs = $fdata[1] + $djofs;
                        $rfields = static::replace($rfields, $fdata[0], $jofs, strlen($fdata[0]), static::patchType(rtrim($mv['type'][$j][0], ';')).' '.$mv['name'][$j][0].';', $incofs);
                        $djofs += $incofs;
                    }
                    $ofs = $m['before'][$i][1] + $dofs;
                    $content = static::replace($content, $m['before'][$i][0], $ofs, strlen($m['before'][$i][0]), 'typedef struct '.$m['type'][$i][0].' {', $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                    $ofs = $m['fields'][$i][1] + $dofs;
                    $content = static::replace($content, $fields, $ofs, strlen($fields), $rfields, $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                    $ofs = $m['close'][$i][1] + $dofs;
                    $content = static::replace($content, $m['close'][$i][0], $ofs, strlen($m['close'][$i][0]), '};', $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                }
            }

            // type is
            if (preg_match_all('/(?<before>(?<=\n) *type +(?<name>'.$rename.') +is)(?<type>[^\n|;]+;?)/u', $content, $m, PREG_OFFSET_CAPTURE)) {
                $dofs = 0;
                foreach ($m[0] as $i => $dligo) {
                    $ofs = $dligo[1] + $dofs;
                    $content = static::replace($content, $dligo[0], $ofs, strlen($dligo[0]), 'typedef '.static::patchType(rtrim($m['type'][$i][0], ';')).' '.$m['name'][$i][0].';', $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                }
            }

            // type is |
            $recase = '(?<precase>(\n\s*)*\|?\s*)(?<case>[A-Z][_a-zA-Z0-9]*)(?<aftcase>\s*[^\n|;]*)';
            if (preg_match_all('/(?<before>(?<=\n) *type +(?<name>'.$rename.') +is)(?<cases>\s*('.$recase.')+)/u', $content, $m, PREG_OFFSET_CAPTURE)) {
                $dofs = 0;
                foreach ($m[0] as $i => $dligo) {
                    $rcases = $m['cases'][$i][0];
                    preg_match_all('/'.$recase.'/u', $rcases, $mv, PREG_OFFSET_CAPTURE);
                    $djofs = 0; $c = count($mv[0]);
                    foreach($mv[0] as $j => $fdata) {
                        $jofs = $fdata[1] + $djofs;
                        $precase = static::spaces($mv['precase'][$j][0]);
                        $aftcase = static::spaces($mv['aftcase'][$j][0]);
                        if (' ' === substr($aftcase, 0, 1)) $aftcase = substr($aftcase, 1);
                        else if (' ' === substr($precase, -1)) $precase = substr($precase, 0, -1);
                        $rcases = static::replace($rcases, $fdata[0], $jofs, strlen($fdata[0]), $precase.$mv['case'][$j][0].($j == ($c - 1) ? '}' : ',').$aftcase, $incofs);
                        $djofs += $incofs;
                    }
                    $ofs = $m['before'][$i][1] + $dofs;
                    $content = static::replace($content, $m['before'][$i][0], $ofs, strlen($m['before'][$i][0]), 'enum '.$m['name'][$i][0].' {', $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                    $ofs = $m['cases'][$i][1] + $dofs;
                    $content = static::replace($content, $m['cases'][$i][0], $ofs, strlen($m['cases'][$i][0]), $rcases, $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                }
            }

            // function
            $revar = '\s*(?<const>const|var)\s+(?<name>'.$rename.')\s*:\s*(?<type>[^;]+);?';
            if (preg_match_all('/\s*function\s+(?<func>'.$rename.')\s*\((?<vars>(?:'.$revar.')+)\)\s*:(?<return>.*?)is\s*(?<open>{|[^\s]+)/u', $content, $m, PREG_OFFSET_CAPTURE)) {
                $dofs = 0;
                foreach ($m[0] as $i => $dligo) {
                    $ligo = $dligo[0]; $len = strlen($dligo[0]); $ofs = $dligo[1] + $dofs;
                    preg_match_all('/'.$revar.'/u', $m['vars'][$i][0], $mv);
                    $in = [];
                    foreach($mv['name'] as $j => $name) {
                        $in[] = ('const' === ($mv['const'][$j] ?? '') ? 'const ' : '').static::patchType($mv['type'][$j]).' '.$name;
                    }
                    $func = static::patchType($m['return'][$i][0]).' '.$m['func'][$i][0].'('.implode(',', $in).') ';
                    $open = $m['open'][$i][0];
                    if ('{' !== $open) {
                        //TODO
                    }
                    $func .= $open;
                    $content = static::replace($content, $ligo, $ofs, $len, $func, $incofs);
                    if ($incofs) {
                        static::incSavedOfs($ofs, $incofs);
                        $dofs += $incofs;
                    }
                }
            }

            $content = static::restoreDecorations($content);

            echo "\n\n".$content."\n\n";

            return true;
        }

        protected static function replace(string $content, string $ligo, int $ofs, int $len, string $replace, &$incofs = null):string {
            $llines = count(explode("\n", $ligo)); $rlines = count(explode("\n", $replace));
            if ($rlines < $llines) $replace = str_repeat("\n", $llines - $rlines).$replace;//RU Сохраняем кол-во строк
            $newlen = strlen($replace);
            if ($newlen > $len) {
                $incofs = $newlen - $len;
            } else {
                $replace = str_repeat(' ', $len - $newlen).$replace;
                $incofs = 0;
            }
            $sligo = substr($content, $ofs, $len);
            if ($ligo !== $sligo) static::stderr("Replace error: '$ligo' != '$sligo'");
            //static::stderr("$incofs\n'$ligo'\n ---> \n'$replace'\n");
            $content = substr($content, 0, $ofs).$replace.substr($content, $ofs + $len);
            return $content;
        }

        protected static function patchType(string $type):string {
            $type = trim($type);
            if (preg_match('/^option\((.*)\)$/u', $type, $mt)) {
                return 'std::variant<'.static::patchType($mt[1]).'>';
            }
            $types = explode('*', $type);
            if (count($types) > 1) {
                $subtypes = [];
                foreach ($types as $subtype) {
                    $subtype =rtrim($subtype, '*');
                    $subtypes[] = static::patchType($subtype);
                }
                return 'std::tuple<'.implode(',', $subtypes).'>';
            } else {
                if (preg_match('/^(?:map|big_map)\((.*)\)$/ui', $type, $mm)) {
                    $types = explode(',', $mm[1]);
                    return 'std::map<'.static::patchType(trim($types[0], '()')).','.static::patchType(trim($types[1], '()')).'>';
                }
                if (preg_match('/^(list|set|contract)\((.*)\)$/ui', $type, $ml)) {
                    if ('list' === strtolower($ml[1])) return 'std::list<'.static::patchType($ml[2]).'>';
                    else if ('set' === strtolower($ml[1])) return 'std::set<'.static::patchType($ml[2]).'>';
                    else return 'contract<'.static::patchType($ml[2]).'>';
                }
                if (preg_match('/(?<module>[_a-zA-Z0-9]+)\.(?<type>[_a-zA-Z0-9]+)/u', $type, $mm)) {
                    return $mm['module'].'::'.$mm['type'];
                }
                return $type;
            }
        }

        protected static function incSavedOfs(int $from, int $inc):void {
            foreach (static::$saved as &$save) {
                if ($save['ofs'] >= $from) $save['ofs'] += $inc;
            }
            unset($save);
        }

        //RU Извлечение, сохранение в переменную и замена пробелами такой же длины В БАЙТАХ директив, комментариев
        protected static function saveDecorations(string $content):string {
            //RU Извлекаем и заменяем пробелами такой же длины В БАЙТАХ директивы, комментарии
            $redecor[] = '(?:^|\n)#(define|if|endif|include)[^\n]*';//RU Директивы препроцессора (строка целиком)
            $redecor[] = preg_quote('//', '/').'[^\n]*(\n *'.preg_quote('//', '/').'[^\n]*)*';//RU Однострочные комментарии
            $redecor[] = preg_quote('(*', '/').'[\\S\\s]*?'.preg_quote('*)', '/');//RU Многострочные комментарии
            $redecor = '(?:'.implode('|', $redecor).')';
            static::$saved = [];
            if (preg_match_all('/'.$redecor.'/u', $content, $mc, PREG_OFFSET_CAPTURE)) {
                foreach($mc[0] as $data) {
                    $s = $data[0]; $len = strlen($s);
                    //RU Заменяем С++ многострочными комментариями
                    if (('(*' === substr($s, 0, 2)) && ('*)' === substr($s, -2))) $s = '/*'.substr($s, 2, $len - 4).'*/';
                    $ofs = $data[1];
                    static::$saved[] = [
                        's' => $s,
                        'ofs' => $ofs,
                        'len' => $len
                    ];
                    $s = static::spaces($s);
                    $content = substr($content, 0, $ofs).$s.substr($content, $ofs + $len);
                }
            }
            return $content;
        }

        //RU Все, кроме переводов строки, заменяем пробелами по кол-ву байтов
        protected static function spaces(string $s):string {
            if ('' === $s) return $s;
            $lines = explode("\n", $s);
            foreach ($lines as &$line) $line = str_repeat(' ', strlen($line));
            unset($line);
            $s = implode("\n", $lines);
            return $s;
        }

        protected static function restoreDecorations(string $content):string {
            foreach (static::$saved as $data) {
                ['s' => $s, 'ofs' => $ofs, 'len' => $len] = $data;
                $content = substr($content, 0, $ofs).$s.substr($content, $ofs + $len);
            }
            static::$saved = [];
            return $content;
        }

        protected static function stderr(string $error):void {
            fwrite(STDERR, $error."\n\n");
        }
    };

    Ligo2Dox::run($argv);

