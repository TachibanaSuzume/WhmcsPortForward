<?php
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__.'/../config.php';
$GlobalProxyService[[SERID]]['client'] = new Predis\Client(['scheme' => 'tcp','host' => $RedisIP,'port' => $RedisPort,'parameters'=>['password' => $RedisPass]]);
if('[PTYPE]' == 'udp'){
    $GlobalProxyService[[SERID]]['raddress'] = 'udp://[RSIP]:[RPORT]';
}else{
    $GlobalProxyService[[SERID]]['raddress'] = 'tcp://[RSIP]:[RPORT]';
}
if('[PTYPE]' == 'udp'){
    $GlobalProxyService[[SERID]]['worker'] = new Worker('udp://0.0.0.0:[SPORT]');
}else{
    $GlobalProxyService[[SERID]]['worker'] = new Worker('tcp://0.0.0.0:[SPORT]');
}
($GlobalProxyService[[SERID]]['worker'])->name = 'Service [SERID] Forward Worker';
if('[PTYPE]' == 'udp'){
($GlobalProxyService[[SERID]]['worker'])->onConnect = function($connection)use($GlobalProxyService){
	$connection_to_r = new AsyncUdpConnection($GlobalProxyService[[SERID]]['raddress']);
    $connection_to_r->onMessage = function($connection_to_r, $buffer)use($connection,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_download',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_download'))+strlen($buffer));
        $connection->send($buffer);
    };
    $connection_to_r->onClose = function($connection_to_r)use($connection){
        $connection->close();
    };
    $connection_to_r->connect();
    $connection->onMessage = function($connection, $buffer)use($connection_to_r,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_upload',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_upload'))+strlen($buffer));
        $connection_to_r->send($buffer);
    };
    $connection->onClose = function($connection)use($connection_to_r){
        $connection_to_r->close();
    };
	$connection->onError = function($connection)use($connection_to_r){
        @$connection_to_r->close();
    };
};
}else{
($GlobalProxyService[[SERID]]['worker'])->onConnect = function($connection)use($GlobalProxyService){
	$connection_to_r = new AsyncTcpConnection($GlobalProxyService[[SERID]]['raddress']);
    $connection_to_r->onMessage = function($connection_to_r, $buffer)use($connection,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_download',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_download'))+strlen($buffer));
        $connection->send($buffer);
    };
    $connection_to_r->onClose = function($connection_to_r)use($connection){
        $connection->close();
    };
    $connection_to_r->onError = function($connection_to_r)use($connection){
        $connection->close();
    };
    $connection_to_r->connect();
    $connection->onMessage = function($connection, $buffer)use($connection_to_r,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_upload',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_upload'))+strlen($buffer));
        $connection_to_r->send($buffer);
    };
	$connection_to_r->onBufferFull = function($connection_to_r)use($connection){
        $connection->pauseRecv();
    };
    $connection->onBufferFull = function($connection)use($connection_to_r){
        $connection_to_r->pauseRecv();
    };
    $connection->onBufferDrain = function($connection)use($connection_to_r){
        $connection_to_r->resumeRecv();
    };
	$connection_to_r->onBufferDrain = function($connection_to_r)use($connection){
        $connection->resumeRecv();
    };
    $connection->onClose = function($connection)use($connection_to_r){
        $connection_to_r->close();
    };
    $connection->onError = function($connection)use($connection_to_r){
        $connection_to_r->close();
    };
};
}
/**
if(!defined('GLOBAL_START')){
    Worker::runAll();
}
**/
($GlobalProxyService[[SERID]]['worker'])->listen();
/**
$GlobalProxyService[[SERID]]['workerstop'] = false;
($GlobalProxyService[[SERID]]['worker'])->listen();
$Service_[SERID]_Timer = Timer::add(1, function()use(&$Service_[SERID]_Timer){
	global $GlobalProxyService;
	$StopService = $GlobalProxyService[[SERID]]['workerstop'];
    if($StopService){
		($GlobalProxyService[[SERID]]['worker'])->stop();
		echo 'Worker[SERID]STOP!!'.PHP_EOL;
		unset($GlobalProxyService[[SERID]]);
		Timer::del($Service_[SERID]_Timer);
	}
});
**/