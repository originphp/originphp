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

namespace Origin\Test\Storage\Engine;

use Origin\Storage\Engine\SftpEngine;
use Origin\Test\Storage\Engine\EngineTestTrait;
use phpseclib\Net\SFTP;

include_once 'EngineTestTrait.php';

class SftpEngineTest extends \PHPUnit\Framework\TestCase
{
    use EngineTestTrait;

    protected function setUp(): void
    {
        if (!env('SFTP_USERNAME')) {
            $this->markTestSkipped('SFTP env vars not set');
        }
        if (!class_exists(SFTP::class)) {
            $this->markTestSkipped('phpseclib not installed.');
        }
    }

    public $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine =  new SftpEngine([
                'host' => env('SFTP_HOST'),
                'username' => env('SFTP_USERNAME'),
                'password' => env('SFTP_PASSWORD')
            ]);
        }
        return $this->engine;
    }
    public function testConfig()
    {
        $config = $this->engine()->config();

        $this->assertNotEmpty($config['host']);
        $this->assertNotEmpty($config['username']);
        $this->assertNotEmpty($config['password']);
        $this->assertEquals(22, $config['port']);
        $this->assertNotEmpty($config['root']);
        $this->assertEquals(10, $config['timeout']);
    }
}