<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Test\Job;

use Origin\Service\Service;

class MockService extends Service
{
    public $arg = null;
    public $arg2 = null;
    public function initialize($arg1, $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
    public function execute($param1, $param2)
    {
        return $this->result([
            'success' => true,
            'data' => [
                'param1' => $param1,
                'param2' => $param2,
            ],
        ]);
    }
}
class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialize()
    {
        $service = new MockService('value1', 'value2');
        $this->assertEquals('value1', $service->arg1);
        $this->assertEquals('value2', $service->arg2);
    }

    public function testDispatch()
    {
        $service = new MockService('value1', 'value2');
        $result = $service->dispatch('p1', 'p2');
        $this->assertTrue($result->success);
        $this->assertEquals('p1', $result->data->param1);
        $this->assertEquals('p2', $result->data->param2);
    }
}
