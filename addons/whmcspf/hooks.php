<?php
use \WHMCS\Database\Capsule as Capsule;
add_hook('DailyCronJob', 1, function($vars) {
    $todayunsusp = Capsule::table('mod_whmcspf_suspservice')->where('untime',date("Y-m-d"))->get();
	if($todayunsusp){
		foreach ( $todayunsusp as $listone){
			localAPI('ModuleUnsuspend', array('serviceid' => $listone->serviceid), Capsule::table('tbladmins')->first()->id);
			Capsule::table('tblhosting')->where('id',$listone->serviceid)->update(['domainstatus' => 'Active']);
			whmcspf_setCustomfieldsValue('forwardstatus','Active',$listone->serviceid,null);
			Capsule::table('mod_whmcspf_suspservice')->where('serviceid',$listone->serviceid)->delete();
		}
	}
});
add_hook('AfterModuleTerminate', 1, function($vars) {
    Capsule::table('mod_whmcspf_suspservice')->where('serviceid',$vars['serviceid'])->delete();
});