<?php

namespace Open1688;

class OAuth
{
    protected $appKey;

    protected $appSecret;

    protected $httpClient;

    public function __construct($appKey, $appSecret)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->httpClient = new \GuzzleHttp\Client();
    }

    public function getAuthUrl($redirectUri, $state)
    {
        $authUrl = "https://auth.1688.com/oauth/authorize?client_id={$this->appKey}&site=1688&redirect_uri={$redirectUri}&state={$state}";
        return $authUrl;
    }

    public function getAccessToken($code, $redirectUri, $needRefreshToken = true)
    {
        $tokenUrl = "https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/{$this->appKey}";
        $response = $this->httpClient->post($tokenUrl, [
            'query' => [
                'grant_type' => 'authorization_code',
                'need_refresh_token' => $needRefreshToken ? 'true' : 'false',
                'client_id' => $this->appKey,
                'client_secret' => $this->appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]
        ]);
        $result = $response->getBody()->getContents();
        return json_decode($result, true);
    }

    public function refreshToken($refreshToken)
    {
        $tokenUrl = "https://gw.open.1688.com/openapi/param2/1/system.oauth2/getToken/{$this->appKey}";
        $response = $this->httpClient->post($tokenUrl, [
            'query' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->appKey,
                'client_secret' => $this->appSecret,
                'refresh_token' => $refreshToken,
            ]
        ]);
        $result = $response->getBody()->getContents();
        return json_decode($result, true);
    }
}
