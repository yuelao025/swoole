<?php

//nc -l -p  9501

//require_once "../util/packet.php";

use util\packet;

class Client
{
    private $client;
    public function __construct() {

//        if($this->client[])
        $this->client = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP );

    }
    public function connect() {
        if( !$this->client->connect("127.0.0.1", 8080 , 1) ) {
            echo "Error: {$this->client->errCode}\n";
            exit;
        }

        $msg_normal = "This test!";

        $msg_normal = packet::packEncode($msg_normal);
        // $msg_eof = "This is a Msg\r\n";
        //二进制形式发送 or [ok]
//        $msg_normal = pack("N" , strlen($msg_normal) ). $msg_normal;
        $i = 0;
        while( $i < 1 ) {
            $this->client->send( $msg_normal );
//            sleep(1);
            $i ++;
            $data = $this->client->recv();
            var_dump("rev: ".$data);
            $data = packet::packDecode($data);
            var_dump($data);

//            $revData = packet::packDecode($data);
//            var_dump($revData);
        }
    }


    public function send()
    {

    }
}

/***
 * @param $class
 */
function autoloader($class)
{
    // 构建文件名, 将namespace中的 '\' 替换为文件系统的分隔符 '/'
    $baseClasspath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
//     var_dump("autoLoader:".$baseClasspath);
    // 如果文件存在, 引用文件
    $classpath_tmp = (__DIR__ . DIRECTORY_SEPARATOR)."../";
//    var_dump($classpath_tmp);
    $classpath = $classpath_tmp.$baseClasspath;
    var_dump($classpath);
    if (is_file($classpath)) {
        require "{$classpath}";
        return;
    }
}

spl_autoload_register('autoloader');

$client = new Client();
$client->connect();

