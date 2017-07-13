<?php
/**
* thinkphp 3.2.3 jssdk服务器，获取token，ticket等 直接copy到控制器可用
* appid 微信公众号appid登录微信公众平台可查看
* appsecret 微信公众号appsecret登录微信公众平台需要管理员验证才可查看
* 微信官方文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115
* 郑重声明：微信分享只提供了自定义分享内容，web页面不能通过此接口调出微信分享框！
* @author 无邪<bubucom@aliyun.com> 2017-7-13 
* 
*/
namespace Home\Controller;
use Think\Controller; 

class jssdkShareController extends Controller{
	private $appid = ''; //appid
	private $appsecret = ''; //appid

	/**
	* 初始化 appid 和appsecret可以从在配置文件中获取，这里我调用config配置文件的参数。
	* 这里我假设你已经在/Home/conf/config已经配置了appid和appsecret了配置格式如下：
	*	'weixin' => array(
	*		'appid' => '你微信公众号的appid', // 微信appdi
	*		'appsecret' => '你微信公众号的appsecret' // 微信
	*	),
	* 这里要说明一下，在获取appsecret后还需要在微信供工作平台内设置ip白名单。如不设置获取access_token会报：40164错误：invalid ip, not in whitelist hint: [59FKqA0797e514]
	*/ 
	public function _initialize(){
		parent::_initialize();  //防止覆盖父类初始化
		
		$this->appid = C('weixin.appid');
		$this->appsecret = C('weixin.appsecret');
	}

	/**
	* 对应的控制器模板 index.html
	* 这里对应要分享的页面
	* @return index.html 显示模板
	*/
	public function index(){

		$this->display();
	}
	/**
	* 第一步：获取access_token
	* @return string 32位字符token 错误返回错误信息。。
	*/
	private function getAccess_Token(){
        //  调用tp内置S方法存缓存
        $wxtoken = S('wxtoken');
        if (!$wxtoken){  
          $appid = $this->appid;
          $appsecret = $this->appsecret;
          $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret."";
          $res = json_decode($this->httpGet($url));
          
          if(!isset($res->access_token)){ // 如果不是32位token打印错误信息 这里你也可以记录错误日志
          	 v($res);
          }
          	// 微信服务端是7200秒失效，这里自己服务端设置7100秒防止冲突
            S('wxtoken',$res->access_token,7100);
            $wxtoken = S('wxtoken');
        }
        return $wxtoken; 
    }

    /**
    * 第二步：获取32位access_token后继续获取jsapiticket
    * @return string/json 配置正确返回ticket，配置错误返回错误信息
    */
 	private function getJsapiticket(){
        // 存两个小时 
        $ticket = S('ticket');
        if (!$ticket){  
          $token = $this->getToken();
          $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$token."";
          $res = json_decode($this->httpGet($url));
          if(!isset($res->ticket)){ // 获取不到ticket打印错误信息 这里你也可以记录错误日志
          	 v($res);
          }
          	// 同样微信服务端是7200秒失效，这里自己服务端设置7100秒防止冲突
            S('ticket',$res->ticket,7100);
            $ticket = S('ticket');
        }
        return $ticket; 
    }

    /**
    * 第三步：服务端获取SignPackage,也就是需要在客户端配置的wx.config所需的参数
    * 此步骤包含了生成sha1加密signature和获取wx.config所需参数
    * 郑重提示：步骤应该是先服务区获取accesstoken 然后获取ticket然后获取SignPackage最后在客户端也就是tp模板页配置wx.config
    * @return Array 返回一个包含index.html模板页所需要的wx.config所有参数
    */
    public function getSignPackage() {
        $jsapiTicket = $this->getJsapiticket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        // 按顺序排列按sha1加密生成字符串
        $signature = sha1($string);

        $signPackage = array(
          "appId"     => C('weixin.appid'),
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
          "ticket" =>$jsapiTicket
        );
        return $signPackage; 
    }

    /**
    * phpcurl调用接口，这里直接使用jssdk自带的，有时间自己去学习
    * @return 返回一个json/xml/text 取决于接口返回什么样的数据
    */
    private function httpGet($url) {
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
	    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
	    curl_setopt($curl, CURLOPT_URL, $url);

	    $res = curl_exec($curl);
	    curl_close($curl);

	    return $res;
	}

    /**
    * 随机生成16位随机字符串
    * @return string 返回一个16位随机字符串
    */
    private function createNonceStr($length = 16) {
	    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	    $str = "";
	    for ($i = 0; $i < $length; $i++) {
	      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	    }
	    return $str;
	}
    /**
    * v方法，打印调试方法
    * @return echo 打印传入的参数
    */
    public function v($val=""){
    	echo '<pre>';
    	var_dump($val);
    	echo '</pre>';
    }
}