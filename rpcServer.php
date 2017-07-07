<?php

/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 2017/7/6
 * Time: 下午4:35
 */

abstract class rpcServer
{
    private $http_config = [//    'reactor_num' => 2, //reactor thread num
        'task_worker_num' => 2,
        'worker_num' => 2,    //worker process num
//    'backlog' => 128,   //listen backlog
//    'max_request' => 50,
//    'dispatch_mode' => 1,
        'log_file' => '/tmp/sw_server.log' //swoole 系统日志，任何代码内echo都会在这里输出
    ];

    private $tcp_config = [
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,

        'package_max_length' => 2097152, // 1024 * 1024 * 2,
        'buffer_output_size' => 3145728, //1024 * 1024 * 3,
        'pipe_buffer_size' => 33554432, // 1024 * 1024 * 32,

        'open_tcp_nodelay' => 1,
        'backlog' => 3000
    ];

    private $rpc_config = [
        //自定义配置
        'pid_path' => '/tmp/',//dora 自定义变量，用来保存pid文件
        //'response_header' => array('Content_Type' => 'application/json; charset=utf-8'),
        'master_pid' => 'rpcmaster.pid', //dora master pid 保存文件
        'manager_pid' => 'rpcmanager.pid',//manager pid 保存文件
        'log_dump_type' => 'file',//file|logserver
        'log_path' => '/tmp/bizlog/', //业务日志 dump path
    ];

    private $http_port;
    private $tcp_port;
    private $host;


    private $http_server;
    private $tcp_server;


    /**
     * rpcserver constructor.
     * @param $http_port
     * @param $tcp_port
     * @param $host
     */
    public function __construct($host, $http_port, $tcp_port)
    {
        $this->http_port = $http_port;
        $this->tcp_port = $tcp_port;
        $this->host = $host;

        spl_autoload_register([$this,'autoloader']);

        $this->http_server = new swoole_http_server($host, $http_port);
        $this->tcp_server = $this->http_server->addlistener($host, $tcp_port, SWOOLE_SOCK_TCP);

        $this->http_server->on("Start", [$this, 'onStart']);
        $this->http_server->on("managerStart", [$this, 'onManagerStart']);
        $this->http_server->on("WorkerStart", [$this, 'onWorkerStart']);
        $this->http_server->on("task", [$this, 'onTask']);
        $this->http_server->on("finish", [$this, 'onFinish']);
        $this->http_server->on("request", [$this, 'onRequest']);



        $this->tcp_server->on('receive', [$this, 'onReceive']);


    }

    public function onStart(swoole_server $serv)
    {

        swoole_set_process_name("server master worker");

        $master_pid_path = $this->rpc_config['pid_path'] . $this->rpc_config['master_pid'];
        $master_pid_data = $serv->master_pid;
        file_put_contents($master_pid_path, $master_pid_data);
    }

    public function onManagerStart(swoole_server $serv)
    {
        swoole_set_process_name("server manager worker");

        $manager_pid_path = $this->rpc_config['pid_path'] . $this->rpc_config['manager_pid'];
        $manager_pid_data = $serv->manager_pid;
        file_put_contents($manager_pid_path, $manager_pid_data);
    }

    abstract public function initTask();

    public function onWorkerStart(swoole_server $serv, $worker_id)
    {
        $redis = new \redis();
        $redis->connect("127.0.0.1", 6379);

        $task_worker_id = $serv->worker_pid;

        if ($worker_id >= $serv->setting['worker_num']) {
            // task worker
            file_put_contents("debug.txt", $task_worker_id . "=>" . $worker_id . "\r\n", FILE_APPEND);
            $redis->lpush("debug_work_id", $worker_id);
            swoole_set_process_name("server task worker");

            $this->initTask($serv, $worker_id);

        } else {
            //worker
            swoole_set_process_name("server  worker");

            $data = ['info' => 'some info ...'];
            file_put_contents("debug.txt", $task_worker_id . "=>" . $worker_id . "\r\n", FILE_APPEND);
            $redis->lpush("debug_work_id", $worker_id);
//        $serv->task($data);
        }

    }

    abstract public function todo();

    public function onTask(swoole_server $serv, $worker_id)
    {
        echo "task";
        $this->todo();
    }


    public function onFinish(swoole_server $serv, $worker_id)
    {
        echo "finish";
    }

    public function onRequest(swoole_http_request $request, swoole_http_response $response)
    {

        $path_info = explode('/', $request->server['path_info']);
        if (empty($path_info)) {
            // 请求路径不合法, 设置为请求无效
            $response->status(400);
            $response->end("Invalid Path Info");
        }

        if ($request->server['path_info'] === '/favicon.ico' || $request->server['request_uri'] === '/favicon.ico') {
            return $response->end('');
        }

        // 获取 模块, 控制器, 方法
        $model = (isset($path_info[1]) && !empty($path_info[1])) ? $path_info[1] : 'home';
        $controller = (isset($path_info[2]) && !empty($path_info[2])) ? $path_info[2] : 'index';
        $method = (isset($path_info[3]) && !empty($path_info[3])) ? $path_info[3] : 'index';

        // var_dump($model,$controller,$method);

        //放在此处是ok的；
//    swoole_timer_tick(1000, function(){
//        echo "timeout\n";
//    });

//    // ok
//    swoole_timer_after(1000,function (){
//        echo "request timer after !";
//    });


        try {
            $class_name = "\\{$model}\\{$controller}";

            $object = new $class_name();
            // var_dump($object);

            if (!method_exists($object, $method)) {
                // 请求方法不存在, 抛出异常
                throw new Exception("{$method} not found in {$controller}");
            }

            $result = $object->$method($request, $response);
// var_dump($result);
            $response->status(200);
            $response->end($result);
        } catch (Exception $e) {
            // 返回异常信息
            $response->status(503);
            $response->end(var_export($e, true));
        } catch (Error $e) {
            // 在 PHP7 中,可以增加这一句, 捕获更全面的异常
            $response->status(503);
            $response->end(var_export($e, true));
        }
    }

    public function onReceive(swoole_server $server,$fd,$from_id ,$data)
    {
        echo "receive :".$data;
        $server->send($fd, "hello world");

//        $server->tick(1000, function () use ($server, $fd) {
//            $server->send($fd, "hello world");
//        });

    }


    public function start()
    {
        $this->http_server->set($this->http_config);
        $this->tcp_server->set($this->tcp_config);

        $this->newProcess();

        $this->http_server->start();
    }

    /***
     * @param $class
     */
    public function autoloader($class)
    {
        // 构建文件名, 将namespace中的 '\' 替换为文件系统的分隔符 '/'
        $baseClasspath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        // var_dump("autoLoader:".$baseClasspath);
        // 如果文件存在, 引用文件
        $classpath = __DIR__ . DIRECTORY_SEPARATOR . $baseClasspath;
        if (is_file($classpath)) {
            require "{$classpath}";
            return;
        }
    }

    /**
     * 新建进程 【可以做监控；日志；服务发现等】
     */
    public function newProcess()
    {
        echo "restart new process!\r\n";

//        $process = new \swoole_process(function (){
//        });
        //        $process_pid = $process->pid;
//        var_dump($process_pid);
//        file_put_contents("new_process_text.txt",$process_pid);
//        $process->start();

        //方式1 ok！
        $this->http_server->addProcess(new swoole_process(function (){
            //test ok!
            swoole_set_process_name("new!");
            ///记住了改进程必须一直在；否则每次都会重新拉取！！严重注意了！
            while(1)
            {

            }
        }));

    }
//    public function
    public function __destruct()
    {
        $this->http_server->shutdown();
    }


}