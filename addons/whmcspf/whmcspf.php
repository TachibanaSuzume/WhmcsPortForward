<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use \WHMCS\Database\Capsule as Capsule;
function whmcspf_config()
{
    return array(
        'name' => '端口转发流量统计', 
        'description' => '端口转发流量统计', 
        'author' => 'Flyqie',
        'version' => '1.5',
        'fields' => array(
            'authkey' => array(
                'FriendlyName' => '验证密钥',
                'Type' => 'text',
                'Size' => '500'
            )
        )
    );
}
function whmcspf_activate()
{
	try {
		if (!Capsule::schema()->hasTable('mod_whmcspf')) {
			Capsule::schema()->create('mod_whmcspf', function ($table) {
				$table->increments('id');
				$table->text('serviceid');
				$table->text('bandwidth');
				$table->text('updatetime');
			});
		}
		if (!Capsule::schema()->hasTable('mod_whmcspf_suspservice')) {
			Capsule::schema()->create('mod_whmcspf_suspservice', function ($table) {
				$table->increments('id');
				$table->text('serviceid');
				$table->datetime('untime');
				$table->datetime('addtime');
			});
		}
	} catch (Exception $e) {
		return [
			'status' => 'error',
			'description' => '不能创建表 : ' . $e->getMessage()
		];
	}
    return array('status' => 'success','description' => '插件已经成功激活');
}
function whmcspf_deactivate()
{
	Capsule::schema()->dropIfExists('mod_whmcspf');
	Capsule::schema()->dropIfExists('mod_whmcspf_suspservice');
	
	return [
		'status' => 'success',
		'description' => '模块卸载成功,你不爱老夏了'
	];
}
function whmcspf_output($vars)
{
	if($_REQUEST['action'] == 'get_info_list'){
		if(@$_REQUEST['ajax'] != 'true'){
			exit('禁止直接访问');
		}
		$infolist = Capsule::table('mod_whmcspf')->get();
		$infolistarray = array();
		$num = 0;
		foreach ($infolist as $values) {
			$suspfind = Capsule::table('mod_whmcspf_suspservice')->where('serviceid',$values->serviceid)->first();
			$PackAgeINfo = Capsule::table('tblproducts')->where('id',Capsule::table('tblhosting')->where('id',$values->serviceid)->first()->packageid)->first();
			if($PackAgeINfo){
				$AllBandwidth = $PackAgeINfo->configoption1;
			}else{
				$AllBandwidth = 0;
			}
			//$AllBandwidth = $PackAgeINfo->configoption1;
			$FreeBandwidth = $AllBandwidth - $values->bandwidth;
			if($FreeBandwidth < 0){
				$FreeBandwidth = '0';
			}
			if($suspfind){
				$Status = '流量超限';
				$unsptime = $suspfind->untime;
			}else{
				$Status = '正常';
				$unsptime = '无';
			}
			$infolistarray[$num]['id'] = $values->id;
			$infolistarray[$num]['serviceid'] = '<a href="/admin/clientsservices.php?id='.$values->serviceid.'" target="_blank">#'.$values->serviceid.'</a>';
			$infolistarray[$num]['usedbandwidth'] = $values->bandwidth;
			$infolistarray[$num]['freebandwidth'] = $FreeBandwidth;
			$infolistarray[$num]['allbandwidth'] = $AllBandwidth;
			$infolistarray[$num]['updatetime'] = date('Y-m-d H:i:s', $values->updatetime);
			$infolistarray[$num]['unsptime'] = $unsptime;
			$infolistarray[$num]['status'] = $Status;
			$num++;
		}
		$Infoarray['result'] = 'success';
		$Infoarray['info'] = $infolistarray;
		header('Content-Type: text/json; charset=utf-8');
		exit(json_encode($Infoarray));
	}else{
		include 'templates.admin.php';
		if(@$_REQUEST['ajax'] != 'true'){
			echo $Header.PHP_EOL.$info_management.PHP_EOL.$Footer.PHP_EOL;
		}else{
			exit($info_management.PHP_EOL);
		}
	}
}
function whmcspf_clientarea($vars)
{
	if(@$_REQUEST['authkey'] != $vars['authkey']){
		exit('验证密钥错误');
	}else{
		$PackAgeINfo = Capsule::table('tblproducts')->where('id',Capsule::table('tblhosting')->where('id',$_REQUEST['serviceid'])->first()->packageid)->first();
		if(!$PackAgeINfo){
			exit('产品找不到');
		}
		if(!@$_REQUEST['serviceid'] || !@$_REQUEST['updatetime'] || !@$_REQUEST['bandwidth']){
			exit('参数不完整');
		}
		if(Capsule::table('tblhosting')->where('id',$_REQUEST['serviceid'])->first()->domainstatus != 'Active'){
			//说明已经被暂停了,可以直接不进行下面的操作
			exit('success');
		}
		if(Capsule::table('mod_whmcspf')->where('serviceid',$_REQUEST['serviceid'])->first()){
			Capsule::table('mod_whmcspf')->where('serviceid',$_REQUEST['serviceid'])->update(['updatetime' => $_REQUEST['updatetime'],'bandwidth' => $_REQUEST['bandwidth']]);
		}else{
			Capsule::table('mod_whmcspf')->insert(['serviceid' => $_REQUEST['serviceid'],'updatetime' => $_REQUEST['updatetime'],'serviceid' => $_REQUEST['serviceid'],'bandwidth' => $_REQUEST['bandwidth']]);
		}
		if(($PackAgeINfo->configoption1 < $_REQUEST['bandwidth'])){
			//流量超额
			$unsusptime = date("Y-m", strtotime("+1 months")).'-1';
			localAPI('ModuleSuspend', array('serviceid' => $_REQUEST['serviceid'],'suspendreason' => '流量超额'), Capsule::table('tbladmins')->first()->id);
			Capsule::table('tblhosting')->where('id',$_REQUEST['serviceid'])->update(['domainstatus' => 'Suspended']);
			whmcspf_setCustomfieldsValue('forwardstatus','maxtra',$_REQUEST['serviceid'],null);
			//如果产品到期时间小于解除暂停的时间,那么不作处理
			if(strtotime($unsusptime) < strtotime(Capsule::table('tblhosting')->where('id',$_REQUEST['serviceid'])->first()->nextduedate)){
				Capsule::table('mod_whmcspf_suspservice')->insert(['serviceid' => $_REQUEST['serviceid'],'untime' => $unsusptime,'addtime' => date("Y-m-d")]);
			}
		}
        whmcspf_setCustomfieldsValue('bandwidth',$_REQUEST['bandwidth'],$_REQUEST['serviceid'],null);
		exit('success');
	}
}

function whmcspf_setCustomfieldsValue($field,$value,$servid,$uid){
    $ownerRow = Capsule::table('tblhosting')->where('id',$servid)->first();
    if (!$ownerRow){
         return false;
    }
    if (!is_null($uid) && $uid != $ownerRow->userid){
        return false;
    }
    $res = Capsule::table('tblcustomfields')->where('relid',$ownerRow->packageid)->where('fieldname',$field)->first();
    if ($res) {
        $fieldValue = Capsule::table('tblcustomfieldsvalues')->where('relid',$ownerRow->id)->where('fieldid',$res->id)->first();
        if ($fieldValue) {
            if($fieldValue->value != $value) {
                Capsule::table('tblcustomfieldsvalues')->where('relid',$ownerRow->id)->where('fieldid', $res->id)->update(['value' => $value,]);
            }else{
                Capsule::table('tblcustomfieldsvalues')->insert(['relid' => $ownerRow->id,'fieldid' => $res->id,'value' => $value]);
            }
        }else{
			Capsule::table('tblcustomfieldsvalues')->insert(['relid' => $ownerRow->id,'fieldid' => $res->id,'value' => $value]);
		}
    }
}