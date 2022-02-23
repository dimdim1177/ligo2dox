#!/bin/bash

  dir=$(dirname $0)
  rm -rf "$dir/html"
  cd $dir
  doxygen Doxyfile
