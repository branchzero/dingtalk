<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) 张铭阳 <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyDingTalk\Workflow;

use Carbon\Carbon;
use EasyDingTalk\Kernel\BaseClient;

class Client extends BaseClient
{
    /**
     * 获取指定用户可见的审批表单列表
     *
     * @param string|null $userId
     * @param int         $offset
     * @param int         $size
     *
     * @return mixed
     */
    public function userVisibleTemplates($userId = null, $nextToken = 0, $maxResults = 100)
    {
        return $this->client->get('workflow/processes/userVisibilities/templates', ['query' => ['userId' => $userId, 'nextToken' => $nextToken, 'maxResults' => $maxResults]]);
    }

    /**
     * 获取当前企业所有可管理的表单
     *
     * @param string $userId
     *
     * @return mixed
     */
    public function managementsTemplates($userId)
    {
        return $this->client->get('workflow/processes/managements/templates', ['query' => ['userId' => $userId]]);
    }

    /**
     * 获取审批实例ID列表
     *
     * @param string|null $processCode
     * @param array       $params
     *
     * @return mixed
     */
    public function queryInstance($processCode = null, $params = [])
    {
        $params = array_merge(['processCode' => $processCode], $params);
        if (!array_key_exists('startTime', $params)) {
            $params['startTime'] = Carbon::now()->subDays(30)->getTimestampMs();
        }
        if (!array_key_exists('nextToken', $params)) {
            $params['nextToken'] = 0;
        }
        if (!array_key_exists('maxResults', $params)) {
            $params['maxResults'] = 20;
        }

        return $this->client->postJson('workflow/processes/instanceIds/query', $params);
    }

    /**
     * 获取单个审批实例详情
     *
     * @param string $instaceCode
     *
     * @return mixed
     */
    public function showInstance($instaceCode)
    {
        return $this->client->get('workflow/processInstances', ['query' => ['processInstanceId' => $instaceCode]]);
    }

    /**
     * 获取用户待审批数量
     *
     * @param string $userId
     *
     * @return mixed
     */
    public function todoTaskCount($userId)
    {
        return $this->client->get('workflow/processes/todoTasks/numbers', ['query' => ['userId' => $userId]]);
    }
}
