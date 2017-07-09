<?php


namespace home;

use util\test;
use util\ip;
use test\climid;
use model\user;


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


	public function demo($request)
	{
	    $ins = new climid($this->config);
        $ret = $ins->singleAPI("/module_a/abc", "test", 2, "127.0.0.1","8080");
//        var_dump( $ret);
	    return json_encode($ret);

	}

	public function index($request)
	{
//	    var_dump($request);
        $post = isset($request->post) ? $request->post : array();
//        var_dump($post);
	    $info = test::demo();
		return $info." default";
	}

	public function user()
    {
        $info = user::get();    
	    return $info;
    }
    
	public function getIp($request)
    {
        $ip = ip::getLocalIp();
        return $ip;
    }


    public function reload($request)
    {
        $ins = new climid($this->config);
        $ret = $ins->singleAPI("/m/a", "reload", 1, "127.0.0.1","8080");
        return json_encode($ret,JSON_UNESCAPED_UNICODE);
    }

    public function stats($request)
    {
        $ins = new climid($this->config);
        $ret = $ins->singleAPI("/m/a", "stats", 1, "127.0.0.1","8080");
        return json_encode($ret,JSON_UNESCAPED_UNICODE);
    }


}
