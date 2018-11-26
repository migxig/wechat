<?php
define('IMG_PATH', '/url/local/html/wechat/img');

//file_put_contents('log.txt', $_SERVER['REQUEST_URI']);

function validWx()
{
    $timestamp = $_GET['timestamp'];
    $nonce = $_GET['nonce'];
    $token = 'mingxing';
    $signature = $_GET['signature'];

    $signArr = [$timestamp, $nonce, $token];
    sort($signArr);
    $signStr = sha1(implode('', $signArr));

    if($signStr == $signature) {
        return true;
    } else {
        return false;
    }
}

$echoStr = isset($_GET['echostr']) ? $_GET['echostr'] : '';
if($echoStr) {
    //验证微信发来的消息
    $check = validWx();
} else {
    //根据参数执行控制器-方法
    $ct = isset($_GET['ct']) ? trim($_GET['ct']) : '';
    $ac = isset($_GET['ac']) ? trim($_GET['ac']) : '';
    if($ct && file_exists($ct.'.class.php')) {
        require_once($ct.'.class.php');
        $class = new $ct;
        $class->$ac();
    } else {
        //默认回复
        require_once('message.class.php');

        $msg = new message();
        $msg->responseMsg();
    }
}