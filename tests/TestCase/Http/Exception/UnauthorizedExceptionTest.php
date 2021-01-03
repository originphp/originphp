<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\Exception;

use Origin\Http\Exception\UnauthorizedException;

class UnauthorizedExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $exception = new UnauthorizedException();
        $this->assertEquals(401, $exception->getCode());
        $this->assertEquals('Unauthorized', $exception->getMessage());
    }
}
