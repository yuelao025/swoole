<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/9
 * Time: 下午9:03
 */

namespace util;


class mysql
{

    protected $db;
    protected static $instance;
    private $conf = [
        'HOST' => '127.0.0.1',
        'USER' => 'root',
        'PASSWORD' =>  '123456',
        'DB_NAME' => 'blog2'
    ];
    
    public function __construct()
    {
        $this->db =  new MysqliDb(
            $this->conf['HOST'],
            $this->conf['USER'],
            $this->conf['PASSWORD'],
            $this->conf['DB_NAME']
        );
    }

    public static function getInstance(){
        if(!isset(self::$instance)){
            self::$instance = new Mysql();
        }
        return self::$instance;
    }

    public function getDb(){
        return $this->db;
    }
}