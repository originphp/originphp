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

namespace Origin\Test\Console;

use Origin\Console\ConsoleOutput;

class MockConsoleOutput extends ConsoleOutput
{
    public function getStream()
    {
        return $this->stream;
    }
    public function getStyles()
    {
        return $this->styles;
    }
    public function clearStyles()
    {
        $this->styles = [];
    }
    public function getContents()
    {
        $stream =  $this->stream;
        rewind($stream);
        return  stream_get_contents($stream);
    }
}

class ConsoleOutputTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $ConsoleOutput = new MockConsoleOutput();
        $this->assertTrue(is_resource($ConsoleOutput->getStream()));
    }
    public function testWrite()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('hello world');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals('hello world', stream_get_contents($stream));
    }

    public function testTags()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('<unkown>hello world</unkown>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals('<unkown>hello world</unkown>', stream_get_contents($stream));

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('<yellow>hello world</yellow>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals("\033[33mhello world\033[0m", stream_get_contents($stream));
    }

    public function testStyles()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $this->assertEquals($ConsoleOutput->getStyles(), $ConsoleOutput->styles());
        $this->assertEquals(['text' => 'white','background'=>'lightRed'], $ConsoleOutput->styles('error'));
        
        $ConsoleOutput->styles('foo', ['bar']);
        $this->assertEquals(['bar'], $ConsoleOutput->styles('foo'));
        $this->assertNull($ConsoleOutput->styles('nonExistant'));
        $ConsoleOutput->styles('foo', false);
        $this->assertNull($ConsoleOutput->styles('foo'));
    }

    public function testOutput()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->styles('complete', ['background'=>'lightRed','underline'=>true,'text'=>'white']);
        $ConsoleOutput->write('<complete>Test</complete>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals("\033[97;101;4mTest\033[0m", stream_get_contents($stream));
    }

    public function testOutputTypes()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->debug('test');
        $this->assertContains('<debug>DEBUG:</debug> <info>test</info>', $ConsoleOutput->getContents());
      
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->error('test', 'A comment about this error');
        $output = $ConsoleOutput->getContents();
        $this->assertContains('<error>ERROR:</error> <info>test</info>', $output);
        $this->assertContains('A comment about this error', $output);

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->warning('test');
        $this->assertContains('<warning>WARNING:</warning> <info>test</info>', $ConsoleOutput->getContents());
     
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->info('test');
        $this->assertContains('<info>INFO:</info> <info>test</info>', $ConsoleOutput->getContents());
        
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->notice('test');
        $this->assertContains('<notice>NOTICE:</notice> <info>test</info>', $ConsoleOutput->getContents());

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->success('test');
        $this->assertContains('<success>SUCCESS:</success> <info>test</info>', $ConsoleOutput->getContents());

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->clearStyles();

        $ConsoleOutput->critical('test');
        $this->assertContains('<critical>CRITICAL:</critical> <info>test</info>', $ConsoleOutput->getContents());
    }
}
