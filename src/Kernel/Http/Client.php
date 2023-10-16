<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) 张铭阳 <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyDingTalk\Kernel\Http;

use GuzzleHttp\Middleware;
use Overtrue\Http\Client as BaseClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client extends BaseClient
{
    /**
     * @var \EasyDingTalk\Application
     */
    protected $app;

    protected static $version;

    protected static $gateway;

    protected static $gatewayList = [
        'default'  => 'https://oapi.dingtalk.com',
        'previous' => 'https://oapi.dingtalk.com',
        'latest'   => 'https://api.dingtalk.com'
    ];

    /**
     * @var array
     */
    protected static $httpConfig = [
        'base_uri' => 'https://oapi.dingtalk.com',
    ];

    /**
     * @param \EasyDingTalk\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;

        parent::__construct(array_merge(static::$httpConfig, $this->app['config']->get('http', [])));
    }

    /**
     * @param array $config
     */
    public function setHttpConfig(array $config)
    {
        static::$httpConfig = array_merge(static::$httpConfig, $config);
    }

    /**
     * @return $this
     */
    public function withAccessTokenMiddleware()
    {
        if (isset($this->getMiddlewares()['access_token'])) {
            return $this;
        }

        $middleware = function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if ($this->app['access_token']) {
                    if (stripos(self::$httpConfig['base_uri'], 'https://api.') !== false) {
                        $request = $request->withHeader('x-acs-dingtalk-access-token', $this->app['access_token']->getToken());
                    } else {
                        parse_str($request->getUri()->getQuery(), $query);

                        $request = $request->withUri(
                            $request->getUri()->withQuery(http_build_query(['access_token' => $this->app['access_token']->getToken()] + $query))
                        );
                    }
                }

                return $handler($request, $options);
            };
        };

        $this->pushMiddleware($middleware, 'access_token');

        return $this;
    }

    /**
     * @return $this
     */
    public function withRetryMiddleware()
    {
        if (isset($this->getMiddlewares()['retry'])) {
            return $this;
        }

        $middleware = Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null) {
            if (is_null($response) || $retries < 1) {
                return false;
            }

            if (in_array(json_decode($response->getBody(), true)['errcode'] ?? null, [40001])) {
                $this->app['access_token']->refresh();

                return true;
            }
        });

        $this->pushMiddleware($middleware, 'retry');

        return $this;
    }

    /**
     * JSON request.
     *
     * @param string       $url
     * @param string|array $data
     * @param array        $query
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function postJson(string $url, array $data = [], array $query = [])
    {
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data]);
    }

    public function request(string $uri, string $method = 'GET', array $options = [], bool $async = false)
    {
        if (stripos(self::$httpConfig['base_uri'], 'https://api.') === 0) {
            $uri = self::$version . '/' . $uri;
            $options['base_uri'] = self::$httpConfig['base_uri'];
        }

        $result = $this->requestRaw($uri, $method, $options, $async);

        $transformer = function ($response) {
            return $this->castResponseToType($response, $this->config->getOption('response_type'));
        };

        return $async ? $result->then($transformer) : $transformer($result);
    }

    public function gateway($gateway = 'latest')
    {
        self::$gateway = $gateway;
        if (!array_key_exists($gateway, self::$gatewayList)) {
            self::$gatewayList[$gateway] = $gateway;
        }
        self::$httpConfig['base_uri'] = self::$gatewayList[$gateway];
        if ($gateway == 'latest') {
            $this->version();
        }

        return $this;
    }

    public function version($version = 'v1.0')
    {
        if (!empty($version)) {
            self::$version = $version;
        }

        return $this;
    }

    public function getVersion()
    {
        return self::$version;
    }

    public function getGateway()
    {
        return self::$gateway;
    }
}
