<?php

namespace think\api\concerns;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;

trait InteractsWithHttp
{
    protected $endpoint = "https://api.topthink.com/";

    protected $appCode;

    protected $handleStack;

    public function __construct($appCode, $handler = null)
    {
        $this->appCode     = $appCode;
        $this->handleStack = HandlerStack::create($handler);
    }

    public function request($method, $uri = '', $options = [])
    {
        $client = $this->createHttpClient();

        $response = $client->request($method, $uri, $options);

        return $this->parseResponse($response);
    }

    protected function parseResponse(ResponseInterface $response)
    {
        $result = $response->getBody()->getContents();

        if (false !== strpos($response->getHeaderLine('Content-Type'), 'application/json')) {
            $result = json_decode($result, true);
        }

        return $result;
    }

    protected function createHttpClient()
    {
        return new Client([
            'base_uri' => $this->endpoint,
            'handler'  => $this->handleStack,
            'headers'  => [
                'Authorization' => "AppCode {$this->appCode}",
                'User-Agent'    => "ThinkApi/1.0",
            ],
            'verify'   => false,
        ]);
    }

}
