<?php


namespace home;

use util\test;
use util\ip;

class index
{
	public function demo()
	{
		return __FILE__;
	}

	public function index()
	{
	    $info = test::demo();
	    echo $info;
		return $info." default index.action..";
	}

	public function getIp()
    {
        $ip = ip::getLocalIp();
        return $ip;
    }

}
