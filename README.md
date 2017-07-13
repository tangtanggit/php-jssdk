# php-jssdk
基于thinkphp3.2.3的微信分享jsddk，重写微信分享jssdk的分享类，包括客户端和服务器获取token等。
贴出jssdk微信官方文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115


微信自定义分享jssdk步骤（前提是在你的公众号已经认证的情况下）

第一步：
登入微信公众平台查看appid和appsecret 并在/Home/conf/config.php下配置配置格式如下：
'weixin' => array(
	'appid' => '你微信公众号的appid', // 微信appdi
	'appsecret' => '你微信公众号的appsecret' // 微信
),

第二步：
需要在公众平台查看appsecret时同时设置ip白名单（你业务服务器的ip）如不设置获取access_token会报：40164错误：invalid ip, not in whitelist hint: [59FKqA0797e514]

第三步：
在公众平台设置js安全域名大概目录是：设置---公众号设置---功能设置

以上配置完成并且无误可将控制器和模板直接导入thinkphp框架的项目直接使用！
第一次写东西，真tm累！






