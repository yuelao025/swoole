<?php


namespace  test;

use util\packet;

class climid
{

    //client obj pool
    private static $client = array();

    //for the async task list
    private static $asynclist = array();

    //for the async task result
    private static $asynresult = array();

    //client config array
    private $serverConfig = array();

    //when connect fail will block error config
    private $serverConfigBlock = array();

    //this request guid
    private $guid;

    //1 random from specify group,2 specify by ip port
    //1 随机从指定group名称内选择客户端，2 指定ip进行连接
    private $connectMode = 0;

    //是否使用上一次已连接connectclient
    //用于减少单机长链接数
    //private $connectReuse = true;

    //current connect ip port
    private $connectIp = "";
    private $connectPort = 0;

    //config group name
    private $connectGroup = "";

    //current using client obj key on static client array
    private $currentClientKey = "";

    public function __construct($serverConfig)
    {
        if (count($serverConfig) == 0) {
            echo "cant found config on the Dora RPC..";
            throw new \Exception("please set the config param on init Dora RPC", -1);
        }
        $this->serverConfig = $serverConfig;
    }

    //$param = array("type"=>1,"group"=>"group1");
    //$param = array("type"=>2,"ip"=>"127.0.0.1","port"=>9567);



    //get current client
    private function getClientObj()
    {
        //config obj key
        $key = "";

        $clientKey = "aaaa";
        $connectHost = '127.0.0.1';
        $connectPort = '8080';

        if (!isset(self::$client[$clientKey]))
        {

            $client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
            $client->set(array(
                'open_length_check' => 1,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
                'package_max_length' => 1024 * 1024 * 2,
                'open_tcp_nodelay' => 1,
                'socket_buffer_size' => 1024 * 1024 * 4,
            ));

            if (!$client->connect($connectHost, $connectPort, 5)) {
                //connect fail
                $errorCode = $client->errCode;
                if ($errorCode == 0) {
                    $msg = "connect fail.check host dns.";
                    $errorCode = -1;
                } else {
                    $msg = \socket_strerror($errorCode);
                }

                if ($key !== "") {
                    //put the fail connect config to block list
                    $this->serverConfigBlock[$this->connectGroup][$key] = 1;
                }

                throw new \Exception($msg . " " . $clientKey, $errorCode);
            }

            self::$client[$clientKey] = $client;
        }

        //success
        return self::$client[$clientKey];
    }




    /*
     * mode 参数更改说明，以前版本只是sync参数不是mode
     * sync :
     *      true 代表是否阻塞等待结果，
     *      false 下发任务成功后就返回不等待结果，用于对接口返回没有影响的操作提速
     * 改版后----
     * mode :
     *      0 代表阻塞等待任务执行完毕拿到结果 ；
     *      1 代表下发任务成功后就返回不等待结果 ；
     *      2 代表下发任务成功后直接返回guid 然后稍晚通过调用阻塞接收函数拿到所有结果
     */
    /**
     * 单api请求
     * @param  string $name api地址
     * @param  array $param 参数
     * @param  int $mode
     * @param  int $retry 通讯错误时重试次数
     * @param  string $ip 要连得ip地址，如果不指定从现有配置随机个
     * @param  string $port 要连得port地址，如果不指定从现有配置找一个
     * @return mixed  返回单个请求结果
     * @throws \Exception unknow mode type
     */
    public function singleAPI($name, $param, $retry = 0, $ip = "", $port = "")
    {
        $packet = "fuck data !";

        $sendData = Packet::packEncode($packet);

        $result = $this->doRequest($sendData);

        //retry when the send fail
        while ((!isset($result["code"]) || $result["code"] != 0) && $retry > 0) {
            $result = $this->doRequest($sendData);
            $retry--;
        }

        return $result;
    }

    /**
     * 并发请求api，使用方法如
     * $params = array(
     *  "api_1117"=>array("name"=>"apiname1",“param”=>array("id"=>1117)),
     *  "api_2"=>array("name"=>"apiname2","param"=>array("id"=>2)),
     * )
     * @param  array $params 提交参数 请指定key好方便区分对应结果，注意考虑到硬件资源有限并发请求不要超过50个
     * @param  int $mode
     * @param  int $retry 通讯错误时重试次数
     * @param  string $ip 要连得ip地址，如果不指定从现有配置随机个
     * @param  string $port 要连得port地址，如果不指定从现有配置找一个
     * @return mixed 返回指定key结果
     * @throws \Exception unknow mode type
     */
    public function multiAPI($params, $retry = 0, $ip = "", $port = "")
    {

        $packet = array(
            'api' => $params,
            'guid' => $this->guid,
        );

        $sendData = Packet::packEncode($packet);

        $result = $this->doRequest($sendData, $packet["type"]);

        //retry when the send fail
        while ((!isset($result["code"]) || $result["code"] != 0) && $retry > 0) {
            $result = $this->doRequest($sendData, $packet["type"]);
            $retry--;
        }

        if ($this->guid != $result["guid"]) {
            return Packet::packFormat($this->guid, "guid wront please retry..", 100100, $result["data"]);
        }

        return $result;
    }


    private function doRequest($sendData)
    {
        //get client obj
        try {
            $client = $this->getClientObj();
        } catch (\Exception $e) {
            $data = Packet::packFormat($this->guid, $e->getMessage(), $e->getCode());
            return $data;
        }

        $ret = $client->send($sendData);
//var_dump($ret);

        //ok fail
        if (!$ret) {
            $errorcode = $client->errCode;

            //destroy error client obj to make reconncet
            self::$client[$this->currentClientKey]->close(true);
            unset(self::$client[$this->currentClientKey]);
            // mark the current connection cannot be used, try another channel
            $this->serverConfigBlock[$this->connectGroup][$this->currentClientKey] = 1;

            if ($errorcode == 0) {
                $msg = "connect fail.check host dns.";
                $errorcode = -1;
                $packet = Packet::packFormat($this->guid, $msg, $errorcode);
            } else {
                $msg = \socket_strerror($errorcode);
                $packet = Packet::packFormat($this->guid, $msg, $errorcode);
            }

            return $packet;
        }

        //recive the response
        $data = $this->waitResult($client);
//        var_dump( $data);
        $data["guid"] = $this->guid;
        return $data;
    }

    //for the loop find the right result
    //save the async result to the asyncresult static var
    //return the right guid request
    private function waitResult(\swoole_client $client)
    {
        while (1) {

            $result = $client->recv();

            if ($result !== false && $result != "") {
                $data = Packet::packDecode($result);
                return $data;

            } else {
                //time out
                $packet = Packet::packFormat($this->guid, "the recive wrong or timeout", 100009);
                return $packet;
            }
        }
    }







}
