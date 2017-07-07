<?php

/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/7
 * Time: 下午5:40
 */
class rpcli
{
    private $tcp_config = array(
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
        'package_max_length' => 1024 * 1024 * 2,
        'open_tcp_nodelay' => 1,
        'socket_buffer_size' => 1024 * 1024 * 4,
    );

    private $host;
    private $port;


    /**
     * rpcli constructor.
     * @param $host
     * @param $port
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;

        $client = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

        $client->set($this->tcp_config);

    }








}