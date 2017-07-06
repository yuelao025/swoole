<?php

//nc -l -p  9501

class Client
{
    private $client;
    public function __construct() {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);
    }
    public function connect() {
        if( !$this->client->connect("127.0.0.1", 8080 , 1) ) {
            echo "Error: {$this->client->errCode}\n";
            exit;
        }

        $msg_normal = "This is a Msg test!\r\n";
        // $msg_eof = "This is a Msg\r\n";
        $msg_length = pack("N" , strlen($msg_normal) ). $msg_normal;
        $i = 0;
        while( $i < 10 ) {
            $this->client->send( $msg_length );
            $i ++;
        }
    }
}
$client = new Client();
$client->connect();

