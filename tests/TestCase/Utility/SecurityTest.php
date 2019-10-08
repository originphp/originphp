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

namespace Origin\Test\Utility;

use Origin\Core\Config;
use Origin\Utility\Security;
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Config::write('Security.pepper', 'B1816172FD2BA98F3AF520EF572E3A47');
    }
    protected function tearDown(): void
    {
        Config::write('Security.pepper', '-----ORIGIN PHP-----');
    }
    public function testHash()
    {
        $plain = 'The quick brown fox jumps over the lazy dog';
        $expected = '2fd4e1c67a2d28fced849ee1bb76e7391b93eb12';
       
        $this->assertEquals($expected, Security::hash($plain, ['type' => 'sha1']));
   
        $expected = 'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592';
        $this->assertEquals($expected, Security::hash($plain));
        
        $expected = '2a70c8107928b49f2c2b64bac4aacb820aef818b';
        $this->assertEquals($expected, Security::hash($plain, ['type' => 'sha1','pepper' => 'OriginPHP']));

        Config::write('Security.pepper', 'OriginPHP');
        $expected = '2a70c8107928b49f2c2b64bac4aacb820aef818b';
        $this->assertEquals($expected, Security::hash($plain, ['type' => 'sha1','pepper' => true]));

        $this->expectException(Exception::class);
        Security::hash($plain, ['type' => 'unkownHashType']);
    }

    public function testHashPassword()
    {
        $result = Security::hashPassword('secret');
        $this->assertStringContainsString('$2y$10', $result);
    }

    /**
     * @depends testHashPassword
     */
    public function testVerifyPassword()
    {
        $result = Security::hashPassword('secret');
        $this->assertTrue(Security::verifyPassword('secret', $result));
    }

    public function testCompare()
    {
        $expected = crypt('12345', '$2a$07$areallylongstringthatwillbeusedasasalt$');
        $correct = crypt('12345', '$2a$07$areallylongstringthatwillbeusedasasalt$');
        $incorrect = crypt('67890', '$2a$07$areallylongstringthatwillbeusedasasalt$');

        $this->assertTrue(Security::compare($expected, $correct));
        $this->assertFalse(Security::compare($expected, $incorrect));
    }

    public function testEncryptDecrypt()
    {
        $plain = 'The quick brown fox jumps over the lazy dog';
        $key = '58024d70eb647a3d0654d5211af2ebfd';
  
        $encrypted = Security::encrypt($plain, $key);
        $decrypted = Security::decrypt($encrypted, $key);
        $this->assertEquals($plain, $decrypted);
        $this->assertNull(Security::decrypt($encrypted, str_replace('7', 'a', $key))); // test wrong key

        $this->expectException(Exception::class);
        Security::decrypt($encrypted, $key.'x');
    }

    public function testEncryptInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        Security::encrypt('foo', 'secret');
    }

    public function testDecryptInvalidKey()
    {
        $key = '58024d70eb647a3d0654d5211af2ebfd';

        $encrypted = Security::encrypt('foo', $key);
        $this->expectException(InvalidArgumentException::class);
        Security::decrypt($encrypted, 'secret');
    }

    public function testGenerateKey()
    {
        $this->assertEquals(32, strlen(Security::generateKey()));
    }

    public function testEncryptInvalidKeyLength()
    {
        $this->expectException(Exception::class);
        Security::encrypt('text', 'invalidkey');
    }

    public function testDecryptInvalidKeyLength()
    {
        $this->expectException(Exception::class);
        Security::decrypt('text', 'invalidkey');
    }

    public function testUUID()
    {
        $this->assertRegExp(
            '/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/',
            Security::uuid()
        );

        $this->assertRegExp(
            '/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/',
            Security::uuid(['timestamp' => true])
        );

        $this->assertRegExp(
            '/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/',
            Security::uuid(['macAddress' => '00:01:01:00:00:00'])
        );

        $this->expectException(InvalidArgumentException::class);
        Security::uuid(['macAddress' => 'example.com']);
    }

    public function testUid()
    {
        $this->assertRegExp('/^([a-zA-Z0-9]*){15}$/', Security::uid());
        $this->assertRegExp('/^([a-zA-Z0-9]*){18}$/', Security::uid(18));
    }

    public function testRandom()
    {
        $this->assertRegExp('/^([a-z0-9]*){18}$/', Security::random());
        $this->assertRegExp('/^([a-z0-9]*){21}$/', Security::random(21));
    }
}
