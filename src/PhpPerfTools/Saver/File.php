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
namespace PhpPerfTools\Saver;

/**
 * File saving handler
 */
class File implements SaverInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $separateMeta;

    /**
     * @var array
     */
    private $config;

    /**
     * File saver constructor.
     *
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        if (empty($config['path'])){
            throw new \InvalidArgumentException("Missing argument.");
        }

        $this->path = rtrim($config['path'], '/\\').DIRECTORY_SEPARATOR;

        if (!\is_dir($this->path) || !\is_writable($this->path)) {
            throw new \InvalidArgumentException("Target directory does not exists or is not writable.");
        }
        $this->file = !empty($config['file']) ? $config['file'] : self::getFilename();
        $this->separateMeta = isset($config['separate_meta']) ? $config['separate_meta'] : true;

        $config['serializer']  = !empty($config['serializer']) ? $config['serializer'] : 'php';
        $config['meta_serializer']  = !empty($config['meta_serializer']) ? $config['meta_serializer'] : 'php';

        $this->config = $config;
    }

    /**
     * Save profile data to target file.
     *
     * @param array $data
     * @return bool|int
     */
    public function save(array $data)
    {
        $profiles = \PhpPerfTools\Util::getDataForStorage($data['profile'], $this->config, true);

        $meta = $data['meta'];

        $meta['summary'] = $data['profile']['main()'];
        $meta = \PhpPerfTools\Util::getDataForStorage($meta, $this->config, false);

        $this->safeWrite($this->path.$this->file.'.meta',$meta.PHP_EOL);

        return $this->safeWrite($this->path.$this->file,$profiles.PHP_EOL);
    }

    /**
     * Get filename to use to store data
     *
     * @return string
     * @throws \Exception
     */
    public static function getFilename()
    {
        $fileNamePattern = 'phpperftools.data.'.microtime(true);
        try {
            $fileNamePattern .= bin2hex(random_bytes(12));
        } catch (Exception $e) {
            $fileNamePattern .= md5(uniqid('phpperftools', true).microtime());
        }

        return $fileNamePattern;
    }

    /**
     * Write data to disk and make sure that error are handled.
     *
     * @param $fileName
     * @param $data
     * @return false|int
     */
    protected function safeWrite($fileName, $data)
    {
        $errorReportingSetting = \error_reporting();
        \error_reporting(0);

        $i = \file_put_contents($fileName, $data);
        \error_reporting($errorReportingSetting);

        // save failed (saved bytes = 0 or returned false)
        if (empty($i)) {
            \error_log("PhpProfTools - Unable to save profile information to file.", \E_USER_ERROR);
        }
        return $i;
    }
}
