<?php
/*
 * Original code Copyright 2013 Mark Story & Paul Reinheimer
 * Changes Copyright Grzegorz Drozd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Default configuration for PhpPerfTools
 *
 * To change these, create a called `config.php` file in the same directory,
 * and return an array from there with your overriding settings.
 */

return array(
    // list of handlers that are used to read and save profiles
    'handlers' => array(),

    // call fastcgi_finish_request() in shutdown handler
    'fastcgi_finish_request' => false,

    // Profile x in 100 requests. (E.g. set PHPPERFTOOLS_PROFILING_RATIO=50 to
    // profile 50% of requests).
    // You can return true to profile every request.
    'profiler_enable' => function() {
        if (function_exists('xdebug_is_debugger_active') && xdebug_is_debugger_active()) {
            return false;
        }
        $ratio = getenv('PHPPERFTOOLS_PROFILING_RATIO') ?: 100;
        return (getenv('PHPPERFTOOLS_PROFILING') !== false) &&
            (mt_rand(1, 100) <= $ratio);
    },

    // reformat url to group requests. Useful when using pretty urls with form
    // params to remove everything after ? for example:
    // www.test.com/controller/action?id=1 => www.test.com/controller/action
    'profiler_simple_url' => function($url) {
        return substr($url,0, strpos($url, '?'));
    },

    // use this callback to process url and do search and replace in url. Handy
    // to remove things like csrf tokens from urls
    'profiler_replace_url' => function($url) {
        return preg_replace('/\_token\=[^\&]+/', '', $url);
    },

    // flags passed to (uprofiler|tideways|xhprof)_enable.
//    'profiler_flags' => 0,

    // options passed to profiler extension. Mainly ignored_functions list
//    'profiler_options' => array(
//        'ignored_functions' => array(),
//    ),
);
