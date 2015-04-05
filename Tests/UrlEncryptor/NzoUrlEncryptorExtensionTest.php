<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Tests\UrlEncryptor;

use Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor;

class NzoUrlEncryptorExtensionTest extends \PHPUnit_Framework_TestCase
{
    const SECRET = 'secret_text';
    const KEY = 'encryptionKeyWith24Chars';
    private $urlEncryptor;

    public function setup()
    {
        $this->urlEncryptor = new UrlEncryptor(self::KEY);
    }

    public function testEncrypt()
    {
        $encrypted = $this->urlEncryptor->encrypt(self::SECRET);
        $decrypted = $this->urlEncryptor->decrypt($encrypted);

        $this->assertEquals($decrypted, self::SECRET);
    }

}