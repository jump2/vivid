<?php

namespace Vivid\Server;

use Vivid\Base\Object;

class Server extends Object
{
    private $_server;

    public $ip = '0.0.0.0';

    public $port = 2541;

    public $settings = [
        'worker_num' => 1,
        'backlog' => 128,
        'max_request' => 50,
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function getServer()
    {
        if(!$this->_server) {
            $this->_server = new \swoole_http_server($this->ip, $this->port);
            $this->_server->set($this->settings);
        }
        return $this->_server;
    }

    public function run()
    {
        $this->server->on('start', [$this, 'start']);
        $this->server->on('request', [$this, 'request']);
        $this->server->on('close', [$this, 'close']);
        $this->server->on('workerStart', [$this, 'workerStart']);
        $this->server->start();
    }

    public function start($server)
    {
        echo "start\n";
    }

    public function request($request, $response)
    {
        $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
    }

    public function workerStart($server, $worker_id)
    {
        echo "workerStart{$worker_id}\n";
    }

    public function close($server, $fd, $reactorId)
    {
        echo "close\n";
    }
}