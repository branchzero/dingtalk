<?php

/*
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyDingTalk\Edu;

use EasyDingTalk\Kernel\BaseClient;

class Client extends BaseClient
{
    /**
     * 获取班级内学生的关系列表
     *
     * @param string $id 部门ID
     *
     * @return mixed
     */
    public function userRelationList($params = [])
    {
        return $this->client->postJson('topapi/edu/user/relation/list', [
            'page_no'   => 1,
            'page_size' => 30
        ], $params);
    }

    /**
     * 获取部门列表
     *
     * @param string $id 部门ID
     *
     * @return mixed
     */
    public function departmentList($params = [])
    {
        return $this->client->postJson('topapi/edu/dept/list', [
            'page_no'   => 1,
            'page_size' => 30
        ], $params);
    }
}
