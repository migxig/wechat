<?php

class message{
//    private $appid = "wx29ba4b74715ef05b";
//    private $appsecret = "fef5aca24e72f0f6129503165deef862";

    //测试号
    private $appid = "wxa4e670ac8ee5eea0";
    private $appsecret = "701c03e4ca96eb9f565231a1944e8a8f";

	public function getDb() 
	{
		$dsn = 'mysql:host=127.0.0.1;dbname=MX;charset=utf8';
		$user = 'root';
		$password = 'DSF376CNJKS';
		try{
			$db = new PDO($dsn,$user,$password);
			//echo '连接成功';
			return $db;
		}catch(PDOException $e){
			echo '数据库连接失败'.$e->getMessage();
		}
	}

	public function getRedis()
	{
		$redis = new Redis();
   		$redis->connect('127.0.0.1', 6379);

        //查看服务是否运行
        //echo "Connection to server sucessfully";
   		//echo "Server is running: " . $redis->ping();

		return $redis;
	}

	public function wxCurl($url, $type = 'get', $format = 'json', $postJson='')
    {
        //初始化
        $ch = curl_init();
        //设置参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);
        }
        //采集数据
        $res = curl_exec($ch);
        //关闭
        //curl_close($ch);

        if($format == 'json') {
            if(curl_errno($ch)) {
                return curl_error($ch);
            } else {
                $data = json_decode($res, 1);
            }
        } else {
            $data = $res;
        }
        return $data;
    }

	public function getAccessToken()
    {
        $key = "access_token";
        $redis = $this->getRedis();

        if($redis->get($key)) {
            $accessToken = $redis->get($key);
        } else {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
            $data = $this->wxCurl($url, 'get', 'json');
            if(isset($data['access_token']) && $data['access_token']) {
                $redis->set($key, $data['access_token']);
                $redis->expire($key, 7180);
            }

            $accessToken = $data['access_token'];
        }
        //var_dump($accessToken);

        return $accessToken;
    }

    public function getWxIpList()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$accessToken;
        $data = $this->wxCurl($url, 'get', 'json');

        //var_dump($data);

        return $data;
    }

	public function responseMsg()
	{
		header('Content-type: text/html;charset=utf-8');

		$postStr = file_get_contents('php://input');
		$postObj = simplexml_load_string($postStr);
		
		$toUser = $postObj->FromUserName;
                $fromUser  = $postObj->ToUserName;
                $time = time();
		
		if($postObj->MsgType == 'event') {
			if($postObj->Event == 'subscribe') {
				$toUser = $postObj->FromUserName;
                		$fromUser  = $postObj->ToUserName;
                		$time = time();
                		$msgType = 'text';
                		$content = "欢迎关注No3No4o\n";
				$content .= "回复城市名称即可查看天气情况\n";
				$content .= "如: 广州市\n";

	$template = "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[%s]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";
		
				$info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
				//file_put_contents('log.txt', $info);
				echo $info;
			}
		}
	
		//图文消息
		if($postObj->MsgType == 'text') {
			$sql = "SELECT * FROM `friends` where name = '".$postObj->Content."'";
			$db= $this->getDb();
			//var_dump($db);die;

			$res = $db->query($sql);
			$rows = $res->fetchAll();
			
			if($rows) {
				$tmpStr = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType>";
				$tmpStr .= "<ArticleCount>".count($rows)."</ArticleCount><Articles>";

				$img_path = 'http://111.230.146.229/wechat/';
				foreach($rows as $val) {
		$tmpStr .= "<item><Title><![CDATA[".$val['name']."]]></Title> <Description><![CDATA[".$val['summary']."]]></Description><PicUrl><![CDATA[".$img_path.$val['img']."]]></PicUrl><Url><![CDATA[".$val['url']."]]></Url></item>";
				}
				$tmpStr .= "</Articles></xml>";			
				$info = sprintf($tmpStr, $toUser, $fromUser, $time, 'news');
			} else {
			    $city = $postObj->Content;
                $url="http://wthrcdn.etouch.cn/weather_mini?city=".$city;
                $strZip = file_get_contents($url);
                $resultJson = gzdecode($strZip);
                $template = "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[%s]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";
                $content = '';
                $result = json_decode($resultJson, 1);

                if($result['status'] == 1000) {
                    $content .= "城市：".$result['data']['city']."\n";
                    $content .= "日期：".$result['data']['forecast'][0]['date']."\n";
                    $content .= "天气：".$result['data']['forecast'][0]['type']."\n";
                    $content .= "低温：".$result['data']['forecast'][0]['low']."\n";
                    $content .= "高温：".$result['data']['forecast'][0]['high']."\n";
                    $content .= "风向：".$result['data']['forecast'][0]['fengxiang']."\n";
                } else {
                    $content = '无相关信息';
                }
                $info = sprintf($template, $toUser, $fromUser, $time, 'text', $content);
            }

			echo $info;
		} 
	}

    public function definedMenu()
    {
        header('content-type: text/html;charset=utf-8');

        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $postArr = [
            'button' => [
                [
                    'type'=>'click',
                    'name'=>urlencode('菜单1'),
                    'key'=>'menu1',
                ],
                [
                    'name'=>urlencode('菜单2'),
                    'sub_button'=>[
                        [
                            'type'=>'click',
                            'name'=>urlencode('二级菜单1'),
                            'key'=>'sub_menu1',
                        ],
                        [
                            'type'=>'view',
                            'name'=>urlencode('二级菜单2'),
                            'url'=>'http://www.baidu.com',
                        ],
                    ],
                ],
                [
                    'type'=>'view',
                    'name'=>urlencode('菜单3'),
                    'url'=>'http://www.qq.com',
                ],
            ],
        ];
        $postJson = urldecode(json_encode($postArr));
        $data = $this->wxCurl($url, 'post', 'json', $postJson);

        var_dump($data);
    }
}
