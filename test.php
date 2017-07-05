<?php


$server = new swoole_http_server('0.0.0.0', 8009);

$server->on('request', function(swoole_http_request $request, swoole_http_response $response){

	if( $request->server['request_method'] == 'GET' && isset($request->get)) {
        $params = $request->get;
    } else {
        $params = [];
    }


    $params = [];
    if($request->server['request_method'] == 'POST') {
        if( $request->header['content-type'] == 'application/json'){
        	echo 55;

        	// var_dump($request->rawContent());
            $params = json_decode($request->rawContent(), true);
        }
        elseif($request->header['content-type'] == 'application/x-www-form-urlencoded'){
        	echo 44;
			$params = isset($request->post)?$request->post:[];
        }

        
    }


    var_dump($params);

    $file = __DIR__ . $request->server['path_info'];
    var_dump($file);
    if( is_file($file) ) {
        // $body = include $file;
        // var_dump($body);
        $body = file_get_contents($file);
        var_dump("body:".$body);
    } else {
        $body = "<html><h1>Hello Swoole!</h1></html>";
    }
    $response->end($body);
});
$server->start();



