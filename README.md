# PhpPerfTools Collector

This is a small standalone module which you can use to collect and store
[XHProf][1] or [Uprofiler][2] or [Tideways][3] performance data for later usage in [GUI][4].

## Goals
 - Compatibility with PHP >= 5.3.0
 - No dependencies aside from the relevant extensions
 - Customizable and configurable so you can build your own logic on top of it

## Usage

### Profile an Application or Site

The simplest way to profile an application is to use `external/header.php`.
`external/header.php` is designed to be combined with PHP's
[auto_prepend_file][6] directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable `auto_prepend_file` per virtual
host.

With apache this would look like:

```apache
<VirtualHost *:80>
  php_admin_value auto_prepend_file "/Users/markstory/Sites/xhgui/external/header.php"
  DocumentRoot "/Users/markstory/Sites/awesome-thing/app/webroot/"
  ServerName site.localhost
</VirtualHost>
```
With Nginx in fastcgi mode you could use:

```nginx
server {
  listen 80;
  server_name site.localhost;
  root /Users/markstory/Sites/awesome-thing/app/webroot/;
  fastcgi_param PHP_VALUE "auto_prepend_file=/Users/markstory/Sites/xhgui/external/header.php";
}
```

### Profile a CLI Script

The simplest way to profile a CLI is to use `external/header.php`.
`external/header.php` is designed to be combined with PHP's
[auto_prepend_file][6] directive. You can enable `auto_prepend_file` system-wide
through `php.ini`. Alternatively, you can enable include the `header.php` at the
top of your script:

```php
<?php
require '/path/to/xhgui/external/header.php';
// Rest of script.
```

You can alternatively use the `-d` flag when running php:

```bash
php -d auto_prepend_file=/path/to/xhgui/external/header.php do_work.php
```

### Use with environment variables

* run `composer require perftools/xhgui-collector` 
* include these lines into your bootstrap file (e.g. index.php) 

```
define('XHGUI_CONFIG_DIR', PATH_TO_OWN_CONFIG);
require_once PATH_TO_YOUR_VENDOR . '/perftools/xhgui-collector/external/header.php';
```
 
* set environment variables to configure profiling behaviour

| env | description | example | default |
| ---- | ----------- | ------- | ------- |
| `PHPPERFTOOLS_PROFILING_RATIO` | the ratio of profiled requests | `PHPPERFTOOLS_PROFILING_RATIO=50` which profiles 50% of all requests | `PHPPERFTOOLS_PROFILING_RATIO=100` |
| `PHPPERFTOOLS_PROFILING` | if this env var is set with any value the profiling is enabled | `PHPPERFTOOLS_PROFILING=enabled` | it is not set per default, so no profiling will be triggered |


## System Requirements

For using the data collection classes you will need the following:

 * PHP version 5.3 or later.
 * [XHProf](http://pecl.php.net/package/xhprof),
   [Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or
   [Tideways](https://github.com/tideways/php-profiler-extension) to actually profile the data.
 * Some way to store data. Choose either:
    * [MongoDB Extension](http://pecl.php.net/package/mongo)>=1.3.0 (MongoDB PHP driver from pecl) and  `alcaeus/mongo-php-adapter` composer dependency,
    * [PDO](https://www.php.net/manual/en/book.pdo.php). This package is tested with SQLite (without native json), MySQL (with and without native json) and PostgreSQL with native json,
    * files in a directory
    * upload to GUI instance    

When in doubt, refer to [PhpPerfTools/GUI][4] repository's composer.json or this
repository's composer.json `suggests` section.


Original code Copyright 2013 Mark Story & Paul Reinheimer
Changes Copyright 2019 Grzegorz Drozd

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

 [1]:https://pecl.php.net/package/xhprof
 [2]:https://github.com/FriendsOfPHP/uprofiler
 [3]:https://github.com/tideways/php-xhprof-extension
 [4]:https://github.com/phpperftools/gui
 [5]:http://www.mongodb.org/
 [perftools/xhgui@133051f]:https://github.com/perftools/xhgui/commit/133051f0c27240adadf00eadc236be595caadcdd
 [6]:http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file
