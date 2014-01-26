Prescription
============

They said it couldn't be done! They said I was MAD! Well... they were right.

__Prescription__ is a very basic psuedo-templating language which provides a simple interface for php within HTML. As of right now, there are no control structures, so ifs, fors, whiles, those don't work. They may in the future, but the primary goal of this project was semantic liberation.

I wanted a templating structure that was order insensitive, and knew which functions were available prior to running. This is accomplished by checking the tags folder for updates (currently done by md5ing a join of a scandir). __Prescription__ then builds a manifest file (in JSON) of available functions and their parameters. This keeps track of the order of variables as well.

Another benefit of __Prescription__ is the support for many different data types. Rather than accepting only double quoted strings, the current build supports

* Numbers (integer and float)
* Strings (single quotes, double quotes, or encoded double quotes)
* JSON (wrapped by square or curly brackets)
* Boolean values (true / false / null)
* PHP Constants (FOO)
* PHP Variables ($foo)

Needless to say, the JSON format causes issues. This is resolved by prefixing functions with "px:" and using parenthesis, rather than curly or square brackets for tag endcaps. Angle brackets would not show up in WYSIWYG, so they were not part of the consideration.

Syntax
-------

A tag should follow one of these conventions

1. `(px:echo var="FOO")`
2. `(px:echo var="FOO" /)`
3. `(px:echo var="FOO")(/px:echo)`

Where all of these are equivalent.

In case 3, we could include text between the tag. This also gets passed to functions, if they are set up to take the parameter `$px_innerHTML`.

It should also be noted that the following two tags are syntactically equivalent.

1. `(px:divide numerator=12 denominator=13 /)`
2. `(px:divide denominator=13 numerator=12 /)`

Extending
---------

__Prescription__ was built around the idea of rapid expansion. To add a tag, simply create a file in the tag folder, give it the name of the tag you wish to call.

Example:

> tags/divide.php

and give it the function signature, naming the parameters you wish to accept, and prefixed by `px_`

Example:

> function px_divide($numerator, $denominator) {

If you wish to wrap html inside of your tag, it will be passed in through the `$px_innerHTML` parameter in your function signature. Provide a default value for it in case the user does not include anything. You can also gain access to px_tags wrapping around your tag by using the `$px_parentData` parameter in the function signature.

### Manifest Files

Manifest files are created when prescription notices a change in the folder. This is based on the file names, and nothing else. If you make changes to a function, be sure to clear the cache directory before running, to ensure that prescription rebuilds the manifest file.

### Cache Directory

The default location of the cache directory is `cache/` in the same directory as `px.php`.  If your program already has a cache folder, or you just want to change it, you can use `PX::set_cache_dir()` to change the directory.

```php
require('px.php');
PX::set_cache_dir('/full/path/to/directory');
```

## Speed Analysis

To be completed... hold your horses.

## Author

Ray Minge
