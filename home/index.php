<?php


namespace home;

use util\test;

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

}
