#!/bin/bash

  dir=$(dirname $0)
  rm -rf "$dir/html-ru"
  rm -rf "$dir/html-en"
  cd $dir
  doxygen Doxyfile.RU
  doxygen Doxyfile.EN
