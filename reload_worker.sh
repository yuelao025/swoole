#!/bin/bash

# 根据当前目录的pid，发送kill信号，重启task和worker  ok!
cat /tmp/rpcmanager.pid|xargs kill -USR1