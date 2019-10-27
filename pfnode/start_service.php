<?php
use Workerman\Worker;
require_once __DIR__ . '/vendor/autoload.php';
$service_worker = new Worker();
$service_worker->name = 'Forword Serice Main';
$service_worker->reloadable = false;
if(!@$GlobalProxyService){
	$GlobalProxyService = array();
}
$service_worker->onWorkerStart = function(){
	global $GlobalProxyService;
    foreach(glob(__DIR__.'/forward_service/*.php') as $start_filet){
	   require $start_filet;
    }
};
$service_worker->onWorkerReload = function($worker){
	global $GlobalProxyService;
	$autoreload_tmp_file_info_main = json_decode(@file_get_contents(__DIR__.'/autoreload.tmp'),true);
    if(@$autoreload_tmp_file_info_main['reloadfile']){
	    $reloadfile = $autoreload_tmp_file_info_main['reloadfile'];
		foreach($reloadfile as $_reloadfile){
			$_reloadfile_name = pathinfo($_reloadfile, PATHINFO_BASENAME);
			$_reloadfile_name = @(explode('.',$_reloadfile_name))[0];
			if(@$GlobalProxyService[$_reloadfile_name]){
				echo $_reloadfile_name.'STOP!!!'.PHP_EOL;
				($GlobalProxyService[$_reloadfile_name]['worker'])->stop();
				unset($GlobalProxyService[$_reloadfile_name]);
			}
			if(file_exists($_reloadfile)){
				require $_reloadfile;
				echo $_reloadfile.'START!!!'.PHP_EOL;
			}
		}
    }
	@unlink(__DIR__.'/autoreload.tmp');
};