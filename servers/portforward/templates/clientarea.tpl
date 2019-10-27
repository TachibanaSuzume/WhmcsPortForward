{if $rawstatus eq 'active'}
<link rel="stylesheet" href="modules/servers/portforward/theme/style.css">
<link rel="stylesheet" href="modules/servers/portforward/theme/flags.css">
<link href="modules/servers/portforward/theme/jquery-confirm.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="modules/servers/portforward/theme/jquery-confirm.js"></script>
<script type="text/javascript" src="modules/servers/portforward/theme/base64.js"></script>
    <div class="row m-b-15">
		<div class="col-md-6 col-sm-12">
			<h4>服务信息 <small>Service Detail</small></h4>
		</div>
	</div>
<div id="YVSY">	
	<div class="row">
        <div class="col-md-4 col-sm-12">
            <a href="javascript:;">
                <div class="box">
                    <div class="boxTitle">
                        产品名称
                    </div>
                    <div>
                        <span class="boxContent">{$product}</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-12">
            <a href="javascript:;">
                <div class="box">
                    <div class="boxTitle">
                        产品状态
                    </div>
                    <div>
                        <span class="boxContent">{$status}</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-12">
            <a href="javascript:;">
                <div class="box">
                    <div class="boxTitle">
                        到期时间
                    </div>
                    <div>
                        <span class="boxContent">{$nextduedate}</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
    <div class="row m-b-15">
	<div class="col-md-6 col-sm-12 pull-right">
		    <button type="button" class="btn btn-default pull-right" onclick="edit_value('{$rsip}','{$rport}','{$serviceid}')">
				<span class="glyphicon glyphicon-fire m-r-5" aria-hidden="true"></span> 修改转发信息
			</button>
        </div>
		<div class="col-md-6 col-sm-12">
            <h4>产品信息 <small>Product Detail</small></h4>
        </div>
    </div>
<div id="YVSY">	
    <div class="row">
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    转发类型
                </div>
                <div>
                <span class="boxContent">{$ptype}</span>
                  </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    转发IP
                </div>
                <div>
                  <span class="boxContent"><strong onclick="get_proxy_ip()">点我获取IP</strong></span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    转发端口
                </div>
                <div>
                  <span class="boxContent">{$sport}</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    源IP
                </div>
                <div>
                  <span class="boxContent">{$rsip}</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    源端口
                </div>
                <div>
                  <span class="boxContent">{$rport}</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    转发状态
                </div>
                <div>
                  <span class="boxContent">{$forwardstatus}</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    已用流量
                </div>
                <div>
                  <span class="boxContent">{$usedbandwidth} Mb</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    可用流量
                </div>
                <div>
                  <span class="boxContent">{$freedbandwidth} Mb</span>
                </div>
             </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    总流量
                </div>
                <div>
                  <span class="boxContent">{$alldbandwidth} Mb</span>
                </div>
             </div>
        </div>
	</div>
</div>
{literal}
<script>
var edit_value = function(rsip_valuefunc,rport_valuefunc,serverid){
    $.confirm({
        title: '修改转发信息',
        content: '' +
        '<form action="" class="formName">' +
        '<div class="form-group">' +
        '<p>修改转发信息</p>' +
        '<div class="form-group">'+
		'<label>IP地址</label><input type="text" class="form-control" name="rsip" value="' + rsip_valuefunc +'">' +
		'<label>端口</label><input type="text" class="form-control" name="rport" value="' + rport_valuefunc +'">' +
        '</div>' +
        '</form>',
        buttons: {
            formSubmit: {
                text: '提交',
                btnClass: 'btn-blue',
                action: function () {
                    var rsip = this.$content.find('[name=rsip]').val();
					var rport = this.$content.find('[name=rport]').val();
                    if(!rport || !rsip){
                        $.alert('请完整填写信息');
                        return false;
                    }
                    $.confirm({
                        content: function () {
                            var self = this;
                            return $.ajax({
                                url: window.location.href,
                                dataType: 'json',
								method: 'post',
								data: {
									  pfaction: 'changevalue',
									  rsip: rsip,
									  rport: rport
                                }
                            }).done(function (response) {
                                if (response.result == "success"){
                                    self.setType('green')
                                    self.setTitle('成功');
                                    self.setContent('修改成功,5秒后自动刷新页面');
                                    setTimeout(function(){
                                         window.location.reload();
                                    },5000)
                                } else {
                                    self.setType('red')
                                    self.setTitle('错误');
                                    self.setContent(response.msg);
                                }

                            }).fail(function(){
                                self.setType('red')
                                self.setTitle('错误');
                                self.setContent('与服务器通讯时出现错误, 请重试.');
                            });
                        }
                    });
                }
            },
            cancel: {
                text: '取消',
            }
        },
        onContentReady: function () {
            var jc = this;
            this.$content.find('form').on('submit', function (e) {
                e.preventDefault();
                jc.$$formSubmit.trigger('click');
            });
        }
    });
}
{/literal}

function get_proxy_ip(){
proxyipinfo = Base64.decode('{$sip}');
{literal}
$.alert({title: '转发IP列表',content: proxyipinfo});
{/literal}
}
</script>
{else}
抱歉,该产品目前无法管理({$status})
{if $suspendreason}
,原因:{$suspendreason}
{/if}
{/if}		