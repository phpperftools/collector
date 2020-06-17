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
    'handlers' => array(
        array(
            'id' => 'files',
            'name' => 'Files from data dir',
            'type' => 'file',

            'path' => '/path/to/dir',
//            'filename' => \PhpPerfTools\Saver\File::getFilename(__DIR__),
            'serializer' => 'json',
            'separate_meta' => true,
            'meta_serializer' => 'json',
        ),
        array(
            'id' => 'upload',
            'name' => 'Test Server',
            'type' => 'upload',

            'uri' => 'https://staging-server-01/import/save?handler=mysql-test',
            'timeout' => 3,
        ),
        array(
            'id' => 'mongo',
            'name' => 'Staging mongo server',
            'type' => 'mongo',

            'host' => 'mongodb://127.0.0.1:27017',
            'collection' => 'xhprof'
        ),
        array(
            'id' => 'sqlite-test',
            'name' => 'Test sqlite instance',
            'type' => 'pdo',

            'dsn' => 'sqlite:../test.sqlite',
            'user' => 'user',
            'password' => 'password'
        ),
        array(
            'id'=>'mysql-test',
            'name' => 'Test mysql instance',
            'type' => 'pdo',

            'dsn' => 'mysql:host=localhost;dbname=xhgui',
            'user' => 'root',
            'password' => 'password'
        ),
        array(
            'id' => 'postgresql-test',
            'name' => 'Test postgresql instance',
            'type' => 'pdo',

            'dsn' => 'pgsql:host=10.10.10.3;dbname=xhgui',
            'user' => 'user',
            'password' => 'password'
        ),
    ),

    'function_filter' => array(
        // 'Composer.*',
    ),
);
