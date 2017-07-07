#!/bin/bash

# verify ok! 所有进程down了 包括 swoole_process 进程

cat /tmp/rpcmaster.pid|xargs kill