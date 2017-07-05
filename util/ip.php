<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/5
 * Time: 下午5:45
 */

namespace util;

class ip
{

    /**
     * 获取当前服务器ip
     * @return string
     */
    public static function getLocalIp()
    {
        static $currentIP;

        if ($currentIP === null) {
            $serverIps = \swoole_get_local_ip();
            $patternArray = array(
                '10\.',
                '172\.1[6-9]\.',
                '172\.2[0-9]\.',
                '172\.31\.',
                '192\.168\.'
            );
            foreach ($serverIps as $serverIp) {
                // 匹配内网IP
                if (preg_match('#^' . implode('|', $patternArray) . '#', $serverIp)) {
                    $currentIP = $serverIp;
                    return $currentIP;
                }
            }
        } else {
            return $currentIP;
        }

    }
}