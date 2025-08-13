<?php
/**
 * 网关
 * @author mail@phpha.com
 */
declare(strict_types=1);

namespace Psbc\sdk;

use Exception;
use Psbc\sm\RtSm2;
use Psbc\sm\RtSm4;
use Psbc\util\SmSignFormatRS;

class Gateway
{
    // 配置信息
    private $config;

    /**
     * 初始化
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        // 配置信息
        $this->config = $config;
        // 参数校验
        if (empty($config['reqUrl']) || empty($config['appId']) || empty($config['mchId'])
            || empty($config['iv']) || empty($config['sopPublicKey']) || empty($config['mchPrivateKey'])) {
            throw new Exception('配置信息缺失');
        }
    }

    /**
     * 请求接口
     * @param string $apiNo 接口
     * @param array $param 业务参数
     * @param array $extra 扩展参数
     * @return array
     * @throws Exception
     */
    public function request(string $apiNo, array $param, array $extra = []): array
    {
        // 请求ID
        $reqId = Helper::uniqueId();
        // 格式化
        $param = [
            'head' => [
                'partnerTxSriNo' => $reqId,
                'method' => $apiNo,
                'version' => '1',
                'merchantId' => $this->config['mchId'],
                'appID' => $this->config['appId'],
                'reqTime' => date('YmdHis'),
                'accessType' => 'API' // API|H5
            ],
            'body' => $param
        ];
        // SM4秘钥
        $secret = substr(md5($apiNo . Helper::uniqueId()), 8, 16);
        // 请求参数
        $params = [
            'request' => (new RtSm4($secret))->encrypt(json_encode($param), 'sm4-cbc', $this->config['iv'], 'base64'),
            'encryptKey' => (new RtSm2())->doEncrypt($secret, $this->config['sopPublicKey'], C1C2C3),
            'accessToken' => $extra['accessToken'] ?? ''
        ];
        // 生成签名
        $params['signature'] = $this->sign($params, $this->config['mchPrivateKey']);
        // 头信息
        $header = ['Content-Type:application/json;charset=UTF-8'];
        // 请求接口
        $result = Helper::request(sprintf($this->config['reqUrl'], $this->config['mchId'], $reqId), json_encode($params), $header);
        if ($result['err_no'] !== 0 || empty($result['result'])) {
            return Helper::return(1000);
        }
        // 转换格式
        $data = json_decode($result['result'], true);
        if (empty($data['response']) || empty($data['encryptKey']) || empty($data['signature'])) {
            return Helper::return(1001);
        }
        // 验签失败
        if (!$this->verify($data, $this->config['sopPublicKey'])) {
            return Helper::return(1002);
        }
        // SM2解密
        $secret = (new RtSm2())->doDecrypt($data['encryptKey'], $this->config['mchPrivateKey'], true, C1C2C3);
        if (empty($secret)) {
            return Helper::return(1003);
        }
        // SM4解密
        $bizData = (new RtSm4($secret))->decrypt($data['response'], 'sm4-cbc', $this->config['iv'], 'base64');
        if ($bizData === false) {
            return Helper::return(1004);
        }
        // 转换格式
        $bizData = json_decode($bizData, true);
        if (empty($bizData['body']['respCode'])) {
            return Helper::return(1005);
        }
        // 返回
        return Helper::return(0, $bizData['body']);
    }

    /**
     * 异步通知
     * @param array $param
     * @return array
     * @throws Exception
     */
    public function notify(array $param): array
    {
        // 验签失败
        if (!$this->verify($param, $this->config['sopPublicKey'])) {
            return Helper::return(1002);
        }
        // SM2解密
        $secret = (new RtSm2())->doDecrypt($param['encryptKey'], $this->config['mchPrivateKey'], true, C1C2C3);
        if (empty($secret)) {
            return Helper::return(1003);
        }
        // SM4解密
        $bizData = (new RtSm4($secret))->decrypt($param['request'], 'sm4-cbc', $this->config['iv'], 'base64');
        if ($bizData === false) {
            return Helper::return(1004);
        }
        // 转换格式
        $bizData = json_decode($bizData, true);
        if (empty($bizData['body']['respCode'])) {
            return Helper::return(1005);
        }
        // 返回
        return Helper::return(0, $bizData['body']);
    }

    /**
     * 生成签名
     * @param array $param
     * @param string $privateKey
     * @return string
     */
    private function sign(array $param, string $privateKey): string
    {
        // 字符串
        $str = $param['request'] . $param['encryptKey'] . ($param['accessToken'] ?? '');
        // 签名
        $sign = (new RtSm2('base64'))->doSign($str, $privateKey, $this->config['mchId']);
        // 返回
        return SmSignFormatRS::asn1_to_rs($sign);
    }

    /**
     * 校验签名
     * @param array $param
     * @param string $publicKey
     * @return bool
     */
    private function verify(array $param, string $publicKey): bool
    {
        // 请求或响应
        $str = empty($param['response']) ? $param['request'] : $param['response'];
        // 字符串
        $str .= $param['encryptKey'] . ($param['accessToken'] ?? '');
        // 签名处理
        $rs = explode('#', $param['signature']);
        $sign = base64_encode(hex2bin($rs[0]) . hex2bin($rs[1]));
        $sign = SmSignFormatRS::rs_to_asn1($sign);
        // 返回
        return (new RtSm2('base64'))->verifySign($str, $sign, $publicKey, $this->config['mchId']);
    }
}
