<?php

//nc -l -p  9501

//require_once "../util/packet.php";

use util\packet;

class Client
{
    private $client;
    public function __construct() {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);

//        $this->client->set(array(
//            'open_length_check'     => 1,
//            'package_length_type'   => 'N',
//            'package_length_offset' => 0,       //第N个字节是包长度的值
//            'package_body_offset'   => 4,       //第几个字节开始计算长度
////            'package_max_length'    => 2000000,  //协议最大长度
//        ));

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
            sleep(1);
            $i ++;
            $data = $this->client->recv();
            var_dump($data);
            $data = packet::packDecode($data);
            var_dump($data);

//            $revData = packet::packDecode($data);
//            var_dump($revData);
        }
//        while (1)
        {
        }
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
//    var_dump($classpath_tmp);die;
    $classpath = $classpath_tmp.$baseClasspath;
//    var_dump($classpath);
    if (is_file($classpath)) {
        require "{$classpath}";
        return;
    }
}

spl_autoload_register('autoloader');

$client = new Client();
$client->connect();

