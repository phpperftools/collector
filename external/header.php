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

/** @noinspection PhpComposerExtensionStubsInspection */ // ignore missing extension requirement in composer.json
/** @noinspection ForgottenDebugOutputInspection */ // ignore error_log calls
/** @noinspection PhpUndefinedFunctionInspection */ // ignore undefined function from profiling extensions
/** @noinspection PhpUndefinedConstantInspection */ // ignore undefined constants from profiling extensions
/* Things you may want to tweak in here:
 *  - xhprof_enable() uses following constants:
 *    - XHPROF_FLAGS_CPU
 *    - XHPROF_FLAGS_MEMORY
 *    - XHPROF_FLAGS_NO_BUILTINS
 *  - tideway_enable() uses following constants:
 *    - TIDEWAYS_XHPROF_FLAGS_CPU
 *    - TIDEWAYS_XHPROF_FLAGS_MEMORY
 *    - TIDEWAYS_XHPROF_FLAGS_NO_BUILTINS
 *    - TIDEWAYS_XHPROF_FLAGS_MEMORY_MU
 *    - TIDEWAYS_XHPROF_FLAGS_MEMORY_PMU
 *    - TIDEWAYS_XHPROF_FLAGS_MEMORY_ALLOC
 *    - TIDEWAYS_XHPROF_FLAGS_MEMORY_ALLOC_AS_MU
 *  - uprofiler uses following constants:
 *    - UPROFILER_FLAGS_CPU
 *    - UPROFILER_FLAGS_MEMORY
 *    - UPROFILER_FLAGS_NO_BUILTINS
 *
 * The easiest way to get going is to either include this file in your index.php script, or use php.ini's
 * auto_prepend_file directive http://php.net/manual/en/ini.core.php#ini.auto-prepend-file
 */

/* Flags:
 *
 * *_FLAGS_NO_BUILTINS
 *  Omit built in functions from return
 *  This can be useful to simplify the output, but there's some value in seeing that you've called strpos() 2000 times
 *
 * *_FLAGS_CPU
 *  Include CPU profiling information in output
 *
 * *_FLAGS_MEMORY
 *  Include Memory profiling information in output
 *
 * Additional flags for Tideway profiler:
 * TIDEWAYS_XHPROF_FLAGS_MEMORY_ALLOC
 * Add additional fields to output: total number of memory allocation operations, total number of memory freeing
 * operations, total amount of memory used in function.
 *
 * TIDEWAYS_XHPROF_FLAGS_MEMORY_MU
 * Track memory usage
 *
 * TIDEWAYS_XHPROF_FLAGS_MEMORY_PMU
 * Track peak memory usage
 *
 * TIDEWAYS_XHPROF_FLAGS_MEMORY_ALLOC_AS_MU
 * Return total number of memory used in mu field (instead of memory_get_usage() )
 *
 * TIDEWAYS_FLAGS_NO_SPANS
 * Skip timespan generation (this flag is enabled by default for PhpPerfTools)
 *
 * Additional flags for uprofiler:
 * UPROFILER_FLAGS_FUNCTION_INFO
 * Add additional info about function calls to profiles
 *
 * Use bitwise operators to combine flags, for example: XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY to profile CPU and Memory
 *
 */

// this file should not - under no circumstances - interfere with any other application
if (!extension_loaded('xhprof')
    && !extension_loaded('uprofiler')
    && !extension_loaded('tideways')
    && !extension_loaded('tideways_xhprof')
) {
    error_log('phpperftools - either extension xhprof, uprofiler, tideways or tideways_xhprof must be loaded');
    return;
}

// Use the callbacks defined in the configuration file
// to determine whether or not phpperftools should enable profiling.
//
// Only load the config class so we don't pollute the host application's
// autoloader.
$dir = dirname(__DIR__);
require_once $dir . '/src/PhpPerfTools/Config.php';
$configDir = defined('PHPPERFTOOLS_CONFIG_DIR') ? PHPPERFTOOLS_CONFIG_DIR : $dir . '/config/';
\PhpPerfTools\Config::load($configDir . 'config.default.php');
if (file_exists($configDir . 'config.php')) {
    \PhpPerfTools\Config::load($configDir . 'config.php');
}


