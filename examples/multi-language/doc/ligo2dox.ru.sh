#!/bin/bash

    dir=$(dirname $0)
    "$dir/../../../ligo2dox.php" "$1" | "$dir/mlcomment/mlcomment.php" -l RU -n "///" -o "/**" -c "*/" dox -
