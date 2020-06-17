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

/**
 * Loads and reads config file.
 */
class Config
{
    /**
     * @var array
     */
    private static $config = array(
        'debug' => false,
        'mode' => 'development',
        'handlers' => array(),
        'function_filter' => array(),
        'additional_data' => array(),
        'fastcgi_finish_request' => true,
        'profiler_enable' => null,
        'profiler_simple_url' => null,
        'profiler_replace_url' => null,
        'date_format' => 'M jS H:i:s',
        'detail_count' => 6,
        'rows_per_page' => 25,
        'globals_key' => 'profiler',
    );

    /**
     * Load a config file, it will merge config to current set.
     *
     * @param string $file
     * @return void
     */
    public static function load($file)
    {
        $config = include($file);

        // handlers are an array and require special handling
        $currentHandlers = self::$config['handlers'];
        if (!empty($config['handlers'])) {
            $newHandlers = $config['handlers'];
        }
        unset(self::$config['handlers'], $config['handlers']);

        self::$config = \array_merge(self::$config, $config);
        if (!empty($newHandlers)) {
            self::$config['handlers'] = \array_merge($currentHandlers, $newHandlers);
        } else {
            self::$config['handlers'] = $currentHandlers;
        }
    }

    /**
     * Read a config value.
     *
     * @param string $name The name of the config variable
     * @param null $default
     * @return mixed value or default.
     */
    public static function read($name, $default = null)
    {
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }
        return $default;
    }

    /**
     * Get all the configuration options.
     *
     * @return array
     */
    public static function all()
    {
        return self::$config;
    }

    /**
     * Write a config value. It will not be preserved to the disk.
     *
     * @param string $name The name of the config variable
     * @param mixed $value The value of the config variable
     * @return void
     */
    public static function write($name, $value)
    {
        self::$config[$name] = $value;
    }

    /**
     * Clear out the data stored in the config class.
     *
     * @return void
     */
    public static function clear()
    {
        self::$config = array();
    }

    /**
     * Called during profiler initialization
     *
     * Allows arbitrary conditions to be added configuring how
     * PhpPerfTools profiles runs.
     *
     * @return boolean
     */
    public static function shouldRun()
    {
        $callback = self::read('profiler_enable');
        if (!is_callable($callback)) {
            return false;
        }
        return (bool)$callback();
    }

    /**
     * Get handler by name or id
     *
     * @param $nameOrId
     * @return array
     */
    public static function getHandler($nameOrId)
    {
        $all = self::read('handlers');
        $foundHandler = array();
        foreach($all as $handler) {
            if (!empty($handler['id']) && $handler['id'] == $nameOrId) {
                $foundHandler = $handler;
                break;
            }

            if (!empty($handler['name']) && $handler['name'] == $nameOrId) {
                $foundHandler = $handler;
                break;
            }
        }

        // make sure that handler has id and type column set!!
        if (empty($foundHandler['id']) || empty($foundHandler['type'])) {
            throw new \RuntimeException('Invalid configuration. Handler must have id and type.');
        }

        return $foundHandler;
    }

    /**
     * @return array|mixed
     */
    public static function getCurrentHandlerConfig()
    {
        if (defined('PHPPERFTOOLS_DEFAULT_HANDLER')) {
            return self::getHandler(PHPPERFTOOLS_DEFAULT_HANDLER);
        }

        if (!empty($_ENV['PHPPERFTOOLS_DEFAULT_HANDLER'])) {
            return self::getHandler($_ENV['PHPPERFTOOLS_DEFAULT_HANDLER']);
        }

        if (\getenv('PHPPERFTOOLS_DEFAULT_HANDLER') !== false) {
            return self::getHandler(\getenv('PHPPERFTOOLS_DEFAULT_HANDLER'));
        }

        if (empty($_REQUEST['handler'])) {
            $handlers = self::read('handlers');
            return $handlers[0];
        }

        return self::getHandler($_REQUEST['handler']);
    }
}
