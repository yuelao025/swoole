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

    public static function getsku($skus)
    {
        $db = mysql::getInstance()->getDb();
        $skuStr = '';

        if($skus)
        {
            if(is_array($skus))
            {
                foreach($skus as $sku){
                    $temp = "('".$sku."')";
                    $skuStr .= $temp.",";
                }
            }
            //针对单个sku
            else{
                $temp = "('".$skus."')";
                $skuStr .= $temp.",";
            }
        }

        $newstr = substr($skuStr,0,strlen($skuStr)-1);
        $sql = 'SELECT a.* ,b.supplier_name from  sku_supplier_relation as a inner JOIN 
              sku_supplier_sync as b on a.supplier_id = b.supplier_id
            and a.sku in '. $newstr;

//        var_dump($sql);die;
        $data = $db->query($sql);

        return $data;
    }
}