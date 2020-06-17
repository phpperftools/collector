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
 * Upload handler
 */
class Upload implements SaverInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * \PhpPerfTools\Saver_Upload constructor.
     *
     * @param $uri
     * @param $timeout
     */
    public function __construct($config)
    {
        if (empty($config['uri'])){
            throw new \InvalidArgumentException("Missing argument");
        }
        
        if (!isset($config['timeout']) || !\is_numeric($config['timeout'])) {
            $config['timeout'] = 3;
        }
        
        $this->uri = $config['uri'];
        $this->timeout = $config['timeout'];
    }

    /**
     * @param array $data
     *
     * @return mixed|void
     */
    public function save(array $data)
    {
        $json = json_encode($data);

        $ch = curl_init($this->uri);

        $headers = array(
            'Accept: application/json',         // Prefer to receive JSON back
            'Content-Type: application/json'    // The sent data is JSON
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        curl_exec($ch);

        curl_close($ch);
    }
}
