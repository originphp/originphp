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

namespace Origin\Test\Console\Exception;

use Origin\Console\Exception\MissingShellException;

class MissingShellExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new MissingShellException('UnkownShell');
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('UnkownShell could not be found.', $exception->getMessage());
    }
}