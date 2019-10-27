<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
require_once __DIR__ . '/vendor/autoload.php';
$autoload_monitor_dir = realpath(__DIR__.'/forward_service/');
$autoload_worker = new Worker();
$autoload_worker->name = 'Forword Serice Autoloader';
$autoload_worker->reloadable = false;
$autoload_tmp_file_info_main = json_decode(@file_get_contents(__DIR__.'/autoload.tmp'),true);
if(!$autoload_tmp_file_info_main){
	$autoload_last_mtime = time();
}else{
	$autoload_last_mtime = $autoload_tmp_file_info_main['lastmtime'];
}
$autoload_last_file_list = array();
$autoload_worker->onWorkerStart = function(){
    global $autoload_monitor_dir;
    Timer::add(10, 'autoload_check_files_change', array($autoload_monitor_dir));
};
if(!function_exists('autoload_check_files_change')){
function autoload_check_files_change($monitor_dir){
    global $autoload_last_mtime,$autoload_last_file_list;
	$autoload_tmp_file_info = json_decode(@file_get_contents(__DIR__.'/autoload.tmp'),true);
    $dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator);
	$tmplist = array();
	foreach($iterator as $ListOne){
		$tmplist[] = (string)$ListOne;
	}
	if(!@$autoload_tmp_file_info['list']){
		$autoload_last_file_list = $tmplist;
	}else{
		$autoload_last_file_list = $autoload_tmp_file_info['list'];
	}
	$NeedReloadFile = array();
    foreach ($iterator as $file){
		$Keys = array_keys($autoload_last_file_list,(string)$file);
        if(pathinfo($file, PATHINFO_EXTENSION) != 'php'){
			unset($autoload_last_file_list[($Keys[0])]);
            continue;
        }
		if(empty(@$Keys[0]) && @$Keys[0] != 0){
			echo $file." update and reload#1\n";
            $NeedReloadFile[] = (string)$file;
			continue;
		}
        if($autoload_last_mtime < $file->getMTime()){
            echo $file." update and reload#2\n";
            $NeedReloadFile[] = (string)$file;
			continue;
        }
		unset($autoload_last_file_list[($Keys[0])]);
    }
	if(!empty($autoload_last_file_list)){
		foreach($autoload_last_file_list as $_autoload_last_file_list){
			if(!array_keys($NeedReloadFile,$_autoload_last_file_list)){
				$NeedReloadFile[] = $_autoload_last_file_list;
			}
		}
	}
	if(count($NeedReloadFile) > 0){
		echo "All update and reload\n";
        $autoload_last_mtime = time();
	    file_put_contents(__DIR__.'/autoload.tmp',json_encode(array('list' => $tmplist,'lastmtime' => $autoload_last_mtime)));
		file_put_contents(__DIR__.'/autoreload.tmp',json_encode(array('reloadfile' => $NeedReloadFile)));
		posix_kill(posix_getppid(), SIGUSR1);
		sleep(2);
		return ;
	}else{
		$autoload_last_mtime = time();
		$autoload_last_file_list = $tmplist;
	}
	file_put_contents(__DIR__.'/autoload.tmp',json_encode(array('list' => $autoload_last_file_list,'lastmtime' => $autoload_last_mtime)));
	if(!file_exists(__DIR__.'/autoreload.tmp')){
		file_put_contents(__DIR__.'/autoreload.tmp',json_encode(array('reloadfile' => array())));
	}
	clearstatcache();
}
}