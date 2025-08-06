<?php
/**
 * 助手类
 * @author mail@phpha.com
 */
declare(strict_types=1);

namespace Psbc\sdk;

class Helper
{
    /**
     * 自定义错误
     */
    private static $error = [
        0 => 'SUCCESS',
        1000 => '请求失败',
        1001 => '响应异常',
        1002 => 'SM2验签失败',
        1003 => 'SM2解密失败',
        1004 => 'SM4解密失败',
        1005 => '解密后参数异常'
    ];

    /**
     * 自定义返回
     * @param int $code
     * @param array $data
     * @param string $msg
     * @return array
     */
    public static function return(int $code = 0, $data = [], string $msg = ''): array
    {
        // 返回
        return [
            'code' => $code,
            'msg' => empty($msg) ? (self::$error[$code] ?? '') : $msg,
            'data' => $data
        ];
    }

    /**
     * 自定义请求
     * @param string $url
     * @param array|string $data
     * @param array $header
     * @param string $filePath
     * @return array
     */
    public static function request(string $url, $data = [], array $header = [], string $filePath = ''): array
    {
        // 当前时间
        $curr_time = microtime(true);
        // 初始化
        $handle = curl_init($url);
        // 参数配置
        $options = [
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
        // POST请求
        // is_array($data) && Content-Type: multipart/form-data
        // is_string($data) && Content-Type: application/x-www-form-urlencoded
        if (!empty($data)) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        // 文件下载
        if (!empty($filePath)) {
            $fileHandle = fopen($filePath, 'w+');
            $options[CURLOPT_FILE] = $fileHandle;
        }
        // 设置参数
        curl_setopt_array($handle, $options);
        // 执行请求
        $result = curl_exec($handle);
        // 状态码
        $http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        // 错误信息
        $err_no = curl_errno($handle);
        // 关闭资源
        curl_close($handle);
        empty($filePath) || fclose($fileHandle);
        // 返回
        return [
            'err_no' => $err_no,
            'http_code' => $http_code,
            'used_time' => sprintf('%.0fms', (microtime(true) - $curr_time) * 1000),
            'result' => $result
        ];
    }

    /**
     * 生成唯一ID
     * @return string
     */
    public static function uniqueId(): string
    {
        // 24位
        [$micro, $second] = explode(' ', microtime());
        $id = sprintf('%s%06d%04d', date('YmdHis', intval($second)), substr($micro, 2, 6), mt_rand(1, 9999));
        // 返回
        return $id;
    }
}