if (!\PhpPerfTools\Config::shouldRun()) {
    return;
}
$handler = \PhpPerfTools\Config::getCurrentHandlerConfig();

//double check if required extensions are loaded
switch ($handler['type']) {
    case 'mongo':
        if ((!extension_loaded('mongo') && !extension_loaded('mongodb'))) {
            error_log('phpperftools - extension mongo not loaded');
            return;
        }
        break;

    case 'pdo':
        if ((!extension_loaded('pdo'))) {
            error_log('phpperftools - extension pdo not loaded');
            return;
        }
        break;

    case 'upload':
        if ((!extension_loaded('curl'))) {
            error_log('phpperftools - extension curl not loaded');
            return;
        }
        break;
}
unset($dir, $configDir, $handler);

if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

$options =  \PhpPerfTools\Config::read('profiler_options', array());

if (extension_loaded('uprofiler')) {
    $flags = (int) \PhpPerfTools\Config::read('profiler_flags', UPROFILER_FLAGS_CPU | UPROFILER_FLAGS_MEMORY);
    uprofiler_enable($flags, $options);

} else if (extension_loaded('tideways')) {
    $flags = (int) \PhpPerfTools\Config::read('profiler_flags', TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY | TIDEWAYS_FLAGS_NO_SPANS);
    tideways_enable($flags, $options);

} elseif (extension_loaded('tideways_xhprof')) {
    $flags = (int) \PhpPerfTools\Config::read('profiler_flags', TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);
    tideways_xhprof_enable($flags);

} else {
    if (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 4) {
        $flags = (int) \PhpPerfTools\Config::read('profiler_flags', XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_NO_BUILTINS);
        xhprof_enable($flags, $options);

    } else {
        $flags = (int) \PhpPerfTools\Config::read('profiler_flags', XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
        xhprof_enable($flags, $options);

    }
}

register_shutdown_function(
    function () {
        if (extension_loaded('uprofiler')) {
            $data['profile'] = uprofiler_disable();
        } else if (extension_loaded('tideways')) {
            $data['profile'] = tideways_disable();
        } elseif (extension_loaded('tideways_xhprof')) {
            $data['profile'] = tideways_xhprof_disable();
        } else {
            $data['profile'] = xhprof_disable();
        }

        // store session id before we close it:
        $sessionId = null;
        if (session_id() !== '') {
            $sessionId = session_id();
        }

        // ignore_user_abort(true) allows your PHP script to continue executing, even if the user has terminated their request.
        // Further Reading: http://blog.preinheimer.com/index.php?/archives/248-When-does-a-user-abort.html
        // flush() asks PHP to send any data remaining in the output buffers. This is normally done when the script completes, but
        // since we're delaying that a bit by dealing with the xhprof stuff, we'll do it now to avoid making the user wait.
        ignore_user_abort(true);
        if (function_exists('session_write_close')) {
            session_write_close();
        }
        flush();

        if (!defined('PHPPERFTOOLS_ROOT_DIR')) {
            require dirname(__DIR__) . '/src/bootstrap.php';
        }
        require dirname(__DIR__) . '/src/PhpPerfTools/Util.php';
        require dirname(__DIR__) . '/src/PhpPerfTools/Saver.php';

        if (\PhpPerfTools\Config::read('fastcgi_finish_request') && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : null;
        if (empty($uri) && isset($_SERVER['argv'])) {
            $cmd = basename($_SERVER['argv'][0]);
            $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        }

        $replace_url = \PhpPerfTools\Config::read('profiler_replace_url');
        if (is_callable($replace_url)) {
            $uri = $replace_url($uri);
        }

        $time = array_key_exists('REQUEST_TIME', $_SERVER) ? $_SERVER['REQUEST_TIME'] : time();

        // In some locales there is comma instead of dot
        // @todo verify if that is the case
        $delimiter = (strpos($_SERVER['REQUEST_TIME_FLOAT'], ',') !== false) ? ',' : '.';
        $requestTimeFloat = explode($delimiter, $_SERVER['REQUEST_TIME_FLOAT']);
        if (!isset($requestTimeFloat[1])) {
            $requestTimeFloat[1] = 0;
        }

        // @todo remove Micro
        $requestTs = array('sec' => $time, 'usec' => 0);
        $requestTsMicro = array('sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1]);

        $globalsKey = \PhpPerfTools\Config::read('globals_key', 'profiler');
        $branchFile = \PhpPerfTools\Config::read('branch_filename', '.git/HEAD');
        $detectedBranch = null;
        if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.$branchFile)) {
            $detectedBranch = file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.$branchFile);
        } elseif (file_exists(dirname($_SERVER['SCRIPT_FILENAME'], 1).DIRECTORY_SEPARATOR.$branchFile)) {
            $detectedBranch = file_get_contents(dirname($_SERVER['SCRIPT_FILENAME'],1).DIRECTORY_SEPARATOR.$branchFile);
        }
        if (!empty($detectedBranch)) {
            $detectedBranch = trim(str_replace('ref: ', '', $detectedBranch));
        }

        $profilerMetaFile = \PhpPerfTools\Config::read('profiler_info_file', 'profiler_info');
        $profilerMeta = array();
        if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.$profilerMetaFile)) {
            $profilerMeta = parse_ini_file(dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.$profilerMetaFile);
        } elseif (file_exists(dirname($_SERVER['SCRIPT_FILENAME'], 1).DIRECTORY_SEPARATOR.$profilerMetaFile)) {
            $profilerMeta = parse_ini_file(dirname($_SERVER['SCRIPT_FILENAME'],1).DIRECTORY_SEPARATOR.$profilerMetaFile);
        }

        $application = null;
        if (!empty($profilerMeta['APPLICATION'])) {
            $application = $profilerMeta['APPLICATION'];
        } elseif (!empty($GLOBALS[$globalsKey]['application'])) {
            $application = $GLOBALS[$globalsKey]['application'];
        }
        $version = null;
        if (!empty($profilerMeta['VERSION'])) {
            $version = $profilerMeta['VERSION'];
        } elseif (!empty($GLOBALS[$globalsKey]['version'])) {
            $version = $GLOBALS[$globalsKey]['version'];
        }

        $data['meta'] = array(
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_GET,
            'env' => $_ENV,
            'session_id' => $sessionId,
            'simple_url' => \PhpPerfTools\Util::simpleUrl($uri),
            'request_ts' => $requestTs,
            'request_ts_micro' => $requestTsMicro,
            'request_date' => date('Y-m-d', $time),
            'application' =>  $application,
            'version' => $version,
            'branch' => !empty($GLOBALS[$globalsKey]['branch']) ? $GLOBALS[$globalsKey]['branch'] : $detectedBranch,
            'controller' => !empty($GLOBALS[$globalsKey]['controller']) ? $GLOBALS[$globalsKey]['controller'] : null,
            'action' => !empty($GLOBALS[$globalsKey]['action']) ? $GLOBALS[$globalsKey]['action'] : null,
            'method' => \PhpPerfTools\Util::getMethod(),
            'cookies' => $_COOKIE,
        );

        // add additional information to saved profile data - for example db queries or similar
        if (\PhpPerfTools\Config::read('additional_data', false) !== false) {
            $data['meta']['additional_data'] = array();
            foreach(\PhpPerfTools\Config::read('additional_data', array()) as $globalName) {
                if (isset($GLOBALS[$globalName])) {
                    $data['meta']['additional_data'][$globalName] = $GLOBALS[$globalName];
                }
                if (isset($GLOBALS[$globalsKey][$globalName])) {
                    $data['meta']['additional_data'][$globalName] = $GLOBALS[$globalsKey][$globalName];
                }
            }
        }

        try {
            $handler = \PhpPerfTools\Config::getCurrentHandlerConfig();

            $saver = \PhpPerfTools\Saver::factory($handler);
            $saver->save($data);
        } catch (Exception $e) {
            error_log('phpperftools - ' . $e->getMessage());
        }
    }
);
