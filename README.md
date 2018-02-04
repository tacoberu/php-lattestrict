LatteStrict
===========

[![Build Status](https://travis-ci.org/tacoberu/php-lattestrict.svg?branch=master)](https://travis-ci.org/tacoberu/php-lattestrict)

Extend of [Latte](https://latte.nette.org): amazing template engine for PHP. Designed for user templates. Removed support for php and similarly dangerous constructions.


Installation
============

The recommended way to install LatteStrict is via Composer (alternatively you can [download package](https://github.com/tacoberu/php-lattestrict/releases)):

```bash
composer require tacoberu/php-lattestrict
```


Usage
=====

```php
$engine = new Taco\LatteStrict\Engine;
$engine->setLoader(new Latte\Loaders\StringLoader);
echo $engine->renderToString($template, $args);
```


What is removed
===============

* pure php code, like `<?php ... ?>`
* global variables, classes, etc.
