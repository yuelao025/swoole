<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/9
 * Time: 下午9:01
 */

namespace model;

use util\mysql;

class user
{
    public static function get()
    {
        $db = mysql::getInstance()->getDb();
        $data = $db->getOne("tbl_comment");
        return $data;
    }
}