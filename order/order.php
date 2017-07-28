<?php


namespace order;

use model\user;

class order
{
	public function demo($request)
	{
		return __FILE__;
	}

	public function index($request)
	{
//	    var_dump($request->get);
        $sku = $request->get;
	    $data = user::getsku($sku);
	    echo  json_encode($data);

	}

}
