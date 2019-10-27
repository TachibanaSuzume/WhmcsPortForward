<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

function portforward_MetaData(){
    return array(
        'DisplayName' => 'PortForward',
        'APIVersion' => '1.1', 
        'RequiresServer' => true, 
    );
}

function portforward_ConfigOptions()
{
    return array(
        '每月流量(Mb)' => array(
            'Type' => 'text',
            'Size' => '500'
        )
    );
}

function portforward_CreateAccount(array $params)
{
    try {
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'add';
		$postfields['serviceid'] = $params['serviceid'];
		if(!filter_var(trim($params['customfields']['rsip']), FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) && !filter_var(trim($params['customfields']['rsip']), FILTER_VALIDATE_IP)){
			throw new Exception('IP不正确');
		}
		if(!is_numeric(trim($params['customfields']['rport'])) || trim($params['customfields']['rport']) > 25535 || trim($params['customfields']['rport']) < 1){
			throw new Exception('端口不正确');
		}
		$postfields['ptype'] = trim($params['customfields']['ptype']);
		$postfields['rport'] = trim($params['customfields']['rport']);
		$postfields['rsip'] = trim($params['customfields']['rsip']);
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            throw new Exception('服务器返回信息为空');	
		}
        if($ReturnInfo['code'] == 200){
			Capsule::table('tblhosting')->where('id',$params['serviceid'])->update(['domain' => $params['customfields']['rsip'].':'.trim($params['customfields']['rport'])]);
			portforward_setCustomfieldsValue('sport',$ReturnInfo['sport'],$params['serviceid'],null);
			return 'success';
        }else{
            throw new Exception('开通失败,'.$ReturnInfo['msg']);
		}			 
    } catch (Exception $e) {
        logModuleCall(
            'portforward',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function portforward_SuspendAccount(array $params)
{
    try {
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'susp';
		$postfields['serviceid'] = $params['serviceid'];
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            throw new Exception('服务器返回信息为空');	
		}
        if($ReturnInfo['code'] == 200){
			return 'success';
        }else{
            throw new Exception('暂停失败,'.$ReturnInfo['msg']);
		}			 
    } catch (Exception $e) {
        logModuleCall(
            'portforward',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function portforward_RebuildConf(array $params)
{
    try {
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'rebuild';
		$postfields['serviceid'] = $params['serviceid'];
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            throw new Exception('服务器返回信息为空');	
		}
        if($ReturnInfo['code'] == 200){
			return 'success';
        }else{
            throw new Exception('重建失败,'.$ReturnInfo['msg']);
		}			 
    } catch (Exception $e) {
        logModuleCall(
            'portforward',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function portforward_AdminCustomButtonArray(){
    return array(
        "重建" => "RebuildConf"
    );
}

function portforward_UnsuspendAccount(array $params)
{
    try {
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'unsusp';
		$postfields['serviceid'] = $params['serviceid'];
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            throw new Exception('服务器返回信息为空');	
		}
        if($ReturnInfo['code'] == 200){
			return 'success';
        }else{
            throw new Exception('解除暂停失败,'.$ReturnInfo['msg']);
		}			 
    } catch (Exception $e) {
        logModuleCall(
            'portforward',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function portforward_TerminateAccount(array $params)
{
    try {
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'del';
		$postfields['serviceid'] = $params['serviceid'];
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            throw new Exception('服务器返回信息为空');	
		}
        if($ReturnInfo['code'] == 200){	 
			return 'success';
        }else{
            throw new Exception('删除失败,'.$ReturnInfo['msg']);
		}			 
    } catch (Exception $e) {
        logModuleCall(
            'portforward',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
}

function portforward_ClientArea(array $params)
{
	if($_REQUEST['pfaction'] == 'changevalue'){
        if(!$_REQUEST['rsip'] || !$_REQUEST['rport']){
            exit(json_encode(array('result' => 'failed','msg' => '转发到的IP和端口不得为空')));
		}
		if(!filter_var(trim($_REQUEST['rsip']), FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) && !filter_var(trim($_REQUEST['rsip']), FILTER_VALIDATE_IP)){
			exit(json_encode(array('result' => 'failed','msg' => 'IP不正确')));
		}
		if(!is_numeric(trim($_REQUEST['rport'])) || trim($_REQUEST['rport']) > 25535 || trim($_REQUEST['rport']) < 1){
			exit(json_encode(array('result' => 'failed','msg' => '端口不正确')));
		}
		$postfields['username'] = $params['serverusername'];
		$postfields['password'] = $params['serverpassword'];
		$postfields['action'] = 'update';
		$postfields['serviceid'] = $params['serviceid'];
		$postfields['rsip'] = trim($_REQUEST['rsip']);
		$postfields['rport'] = trim($_REQUEST['rport']);
		$postfields['ptype'] = trim($params['customfields']['ptype']);
		$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
        if(!$ReturnInfo){
            exit(json_encode(array('result' => 'failed','msg' => 'ServiceID不存在')));
		}
        if($ReturnInfo['code'] == 200){	 
		     portforward_setCustomfieldsValue('rsip|源服务器IP',trim($_REQUEST['rsip']),$params['serviceid'],null);
			 portforward_setCustomfieldsValue('rport|源服务器端口',trim($_REQUEST['rport']),$params['serviceid'],null);
			 Capsule::table('tblhosting')->where('id',$params['serviceid'])->update(['domain' => trim($_REQUEST['rsip']).':'.trim($_REQUEST['rport'])]);
			 exit(json_encode(array('result' => 'success','msg' => '')));
        }else{
			 exit(json_encode(array('result' => 'failed','msg' => $ReturnInfo['msg'])));
		}		
	}
	if(trim($params['customfields']['forwardstatus']) == 'maxtra'){
		$templatevar['forwardstatus'] = '流量超额';
	}else{
		$templatevar['forwardstatus'] = '正常';
	}
	$ProxyIP = null;
	$HashInfo = portforward_gethashinfo($params['serveraccesshash']);
	if(@$HashInfo['proxyip']){
		$ProxyIPArray = explode(',',trim($HashInfo['proxyip']));
		foreach($ProxyIPArray as $ProxyIPOne){
			$ProxyIP .= $ProxyIPOne.'<br>';
		}
		$ProxyIP = rtrim($ProxyIP,'<br>');
	}else{
		$ProxyIP .= $params['serverip'];
	}
	$ProxyIP = base64_encode($ProxyIP);
	$templatevar['ptype'] = trim($params['customfields']['ptype']);
	$templatevar['sport'] = trim($params['customfields']['sport']);
	$templatevar['sip'] = $ProxyIP;
	$templatevar['rsip'] = trim($params['customfields']['rsip']);
	$templatevar['rport'] = trim($params['customfields']['rport']);
	$templatevar['usedbandwidth'] = trim($params['customfields']['bandwidth']);
	$templatevar['alldbandwidth'] = trim($params['configoption1']);
	$templatevar['freedbandwidth'] = $templatevar['alldbandwidth'] - $templatevar['usedbandwidth'];
	if((int)$templatevar['freedbandwidth'] < 0){
		$templatevar['freedbandwidth'] = '0';
	}
    return array(
        'tabOverviewReplacementTemplate' => 'templates/clientarea.tpl',
        'templateVariables' => $templatevar,
    );
}

function portforward_curlconnect($url,$postfields){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function portforward_TestConnection($params){
	$postfields['username'] = $params['serverusername'];
	$postfields['password'] = $params['serverpassword'];
	$postfields['action'] = 'test';
	$postfields['serviceid'] = 'none';
	$ReturnInfo = json_decode(portforward_curlconnect('http://'.$params['serverip'].':1388/',$postfields),true);
    if(!$ReturnInfo){
	    return array('success' => false,'error' => '服务器返回信息为空');	
    }
    if($ReturnInfo['code'] == 200){	 
		return array('success' => true,'error' => '');
    }else{
		return array('success' => false,'error' => $ReturnInfo['msg']);
   }
}

function portforward_setCustomfieldsValue($field,$value,$servid,$uid){
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

function portforward_gethashinfo($data){
    preg_match_all( '/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches );
    $result = array();
    foreach($matches[1] as $k => $v){
        $result[$v] = $matches[2][$k];
    }
	return $result;
}