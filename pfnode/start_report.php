<?php 
use Workerman\Worker;
use think\Db;
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__.'/config.php';
if(!function_exists('report_curl_post_https')){
function report_curl_post_https($url,$data){ 
    $url = preg_replace('/([^:])[\/\\\\]{2,}/','$1/',$url);
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}
}
$report_redis_client = new Predis\Client(['scheme' => 'tcp','host' => $RedisIP,'port' => $RedisPort,'parameters'=>['password' => $RedisPass]]);
$report_worker = new Worker();
$report_worker->count = 1;
$report_worker->name = 'Report Service';
$report_worker->onWorkerStart = function($report_worker)
{
	Db::setConfig(['type'=> 'sqlite','database'=> 'database.db','prefix'=> '','debug'=> true]);
	//流量统计上报
    \Workerman\Lib\Timer::add(30, function(){
		include __DIR__.'/config.php';
		try {
			$data = Db::table('pfinfo')->where('status','ok')->select();
        } catch (Exception $e) {
			return ;
        }
		foreach ($data as $dataone){
			$returnstatus = report_curl_post_https($websiteurl.'/?m=whmcspf&action=reporttraffic',array('serviceid' => $dataone['serviceid'],'authkey' => $WebsiteAuthkey,'updatetime' => $dataone['updatetime'],'bandwidth' => $dataone['bandwidth']));
			if(trim($returnstatus) != 'success'){
				echo '['.date("Y-m-d h:i:sa").'] '.'上报'.json_encode(array('serviceid' => $dataone['serviceid'],'updatetime' => $dataone['updatetime'],'bandwidth' => $dataone['bandwidth'])).'时失败,服务器返回'.$returnstatus.PHP_EOL;
				file_put_contents('error.log','['.date("Y-m-d h:i:sa").'] '.'上报'.json_encode(array('serviceid' => $dataone['serviceid'],'updatetime' => $dataone['updatetime'],'bandwidth' => $dataone['bandwidth'])).'时失败,服务器返回'.$returnstatus.PHP_EOL,FILE_APPEND);
			}
		}
        unset($data);
    });
	//流量实时统计
    \Workerman\Lib\Timer::add(17, function(){
		global $report_redis_client;
		include __DIR__.'/config.php';
		try {
			$data = Db::table('pfinfo')->where('status','ok')->select();
        } catch (Exception $e) {
			return ;
        }
		foreach ($data as $dataone){
			$UploadByte = @$report_redis_client->get($dataone['serviceid'].'_upload');
			@$report_redis_client->set($dataone['serviceid'].'_upload','0');
			if(!$UploadByte){
				$UploadByte = 0;
			}
			$UploadMb = round($UploadByte/1024/1024, 2);
			$DownloadByte = @$report_redis_client->get($dataone['serviceid'].'_download');
			@$report_redis_client->set($dataone['serviceid'].'_download','0');
			if(!$DownloadByte){
				$DownloadByte = 0;
			}
			$DownloadMb = round($DownloadByte/1024/1024, 2);
			$NewBandWidth = $UploadMb + $DownloadMb;
			//入库
			try {
			    Db::table('pfinfo')->where('id',$dataone['id'])->update(["bandwidth" => $dataone['bandwidth'] + $NewBandWidth,"updatetime" => time()]);
            } catch (Exception $e) {
            }
		}
		unset($data);
    });
};
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}