### Synopsis

`build-regexp` is a tool that generates regular expressions that match a given set of strings.


### Installation

Using [Composer](https://getcomposer.org/download/):

```bash
composer require s9e/regexp-builder-command
```


### Usage

Strings can be specified either directly in the command invocation or via an input file. The following shows how to pass them in the command invocation as a space-separated list:
```
$ ./vendor/bin/build-regexp foo bar baz
(?:ba[rz]|foo)
```

In the following, we create a file with each value on line and pass the name of the file via the `infile` option:
```
$ echo -e "one\ntwo\nthree" > strings.txt
$ ./vendor/bin/build-regexp --infile strings.txt
(?:one|t(?:hree|wo))
```

By default, the regular expression is output in the terminal but it can be saved to a file specified via the `outfile` option:
```
$ ./vendor/bin/build-regexp --outfile out.txt foo bar baz
$ cat out.txt
(?:ba[rz]|foo)
```


### See also

 - https://github.com/s9e/RegexpBuilder - The library that powers this tool.
 - https://github.com/devongovett/regexgen - Similar tool written in JavaScript.
