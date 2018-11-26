<?php
define('IMG_PATH', '/url/local/html/wechat/img');

//file_put_contents('log.txt', $_SERVER['REQUEST_URI']);

//$timestamp = $_GET['timestamp'];
//$nonce = $_GET['nonce'];
//$token = 'mingxing';
//$signature = $_GET['signature'];
//$echoStr = isset($_GET['echostr']) ? $_GET['echostr'] : '';

//$signArr = [$timestamp, $nonce, $token];
//sort($signArr);
//$signStr = sha1(implode('', $signArr));

//if($signStr == $signature && $echoStr) {
//	echo $_GET['echostr'];
//	exit;
//} else {
	require_once('message.class.php');

	$msg = new message();
	$msg->responseMsg();
//}
