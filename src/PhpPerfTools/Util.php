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
 * Common utilities
 */
class Util
{
    /**
     * Creates a simplified URL given a standard URL.
     * Does the following transformations:
     *
     * - Remove numeric values after =.
     *
     * @param string $url
     * @return string
     */
    public static function simpleUrl($url)
    {
        $callable = Config::read('profiler_simple_url');
        if (is_callable($callable)) {
            return call_user_func($callable, $url);
        }
        return preg_replace('/\=\d+/', '', $url);
    }

    /**
     * Serialize data for storage
     *
     * @param      $data
     * @param $handlerConfig
     * @param bool $profiles
     *
     * @return false|string
     */
    public static function getDataForStorage($data, $handlerConfig, $profiles = true)
    {
        if ($profiles) {
            $serializer = $handlerConfig['serializer'];
        } else {
            $serializer = $handlerConfig['meta_serializer'];
        }

        switch ($serializer) {
            case 'json':
                return json_encode($data);
                break;

            case 'igbinary_serialize':
            case 'igbinary_unserialize':
            case 'igbinary':
                return igbinary_serialize($data);
                break;

            case 'php':
            case 'var_export':
                return "<?php \n".var_export($data, true);
                break;
        }
    }

    /**
     * Get id for a record.
     *
     * By default this method will try to re-use request id from http server.
     * This is needed for some storage engines that don't have string/hashlike id generation.
     *
     * @todo add additional suffix for request id when profiling sub-processes
     *
     * @param array $data
     * @param bool $useRequestId
     *
     * @return string
     */
    public static function getId(array $data = array(), $useRequestId = true)
    {

        // in some cases, like during import, we might already have id
        if (!empty($data['id'])) {
            return $data['id'];
        }

        // mongo compatibility
        if (!empty($data['_id'])) {
            return $data['_id'];
        }

        if ($useRequestId) {
            foreach(array('REQUEST_ID', 'HTTP_REQUEST_ID', 'HTTP_X_REQUEST_ID', 'X_CORRELATION_ID', 'HTTP_X_CORRELATION_ID') as $header) {
                if (array_key_exists($header, $_SERVER) !== false) {
                    return $_SERVER[$header];
                }
            }
        }

        // try php 7+ function.
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                // entropy source is not available
        }
        }

        // try openssl. For purpose of this id we can ignore info if this value is strong or not
        if (function_exists('openssl_random_pseudo_bytes')) {
            /** @noinspection CryptographicallySecureRandomnessInspection */
            return bin2hex(openssl_random_pseudo_bytes(16, $strong));
        }

        // fallback to most generic method. Make sure it has 32 characters :)
        return md5(uniqid('phpperftools', true).microtime());
    }


    /**
     * @param array $data
     * @return mixed|string
     */
    public static function getMethod() {
        if(PHP_SAPI ==='cli') {
            return 'CLI';
        }
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            return $_SERVER['REQUEST_METHOD'];
        }
        return 'UNKNOWN';
    }
}
