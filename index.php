
<?php

// 进程总是 2+m+n = master+manager+task worker + worker
//
$server = new swoole_http_server('0.0.0.0', 8008);

$config  = array(
    //自定义配置
    'pid_path' => '/tmp/',//dora 自定义变量，用来保存pid文件
    //'response_header' => array('Content_Type' => 'application/json; charset=utf-8'),
    'master_pid' => 'rpcmaster.pid', //dora master pid 保存文件
    'manager_pid' => 'rpcmanager.pid',//manager pid 保存文件
    'log_dump_type' => 'file',//file|logserver
    'log_path' => '/tmp/bizlog/', //业务日志 dump path


    //const MASTER_PID = './dorarpc.pid';
    //const MANAGER_PID = './dorarpcmanager.pid';
);


$server->set(array(
//    'reactor_num' => 2, //reactor thread num
    'task_worker_num' => 2,
    'worker_num' => 2,    //worker process num
//    'backlog' => 128,   //listen backlog
//    'max_request' => 50,
//    'dispatch_mode' => 1,
    'log_file' => '/tmp/sw_server.log',//swoole 系统日志，任何代码内echo都会在这里输出
));


//master
$server->on('Start', function($serv) use ($config){

        swoole_set_process_name("server master worker");

        $master_pid_path = $config['pid_path'].$config['master_pid'];
        $master_pid_data = $serv->master_pid;
        file_put_contents($master_pid_path,$master_pid_data);
});

//manager
$server->on('managerStart', function($serv) use($config){
    swoole_set_process_name("server manager worker");

    $manager_pid_path = $config['pid_path'].$config['manager_pid'];
    $manager_pid_data = $serv->manager_pid;
    file_put_contents($manager_pid_path,$manager_pid_data);

});

//worker
$server->on('WorkerStart', function($serv, $worker_id){
    //task worker
    if($worker_id >= $serv->setting['worker_num']) {
        swoole_set_process_name("server task worker");
    } else {
        //worker
        swoole_set_process_name("server  worker");
    }
});


//task finish 需要配对
$server->on('task', function($serv, $worker_id){
   echo "task";
//    swoole_timer_tick(1000, function(){
//        echo "timeout\n";
//    });
});


$server->on('finish', function($serv, $worker_id){
    echo "finish";
});


//放在外面也是 ok 的
//swoole_timer_tick(1000, function(){
//    echo "timeout\n";
//});

swoole_timer_after(1000,function (){
   echo "timer after !";
});


$server->on('request', function(swoole_http_request $request, swoole_http_response $response){
    $path_info = explode('/', $request->server['path_info']);
    if( empty($path_info) )
    {
        // 请求路径不合法, 设置为请求无效
        $response->status(400);
        $response->end("Invalid Path Info");
    }

    if($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico'){
        return $response->end();
    } 

    // 获取 模块, 控制器, 方法
    $model      = (isset($path_info[1]) && !empty($path_info[1])) ? $path_info[1] : 'home';
    $controller = (isset($path_info[2]) && !empty($path_info[2])) ? $path_info[2] : 'index';
    $method     = (isset($path_info[3]) && !empty($path_info[3])) ? $path_info[3] : 'index';

    // var_dump($model,$controller,$method);

    //放在此处是ok的；
//    swoole_timer_tick(1000, function(){
//        echo "timeout\n";
//    });


    try {
        $class_name = "\\{$model}\\{$controller}";

        $object = new $class_name();
        // var_dump($object);

        if( !method_exists($object, $method) )
        {
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
    } catch (Error $e ) {
        // 在 PHP7 中,可以增加这一句, 捕获更全面的异常
        $response->status(503);
        $response->end(var_export($e, true));
    }
});

/**
 * 自动加载函数, 根据namespace自动在指定的路径下搜索文件
 * @param $class string 带完整namespace的类名
 */
function autoLoader($class)
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


// 注册自动加载函数
spl_autoload_register('autoLoader');
$server->start();


