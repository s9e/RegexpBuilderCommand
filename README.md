### Synopsis

`build-regexp` is a command line tool that generates regular expressions that match a given set of strings.


### Installation

You can download the [latest release as a PHAR](https://github.com/s9e/RegexpBuilderCommand/releases/latest/download/build-regexp.phar), or install it as a [Composer](https://getcomposer.org/download/) dependency:

```bash
composer require s9e/regexp-builder-command
```


### Usage

Strings can be specified either directly in the command invocation or via an input file. The following shell example shows how to pass them in the command invocation as a space-separated list:
```
$ ./vendor/bin/build-regexp foo bar baz
(?:ba[rz]|foo)
```

In the following example, we create a file with each value on its own line, then we pass the name of the file via the `infile` option:
```
$ echo -e "one\ntwo\nthree" > strings.txt
$ ./vendor/bin/build-regexp --infile strings.txt
(?:one|t(?:hree|wo))
```

By default, the result is output in the terminal directly. Alternatively, it can be saved to a file specified via the `outfile` option. In the following example, we save the result to a `out.txt` file before checking its content:
```
$ ./vendor/bin/build-regexp --outfile out.txt foo bar baz
$ cat out.txt
(?:ba[rz]|foo)
```


### Presets

Several presets are available to generate regexps for different engines. They determine how the input is interpreted, and how/which characters are escaped in the output. The following presets are available:

 - `pcre` escapes non-printing characters and characters outside of low ASCII using [PCRE's escape sequences](https://www.pcre.org/current/doc/html/pcre2syntax.html#SEC3) `\xhh` and `\x{hh..}`. If the `u` flag is specified, the regexp operates on Unicode codepoints. Otherwise, it operates on bytes.
 - `java` and `re2` are functionally identical to `pcre` and always operate on Unicode codepoints.
 - `javascript` escapes non-printing characters and characters outside of low ASCII as `\xhh`, `\uhhhh`, and `\u{hhhhh}`. If the `u` flag is not present, characters outside the BMP are split into surrogate pairs.
 - `raw` does not escape any literals. If the `u` flag is specified, the regexp operates on Unicode codepoints. Otherwise, it operates on bytes and is not guaranteed to produce a UTF-8 string.

The following examples show the results of a few different presets with the Unicode characters U+1F601 and U+1F602 as input.
```
$ ./vendor/bin/build-regexp --preset pcre "😁" "😂"
\xF0\x9F\x98[\x81\x82]

$ ./vendor/bin/build-regexp --preset javascript "😁" "😂"
\uD83D[\uDE01\uDE02]

$ ./vendor/bin/build-regexp --preset pcre --flags u "😁" "😂"
[\x{1F601}\x{1F602}]

$ ./vendor/bin/build-regexp --preset javascript --flags u "😁" "😂"
[\u{1F601}\u{1F602}]
```


### Maintenance

To generate `build-regexp.phar` you'll need to [download a recent release](https://github.com/box-project/box/releases) of `box.phar` and save it to the `bin` directory, then run `composer build-phar`.


### See also

 - https://github.com/s9e/RegexpBuilder - The library that powers this tool.
 - https://github.com/devongovett/regexgen - Similar tool written in JavaScript.
