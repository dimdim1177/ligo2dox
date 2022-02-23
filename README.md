## Convert LIGO code for auto-documenting by Doxygen

The script is designed for preprocessing LIGO code project to C++ like code, in order to Doxygen recognize in it variables, functions, classes.
Developed and testing under Debian 11 / PHP 7.4.

### Usage

First time, you must install Linux and PHP7 :) Then, clone repo and use `ligo2dox.php` as binary, or you can call it by `php ligo2dox.php`.
```
ligo2dox.php FILENAME|-, where
FILENAME - path to *.ligo file for patching, '-' - use stdin
Output always to stdout
```
For example:

`ligo2dox.php contract.ligo`

### How it works

In a nutshell:
- First time from LIGO code extracted comments and preprocessor instructions and saved in memory of script.
- Then LIGO style code transcoding to something as C++ like code, in order to Doxygen recognize in it variables, functions, classes.
- Then early extracted comments and preprocessor instructions return to code, and this result put to Doxygen.

During all these recodings, the script tries as much as possible to preserve the position of entities and comments in lines, because Doxigen is attached to lines of code.

### Possible problems 

Of course, the script, when analyzed, relies on the syntactically correct LIGO code. That is why, as you can see by algorithm above, some constructions of preprocessor instructions will fails.
For example:
```
#if ENABLED
function someWhenEnabled(const x: nat; const y: nat): nat {
#else
function someWhenDisabled(const x: nat): nat {
#endif
```
This construction will fail, because after remove preprocessor lines for patch code this block will be:
```

function someWhenEnabled(const x: nat; const y: nat): nat {

function someWhenDisabled(const x: nat): nat {

```
And this block of code will become invalid.

### 

### Examples

You must install Doxygen and PHP7 for execute scripts in examples.   

#### single-language

Demo project with auto-documenting, with Doxygen-style comments only in English.

Use doc/doc.sh for generate HTML by Doxygen

#### multi-language

Demo project with auto-documenting, with Doxygen-style comments in two languages: English and Russian.

For this project used multi-language comments script from submodule repository, that is why before use it you must update submodules by command:

`git submodule update --init --recursive`

Use doc/doc.sh for generate HTML by Doxygen 

#### quipuswap

Example of auto-documenting one of complex LIGO project from Quipuswap repository quipuswap-stable-core.

For this project used multi-language comments script from submodule repository, that is why before use it you must update submodules by command:

`git submodule update --init --recursive`   

Use doc/doc.sh for generate HTML by Doxygen

