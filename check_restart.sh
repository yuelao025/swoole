#!/bin/bash
# 检查进程是否存在；down掉后直接重启
# */1 * * *  /bin/bash xxxx/check_restart.sh


count=`ps -fe |grep "worker" | grep -v "grep" | grep "master" | wc -l`

echo $count
if [ $count -lt 1 ]; then
ps -eaf |grep "worker" | grep -v "grep"| awk '{print $2}'|xargs kill -9
sleep 2
ulimit -c unlimited
/usr/local/php/bin/php   /swoole/swoole/customServ.php
echo "restart";
echo $(date +%Y-%m-%d_%H:%M:%S) >/data/log/restart.log
fi

