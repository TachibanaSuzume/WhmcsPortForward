<?php
//后台模板文件
$Header = <<<Header
<link href="../modules/addons/whmcspf/static/css/jquery-confirm.css" rel="stylesheet" />

<nav class="navbar navbar-default">
    <div class="container-fluid">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="#">端口转发流量统计</a>
            </div>
            <div class="collapse navbar-collapse" id="navbar-collapse-1">
              <ul class="nav navbar-nav">
                <li class=""><a href="javascript:void(0)" data-href="addonmodules.php?module=whmcspf" data-page="info_management">转发列表</a></li>
                <li class=""><a href="http://www.myserver.group" target="_blank">帮助中心 <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a></li>
              </ul>
            </div>
    </div>
</nav>


<script type="text/javascript">
    $("#contentarea h1:first").remove();
</script>
<div id="info_body" class="container-fluid">
Header;
$Footer = <<<Footer
<div class="main-loading" style="display: none">
    <h1 style="text-align:center;color: #8f8f8f;font-weight: 500;font-size: 48px">
            <i class="fa fa-refresh fa-spin"></i> 请稍后 , 正在加载页面
    </h1>
</div>

<script type="text/javascript">
    var page_lock = false;
    // 导航栏标识当前页面
    $(".navbar-nav a[data-page='" + page + "']").parent().addClass('active');


    // 异步加载页面
    $(".navbar-collapse ul.nav a[data-href]").click(function(){
        if (page_lock){
            return false;
        }
        $(".navbar-collapse ul li.active").removeClass("active");
        $(this).parent().addClass('active');
        $("#info_body").html($(".main-loading").html());
        $(".navbar-collapse ul.nav a[data-href]").prop("disabled", "disabled");
        page_lock = true;
        $("#info_body").load($(this).data("href") + "&ajax=true", function() {
            page_lock = false;
            $(".navbar-collapse ul.nav a[data-href]").prop("disabled", "");
            $(".navbar-collapse ul li.active").removeClass("active");
            $(".navbar-nav a[data-page='" + page + "']").parent().addClass('active');
            
            window.history.pushState("", "", $("ul.navbar-nav li.active a").data("href"));
        })
    })
</script>

<script type="text/javascript" src="../modules/addons/whmcspf/static/js/jquery-confirm.js"></script>
<hr/><p style='float:right'>当前运行版本: v1.5</p>
Footer;
$info_management = <<<info_managementtemp
<div class="info-body">
    <div class="loading">
        <h1 style="text-align:center;color: #8f8f8f;font-weight: 500;font-size: 48px">
                <i class="fa fa-refresh fa-spin"></i> 请稍后 , 正在加载数据
        </h1>
    </div>
    <div class="info row" style="display:none">
        <div class="infolist" style="display:none">
            <div class="btn-c" style="float: right;">
                <a href="javascript:update_info()" class="btn btn-default"><i class="fa fa-refresh"></i> 刷新信息</a>
            </div>
            <table class="data-table table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>服务ID</th>
                        <th>已用流量(Mb)</th>
						<th>剩余流量(Mb)</th>
						<th>总流量(Mb)</th>
						<th>状态</th>
						<th>解禁时间</th>
						<th>更新时间</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>

<link rel="stylesheet" type="text/css" href="../assets/css/dataTables.bootstrap.css">
<link rel="stylesheet" type="text/css" href="../assets/css/dataTables.responsive.css">
<script type="text/javascript" charset="utf8" src="../assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="../assets/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" charset="utf8" src="../assets/js/dataTables.responsive.min.js"></script>

<style>
.dataTables_filter, .dataTables_paginate{
    float: right;
}
</style>

<script type="text/javascript">

var table = jQuery(".data-table").DataTable({
    "dom": '<"listtable"fit>pl',
    "responsive": true,
    "bAutoWidth": false,
    "oLanguage": {
        "sEmptyTable":     "No Records Found",
        "sInfo":           "Showing _START_ to _END_ of _TOTAL_ entries",
        "sInfoEmpty":      "Showing 0 to 0 of 0 entries",
        "sInfoFiltered":   "(filtered from _MAX_ total entries)",
        "sInfoPostFix":    "",
        "sInfoThousands":  ",",
        "sLengthMenu":     "Show _MENU_ entries",
        "sLoadingRecords": "Loading...",
        "sProcessing":     "Processing...",
        "sSearch":         "",
        "sZeroRecords":    "No Records Found",
        "oPaginate": {
            "sFirst":    "First",
            "sLast":     "Last",
            "sNext":     "Next",
            "sPrevious": "Previous"
        }
    },
    "pageLength": 25,
    "order": [
        [ 0, "asc" ]
    ],
    "lengthMenu": [
        [5, 25, 50, -1],
        [5, 25, 50, "All"]
    ],
    "stateSave": true
});

var load_info = function(){
    $(".loading").show();
    $(".info.row").hide();

    $.ajax({
        url: "addonmodules.php?module=whmcspf&ajax=true&action=get_info_list",
        dateType: "json"
    }).done(function(response){
        $(".loading").hide();
        $(".info.row").show();
        if (response.result == "success"){
            $(".info .infolist").show();
            infos = response.info;
            for (info in infos){
				table.row.add([
                "#" + infos[info].id,
                infos[info].serviceid,
                infos[info].usedbandwidth,
				infos[info].freebandwidth,
				infos[info].allbandwidth,
				infos[info].status,
				infos[info].unsptime,
                infos[info].updatetime
                ]).draw();
            }
        }
    }).fail(function(){
        $(".loading h1").html('<i class="fa fa-times" aria-hidden="true"></i> 加载数据错误, 请刷新重试');
    })
}

var update_info = function(){
    $.confirm({
        content: function () {
            var self = this;
            return $.ajax({
                url: 'addonmodules.php?module=whmcspf&ajax=true&action=get_info_list',
                dataType: 'json',
            }).done(function (response) {
                if (response.result == "success"){
                    self.setType('green')
                    self.setTitle('更新完成');
                    self.setContent('已更新信息!');
                    self.close()
                    $('a[data-page="info_management"]').click()
                } else {
                    self.setType('red')
                    self.setTitle('错误');
                    self.setContent(response.error);
                }

            }).fail(function(){
                self.setType('red')
                self.setTitle('错误');
                self.setContent('与服务器通讯时出现错误, 请重试.');
            });
        }
    });
}


load_info()
</script>

<script type="text/javascript">
    var page = "info_management";
</script>
info_managementtemp;
$HelloWorld = null;