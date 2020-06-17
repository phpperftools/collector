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
 * Class \PhpPerfTools\Saver_Mongo
 */
class Mongo implements SaverInterface
{
    /**
     * @var MongoCollection
     */
    private $_collection;

    /**
     * @var MongoId lastProfilingId
     */
    private static $lastProfilingId;

    /**
     * \PhpPerfTools\Saver_Mongo constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (empty($config['host']) || empty($config['collection'])){
            throw new \InvalidArgumentException("Missing argument");
        }

        $options = (!empty($config['options']) ? $config['options'] : array());
        if (!empty($config['user'])) {
            $options['username'] = $config['user'];
        }
        if (!empty($config['password'])) {
            $options['password'] = $config['password'];
        }

        $mongo = new \MongoClient(
            $config['host'],
            $options
        );

        $collection = $mongo->{$config['collection']}->results;
        $collection->findOne();

        $this->_collection = $collection;
    }

    /**
     * @param array $data
     *
     * @return array|bool
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     * @throws MongoException
     */
    public function save(array $data)
    {
        if (!isset($data['_id'])) {
            $data['_id'] = self::getLastProfilingId();
        }

        if (isset($data['meta']['request_ts'])) {
            $data['meta']['request_ts'] = new \MongoDate($data['meta']['request_ts']['sec']);
        }

        if (isset($data['meta']['request_ts_micro'])) {
            $data['meta']['request_ts_micro'] = new \MongoDate(
                $data['meta']['request_ts_micro']['sec'],
                $data['meta']['request_ts_micro']['usec']
            );
        }


        return $this->_collection->insert($data, array('w' => 0));
    }

    /**
     * Return profiling ID
     * @return MongoId lastProfilingId
     */
    public static function getLastProfilingId() {
        if (!self::$lastProfilingId) {
            self::$lastProfilingId = new \MongoId();
        }
        return self::$lastProfilingId;
    }
}
