<?php

namespace Open1688;

class Client
{

    protected $baseUri = 'http://gw.open.1688.com/openapi/';

    protected $appKey;

    protected $appSecret;

    protected $accessToken;

    /** @var \GuzzleHttp\Client $client */
    protected $httpClient;

    /** @var \GuzzleHttp\HandlerStack $stack */
    protected $stack = null;

    public function __construct($appKey, $appSecret, $accessToken)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUri,
            'verify' => false,
            'handler' => $this->getStack(),
        ]);
    }

    /**
     * 获取处理堆栈,可自行注入中间件
     * @return \GuzzleHttp\HandlerStack
     */
    public function getStack()
    {
        if (is_null($this->stack)) {
            // 中间件
            $handler = new \GuzzleHttp\Handler\CurlHandler();
            $this->stack = \GuzzleHttp\HandlerStack::create($handler);
        }
        return $this->stack;
    }

    /**
     * 获取签名
     * @param string $urlPath 实际urlPath
     * @param array $params 请求参数
     * @return string
     */
    public function getSign(string $urlPath, array $params): string
    {
        $arr = [];
        foreach ($params as $key => $param) {
            $arr[] = $key . $param;
        }
        sort($arr);
        $signStr = implode('', $arr);
        $signStr = $urlPath . $signStr;
        return strtoupper(hash_hmac("sha1", $signStr, $this->appSecret));
    }

    /**
     * 转换api为实际urlPath
     * @param string $api 阿里api
     * @return string
     */
    public function parseApi($api)
    {
        [$namespace, $info] = explode(':', $api);
        [$name, $version] = explode('-', $info);
        $api = "param2/{$version}/{$namespace}/{$name}/{$this->appKey}";
        return $api;
    }


    /**
     * 准备参数
     * @param string $api 阿里api
     * @param array $params 请求参数
     */
    public function prepareParams(&$api, &$params)
    {
        $urlPath = $this->parseApi($api);
        $params += [
            'access_token' => $this->accessToken,
            '_aop_timestamp' => (new \DateTime())->format('Uv'),
        ];
        foreach ($params as &$param) {
            $param = is_string($param) ? $param : json_encode($param, JSON_UNESCAPED_UNICODE);
        }
        $params['_aop_signature'] = $this->getSign($urlPath, $params);

    }

    /**
     * get请求
     * @param string $api 阿里api
     * @param array $params 请求参数
     * @return array
     */
    public function get(string $api, array $params = []): array
    {
        $this->prepareParams($api, $params);
        $response = $this->httpClient->get($api, [
            'query' => $params,
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        return $result;
    }



    /**
     * post请求
     * @param string $api 阿里api
     * @param array $params 请求参数
     * @return array
     */
    public function post(string $api, array $params = []): array
    {
        $this->prepareParams($api, $params);
        $response = $this->httpClient->post($api, [
            'form_params' => $params,
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        return $result;
    }
}
