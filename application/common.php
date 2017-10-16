<?php

/*发送短信验证码
auth:mpc
$mobile:手机号
$code :验证码
*/
function NewSms($Mobile){
      $str = "1234567890123456789012345678901234567890";
      $str = str_shuffle($str);
      $code= substr($str,3,6);
    $data = "username=%s&password=%s&mobile=%s&content=%s";
    $url="http://120.55.248.18/smsSend.do?";
    $name = "SYLJ";
    $pwd  = md5("iK8eH5xX");
    $pass = md5($name.$pwd);
    $to   =  $Mobile;
    // $content = "您的验证码是：".$code."，切勿将验证码泄露于他人。【鸿儒网络】";
    $content = "【优恋精选】您的注册验证码是：".$code."，请在10分钟内填写，切勿将验证码泄露于他人！";
    $content = urlencode($content);
    $rdata = sprintf($data, $name, $pass, $to, $content);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$rdata);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $result = curl_exec($ch);
    curl_close($ch);
    return ['code' => $code, 'data' => $result, 'msg' => ''];
}

// 应用公共文件
/**
 * 将查询字符解析成数组
 * @param $str
 */
function parseToArr($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}
function objToArr($arrParams)
{

    return json_decode(json_encode($arrParams),true);
}
//添加账单明细
function add_account($uid,$eth,$ltc,$btc,$type,$created_at){
	$insert['uid'] = $uid;
	$insert['eth_money'] = $eth;
	$insert['ltc_money'] = $ltc;
	$insert['btc_money'] = $btc;
	$insert['type'] = $type;
	$insert['created_at'] = $created_at;
	db('account')->insert($insert);
}
//添加pro_to_users
function add_pro_to_users($uid,$proid,$type,$re_coin,$reward){
	$insert['uid'] = $uid;
	$insert['order_num'] = order_sn();
	$insert['proid'] = $proid;
	$insert['type'] = $type;
	$insert['created_at'] = time();
	$insert['updated_at'] = time();
	$insert['re_coin'] = $re_coin;
	$insert['reward'] = $reward;
	db('pro_to_users')->insert($insert);
}
#获取最新btc,eth行情
function market($type){
	$type = strtolower($type);
	$url = 'https://api.btctrade.com/api/ticker?coin=';
    $data = file_get_contents($url.$type);
    $data = json_decode($data,true);
    // dump($data);exit;
    // echo $data;exit;
    return $data['last'];
    /*
返回结果示例：
{"high":0,"low":0,"buy":1850,"sell":1851.1,"last":1851.1,"vol":10000,"time":1420711226}
返回结果说明：
high: 最高价
low: 最低价
buy: 买一价
sell: 卖一价
last: 最新成交价
vol: 成交量(最近的24小时)
time: 返回数据时服务器时间
    */
}


//随机生成唯一订单号
function order_sn(){
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    return (String) $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
}