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
 * PDO handler to save profiler data
 */
class Pdo implements SaverInterface {

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * PDO constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (empty($config['dsn'])){
            throw new \InvalidArgumentException("Missing argument");
        }

        $this->connection = new \PDO(
            $config['dsn'],
            !empty($config['user']) ? $config['user'] : null,
            !empty($config['password']) ? $config['password'] : null,
            !empty($config['options']) ? $config['options'] : array()
        );
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Save data in sql database.
     *
     * @param array $data
     *
     * @return bool
     */
    public function save(array $data)
    {
        $this->connection->beginTransaction();

        try {
            $id = \PhpPerfTools\Util::getId($data);

            $requestTime = \DateTime::createFromFormat('U u', $data['meta']['request_ts_micro']['sec'].' '.$data['meta']['request_ts_micro']['usec']);

            $infoStatement = $this->connection->prepare('
insert into profiles_info(
    id, url, simple_url, request_time, method, main_ct, main_wt, main_cpu, main_mu, main_pmu, application, version, branch,controller, action, remote_addr, session_id 
) values (
    :id, :url, :simple_url, :request_time, :method, :main_ct, :main_wt, :main_cpu, :main_mu, :main_pmu, :application, :version, :branch, :controller, :action, :remote_addr, :session_id          
)');

            // get some data from meta and save in separate column to make it easier to search/filter
            $infoStatement->execute(array(
                'id' => $id,
                'url'=> $data['meta']['url'],
                'simple_url' => $data['meta']['simple_url'],
                'request_time' => $requestTime->format('Y-m-d H:i:s.u'),
                'main_ct'=> $data['profile']['main()']['ct'],
                'main_wt'=> $data['profile']['main()']['wt'],
                'main_cpu' => $data['profile']['main()']['cpu'],
                'main_mu'=> $data['profile']['main()']['mu'],
                'main_pmu' => $data['profile']['main()']['pmu'],
                'application'=> !empty($data['meta']['application']) ? $data['meta']['application'] : null,
                'version'=> !empty($data['meta']['version']) ? $data['meta']['version'] : null,
                'branch' => !empty($data['meta']['branch']) ? $data['meta']['branch'] : null,
                'controller' => !empty($data['meta']['controller']) ? $data['meta']['controller'] : null,
                'action' => !empty($data['meta']['action']) ? $data['meta']['action'] : null,
                'session_id' => !empty($data['meta']['session_id']) ? $data['meta']['session_id'] : null,
                'method' => !empty($data['meta']['method']) ? $data['meta']['method'] : 'UNKNOWN',
                'remote_addr' => !empty($data['meta']['SERVER']['REMOTE_ADDR']) ? $data['meta']['SERVER']['REMOTE_ADDR'] : null,
            ));

            $profileStatement = $this->connection->prepare('insert into profiles(profile_id, profiles) VALUES(:id, :profiles)');
            $profileStatement->execute(array(
                'id' => $id,
                'profiles' => json_encode($data['profile']),
            ));

            $metaStatement = $this->connection->prepare('insert into profiles_meta(profile_id, meta) VALUES(:id, :meta)');
            $metaStatement->execute(array(
                'id' => $id,
                'meta' => json_encode($data['meta']),
            ));

            $this->connection->commit();

            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            error_log('xhgui - ' . $e->getMessage());
        }
        return false;
    }
}
