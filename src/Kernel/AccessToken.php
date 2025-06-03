<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) 张铭阳 <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyDingTalk\Kernel;

use EasyDingTalk\Kernel\Exceptions\InvalidArgumentException;
use EasyDingTalk\Kernel\Exceptions\InvalidCredentialsException;
use EasyDingTalk\Kernel\Http\Client;
use Overtrue\Http\Traits\ResponseCastable;

use function EasyDingTalk\tap;

class AccessToken
{
    use Concerns\InteractsWithCache, ResponseCastable;

    /**
     * @var \EasyDingTalk\Application
     */
    protected $app;

    /**
     * AccessToken constructor.
     *
     * @param \EasyDingTalk\Application
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 获取钉钉 AccessToken
     *
     * @return array
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get()
    {
        if ($value = $this->getCache()->get($this->cacheFor())) {
            return $value;
        }

        return $this->refresh();
    }

    /**
     * 获取 AccessToken
     *
     * @return string
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getToken()
    {
        return $this->get()['access_token'] ?? $this->get()['accessToken'] ?? null;
    }

    /**
     * 刷新钉钉 AccessToken
     *
     * @return array
     */
    public function refresh()
    {
        $mode = $this->app['config']->get('mode');
        $client = (new Client($this->app));
        if ($mode === 0) {
            $response = $client->requestRaw('gettoken', 'GET', ['query' => [
                'appKey'    => $this->app['config']->get('app_key'),
                'appSecret' => $this->app['config']->get('app_secret'),
            ]]);
        } elseif ($mode) {
            $response = $client->gateway()->requestRaw('v1.0/oauth2/corpAccessToken', 'POST', ['json' => [
                'suiteKey'    => $this->app['config']->get('app_key'),
                'suiteSecret' => $this->app['config']->get('app_secret'),
                'authCorpId'  => $this->app['config']->get('auth_corp_id'),
                'suiteTicket' => $this->app->getSuiteTicket()
            ]]);
        } elseif ($mode) {
            // TODO: test
            $response = $client->requestRaw('sns/gettoken', 'GET', ['query' => [
                'appKey'    => $this->app['config']->get('app_key'),
                'appSecret' => $this->app['config']->get('app_secret'),
            ]]);
        } else {
            throw new InvalidArgumentException('mode is not supported:' . $mode);
        }

        return tap($this->castResponseToType($response, 'array'), function ($value) {
            if ((!array_key_exists('accessToken', $value) && !array_key_exists('errcode', $value))
                || ((array_key_exists('access_token', $value) || (array_key_exists('errmsg', $value)) && 0 !== $value['errcode']))
            ) {
                throw new InvalidCredentialsException(json_encode($value));
            }
            $this->getCache()->set($this->cacheFor(), $value, $value['expires_in'] ?? $value['expiresIn'] ?? 7200);
        });
    }

    /**
     * 缓存 Key
     *
     * @return string
     */
    protected function cacheFor()
    {
        $mode = $this->app['config']->get('mode');
        if ($mode === 0) {
            $fmt = 'access_token.%s';
        } elseif ($mode) {
            $fmt = 'access_token.%s.corp_id.%s';
        } elseif ($mode) {
            $fmt = 'access_token.%s';
        } else {
            throw new InvalidArgumentException('mode is not supported:' . $mode);
        }

        return sprintf($fmt, $this->app['config']->get('app_key'), $this->app['config']->get('auth_corp_id'));
    }
}
