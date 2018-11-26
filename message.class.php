<?php

class message{
    private $appid = "wx29ba4b74715ef05b";
    private $appsecret = "fef5aca24e72f0f6129503165deef862";

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

	public function wxCurl($url)
    {
        //初始化
        $ch = curl_init();
        //设置参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //采集数据
        $res = curl_exec($ch);
        if(curl_errno($ch)) {
            var_dump(curl_error($ch));
        }

        $dataArr = json_decode($res, 1);
        return $dataArr;
    }

	public function getAccessToken()
    {
        $key = "access_token";
        $redis = $this->getRedis();

        if($redis->get($key)) {
            $accessToken = $redis->get($key);
        } else {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
            $data = $this->wxCurl($url);
            if(isset($data['access_token']) && $data['access_token']) {
                $redis->set($key, $data['access_token']);
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
        $data = $this->wxCurl($url);

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
				$content .= "回复姓名即可查看用户简介\n";
				$content .= "如: 嘟嘟\n";

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
				$template = "<xml> <ToUserName><![CDATA[%s]]></ToUserName> <FromUserName><![CDATA[%s]]></FromUserName> <CreateTime>%s</CreateTime> <MsgType><![CDATA[%s]]></MsgType> <Content><![CDATA[%s]]></Content> </xml>";
				$info = sprintf($template, $toUser, $fromUser, $time, 'text', '你想做啥子，别乱来别乱来');
			}

			echo $info;
		} 
	}
}
