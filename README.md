# WhmcsPortForward
可以对接Whmcs进行销售的端口转发系统，支持TCP/UDP及Ipv6/Ipv4端口转发

自定义字段列表:

ptype|转发协议

sport

rsip|源服务器IP

rport|源服务器端口

bandwidth

forwardstatus

除'ptype|转发协议'、'rsip|源服务器IP'、'rport|源服务器端口'以外请全部设置为仅管理员可见!

'ptype|转发协议'、'rsip|源服务器IP'、'rport|源服务器端口'请设置为必填、在订单上时显示、在账单上显示!

安装:

1.安装Redis

2.apt update

3.apt install php php-posix php-pdo-sqlite php-curl

4.编辑config.php

5.Debug : php start.php start Daemon: php start.php start -d

6.Whmcs后台启用流量监控插件

7.添加服务器

8.添加产品

9.开通测试

服务器可选Hash

<proxyip>10.0.1.1,10.0.1.2,10.0.0.3,10.0.0.4,10.0.0.5,10.0.0.6,10.0.0.7</proxyip>
