#!/bin/bash

#kill 新建的process 此处名称是new

ps -eaf |grep "new" | grep -v "grep"| awk '{print $2}'|xargs kill -9
