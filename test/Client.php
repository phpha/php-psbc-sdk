<?php
require_once './vendor/autoload.php';

use Psbc\sdk\Gateway;

// 请求接口
try {
    $result = (new Gateway([
        // 请求地址
        'reqUrl' => 'http://wap.dev.psbc.com/sop-h5/biz/unionpay/%s.htm?partnerTxSriNo=%s',
        // 应用ID
        'appId' => '961925472724332544001',
        // 合作方编号
        'mchId' => 'testMerchant001',
        // 初始化向量|固定
        'iv' => 'UISwD9fW6cFh9SNS',
        // 开放平台公钥
        'sopPublicKey' => '04CABE03249C94BDC8A6A4440DA1B2ADFACF73F4340E5F1B9A76463694B44C2E5600A9BEAA035739383C292CF9F1C4695FAAC7963CD5033D5D647A6B1EBE78EC6A',
        // 商户公钥
        'mchPublicKey' => '0493FC9669F3AAC5450284F9E2E54D65AADEF2F8AD77F8DE2F4C167BA2B1244205F2DF671590E841C01AF63AA6F5F2377367D4277CBDB7F1FF5039F55A55EC4BDF',
        // 商户私钥
        'mchPrivateKey' => '1F0E2F085955461A9B87820AFBD513712CAEA89687BE657DE4EC91613BE62D32'
    ]))->request('b2c.gatewaypay.orderQuery', [
        // 业务参数
    ]);
    print_r($result);
} catch (Exception $e) {
    echo $e->getMessage();
}
