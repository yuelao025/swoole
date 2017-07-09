<?php


namespace home;

use util\test;
use util\ip;
use test\climid;


class index
{

    private $config = [
        'group1' =>
            array (
                '127.0.0.1_8080' =>
                    array (
                        'ip' => '127.0.0.1',
                        'port' => 8080,
                        'updatetime' => '1482239138',
                    ),
            )
    ];


	public function demo()
	{
	    $ins = new climid($this->config);
        $ret = $ins->singleAPI("/module_a/abc", "test", 2, "127.0.0.1","8080");
//        var_dump( $ret);
	    return json_encode($ret);

	}

	public function index()
	{
	    $info = test::demo();
		return $info." default index.action..";
	}

	public function getIp()
    {
        $ip = ip::getLocalIp();
        return $ip;
    }


    public function reload()
    {
        $ins = new climid($this->config);
        $ret = $ins->singleAPI("/module_a/abc", "reload", 2, "127.0.0.1","8080");
        return json_encode($ret,JSON_UNESCAPED_UNICODE);
    }


}
