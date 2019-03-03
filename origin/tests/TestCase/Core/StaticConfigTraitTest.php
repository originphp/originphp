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

namespace Origin\Test\Core;

use Origin\Core\StaticConfigTrait;

class StaticMockObject
{
    use StaticConfigTrait;

    protected static $defaultConfig = [
        'setting' => 'on'
    ];
}

class StaticConfigTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testConfig()
    {
        $this->assertEquals(['setting'=>'on'], StaticMockObject::config());
        $this->assertEquals('on', StaticMockObject::config('setting'));
     
        StaticMockObject::config('key', 'value');
        $this->assertEquals('value', StaticMockObject::config('key'));

        StaticMockObject::config(['foo'=>'bar']);
        $this->assertEquals('bar', StaticMockObject::config('foo'));
        
        $this->assertNull(StaticMockObject::config('bar'));
    }
}