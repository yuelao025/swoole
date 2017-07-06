<?php

/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/6
 * Time: 下午5:10
 */

require_once "rpcServer.php";

class customServ extends rpcServer
{
    public function demo()
    {

    }
}


$serv = new customServ('0.0.0.0','8008','8080');

$serv->start();

