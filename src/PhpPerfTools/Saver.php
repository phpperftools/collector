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
namespace PhpPerfTools;

use PhpPerfTools\Saver\File;
use PhpPerfTools\Saver\Mongo;
use PhpPerfTools\Saver\Pdo;
use PhpPerfTools\Saver\SaverInterface;
use PhpPerfTools\Saver\Upload;

/**
 * A small factory to handle creation of the profile saver instance.
 *
 * This class only exists to handle cases where an incompatible version of pimple
 * exists in the host application.
 */
class Saver
{
    /**
     * Get a saver instance based on configuration data.
     *
     * @param array $config The configuration data.
     * @return SaverInterface
     * @throws \Exception
     */
    public static function factory($config)
    {
        require_once 'Saver/SaverInterface.php';
        switch ($config['type']) {
            case 'file':
                require_once 'Saver/File.php';
                return new File($config);

            case 'upload':
                require_once 'Saver/Upload.php';
                return new Upload($config);

            case 'pdo':
                require_once 'Saver/Pdo.php';
                return new Pdo($config);
                break;

            case 'mongo':
            default:
                require_once 'Saver/Mongo.php';
                return new Mongo($config);
        }
    }

    /**
     * For usage with factory instance - for example for easier testing
     *
     * @param $config
     * @return SaverInterface
     * @throws \Exception
     */
    public function create($config)
    {
        return self::factory($config);
    }
}
