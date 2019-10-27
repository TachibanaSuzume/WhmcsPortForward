<?php
use Workerman\Worker;
use think\Db;
require_once __DIR__ . '/vendor/autoload.php';
$http_worker = new Worker("http://0.0.0.0:1388");
$http_worker->name = 'Http Api Server';
$http_worker->count = 4;
include __DIR__.'/config.php';
$api_redis_client = new Predis\Client(['scheme' => 'tcp','host' => $RedisIP,'port' => $RedisPort,'parameters'=>['password' => $RedisPass]]);
Db::setConfig(['type'=> 'sqlite','database'=> 'database.db','prefix'=> '','debug'=> true]);
$http_worker->onMessage = function($connection, $data){
	global $api_redis_client;
	include __DIR__.'/config.php';
	if(!@$_REQUEST['username'] || !@$_REQUEST['password'] || !@$_REQUEST['action'] || !@$_REQUEST['serviceid']){
		$connection->send(json_encode(array('code' => 403,'msg' => '参数不全#1')));
		return ;
	}
	if((trim($_REQUEST['username']) != $authusername) || (trim($_REQUEST['password']) != $authpassword)){
		$connection->send(json_encode(array('code' => 403,'msg' => '鉴权错误')));
		return ;
	}
	if(trim($_REQUEST['action']) == 'add'){
		if(!@$_REQUEST['ptype'] || !@$_REQUEST['rport'] || !@$_REQUEST['rsip']){
			$connection->send(json_encode(array('code' => 500,'msg' => '参数不全#2')));
			return ;
		}
	    if(Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find()){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID已经存在')));
			return ;
	    }
		if(trim($_REQUEST['ptype']) != 'tcp' && trim($_REQUEST['ptype']) != 'udp'){
			$connection->send(json_encode(array('code' => 500,'msg' => '转发类别错误')));
			return ;
		}
		while(true){
	       $makenum = 0;
           $portnum = mt_rand($portmin,$portmax);
	       if($makenum >= $portmakemax){
			    $portnum = null;
				break;
	       }
	       if(!(Db::table('pfinfo')->where('sport',$portnum)->find())){
		        break;
	       }
	       $makenum++;
        }
		if(!$portnum){
			$connection->send(json_encode(array('code' => 500,'msg' => '端口生成次数已经超过限制,请稍候重试')));
		    return ;
		}
		$ServiceFileInfo = pf_gen_service_php(trim($_REQUEST['ptype']),trim($_REQUEST['serviceid']),trim($_REQUEST['rsip']),trim($_REQUEST['rport']),$portnum);
		file_put_contents(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php',$ServiceFileInfo);
		$sqlreturn = Db::table('pfinfo')->insert(["bandwidth" => '0',"status" => 'ok',"updatetime" => time(),"addtime" => time(),"serviceid" => trim($_REQUEST['serviceid']),"ptype" => trim($_REQUEST['ptype']),"rsip" => trim($_REQUEST['rsip']),"rport" => trim($_REQUEST['rport']),"sport" => $portnum]);
		if($sqlreturn){
			$api_redis_client->set(trim($_REQUEST['serviceid']).'_upload','0');
			$api_redis_client->set(trim($_REQUEST['serviceid']).'_download','0');
			$connection->send(json_encode(array('code' => 200,'msg' => '添加成功','sport' => $portnum)));
		}else{
			$connection->send(json_encode(array('code' => 500,'msg' => '数据库操作失败')));
			@unlink(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php');
		}
		return ;
	}elseif(trim($_REQUEST['action']) == 'del'){
		$DelInfo = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find();
	    if(!$DelInfo){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID不存在')));
			return ;
	    }
		@unlink(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php');
		$sqlreturn = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->delete();
		if($sqlreturn){
			$api_redis_client->del(trim($_REQUEST['serviceid']).'_upload');
			$api_redis_client->del(trim($_REQUEST['serviceid']).'_download');
			$connection->send(json_encode(array('code' => 200,'msg' => '删除成功')));
		}else{
			$connection->send(json_encode(array('code' => 500,'msg' => '数据库操作失败')));
		}
		return ;
	}elseif(trim($_REQUEST['action']) == 'update'){
		$ServiceInfo = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find();
	    if(!$ServiceInfo){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID不存在')));
			return ;
	    }
		if(!@$_REQUEST['ptype'] || !@$_REQUEST['rport'] || !@$_REQUEST['rsip']){
			$connection->send(json_encode(array('code' => 500,'msg' => '参数不全#2')));
			return ;
		}
		if(trim($_REQUEST['ptype']) != 'tcp' && trim($_REQUEST['ptype']) != 'udp'){
			$connection->send(json_encode(array('code' => 500,'msg' => '转发类别错误')));
			return ;
		}
		$ServiceFileInfo = pf_gen_service_php(trim($_REQUEST['ptype']),trim($ServiceInfo['serviceid']),trim($_REQUEST['rsip']),trim($_REQUEST['rport']),trim($ServiceInfo['sport']));
		file_put_contents(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php',$ServiceFileInfo);
		$sqlreturn = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->update(["rsip" => trim($_REQUEST['rsip']),"rport" => trim($_REQUEST['rport']),"ptype" => trim($_REQUEST['ptype'])]);
		if($sqlreturn){
			$connection->send(json_encode(array('code' => 200,'msg' => '更新成功')));
		}else{
			$connection->send(json_encode(array('code' => 500,'msg' => '数据库操作失败')));
		}
		return ;
	}elseif(trim($_REQUEST['action']) == 'unsusp'){
		$ServiceInfo = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find();
	    if(!$ServiceInfo){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID不存在')));
			return ;
	    }
		$ServiceFileInfo = pf_gen_service_php(trim($ServiceInfo['ptype']),trim($ServiceInfo['serviceid']),trim($ServiceInfo['rsip']),trim($ServiceInfo['rport']),trim($ServiceInfo['sport']));
		file_put_contents(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php',$ServiceFileInfo);
		$sqlreturn = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->update(["status" => 'ok']);
		if($sqlreturn){
			$connection->send(json_encode(array('code' => 200,'msg' => '解除暂停成功')));
		}else{
			$connection->send(json_encode(array('code' => 500,'msg' => '数据库操作失败')));
		}
		return ;
	}elseif(trim($_REQUEST['action']) == 'rebuild'){
		$ServiceInfo = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find();
	    if(!$ServiceInfo){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID不存在')));
			return ;
	    }
		$ServiceFileInfo = pf_gen_service_php(trim($ServiceInfo['ptype']),trim($ServiceInfo['serviceid']),trim($ServiceInfo['rsip']),trim($ServiceInfo['rport']),trim($ServiceInfo['sport']));
		file_put_contents(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php',$ServiceFileInfo);
		$connection->send(json_encode(array('code' => 200,'msg' => '重建成功')));
		return ;
	}elseif(trim($_REQUEST['action']) == 'susp'){
		$ServiceInfo = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->find();
	    if(!$ServiceInfo){
			$connection->send(json_encode(array('code' => 500,'msg' => 'ServiceID不存在')));
			return ;
	    }
		@unlink(__DIR__.'/forward_service/'.trim($_REQUEST['serviceid']).'.php');
		$sqlreturn = Db::table('pfinfo')->where('serviceid',trim($_REQUEST['serviceid']))->update(["status" => 'susp']);
		if($sqlreturn){
			$connection->send(json_encode(array('code' => 200,'msg' => '暂停成功')));
		}else{
			$connection->send(json_encode(array('code' => 500,'msg' => '数据库操作失败')));
		}
		return ;
	}elseif(trim($_REQUEST['action']) == 'test'){
		$connection->send(json_encode(array('code' => 200,'msg' => '对接成功')));
		return ;
	}else{
		$connection->send(json_encode(array('code' => 404,'msg' => '动作不存在')));
		return ;
	}
};
if(!function_exists('pf_gen_service_php')){
function pf_gen_service_php($ptype,$serviceid,$rsip,$rport,$sport){
	if(filter_var(trim($rsip), FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){
		$rsip = '['.trim($rsip).']';
	}
	$info = file_get_contents(__DIR__.'/service_file_template.php');
	$info = str_replace("[PTYPE]",$ptype,$info);
	$info = str_replace("[SERID]",$serviceid,$info);
	$info = str_replace("[RSIP]",$rsip,$info);
	$info = str_replace("[RPORT]",$rport,$info);
	$info = str_replace("[SPORT]",$sport,$info);
	return $info;
}
}
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}