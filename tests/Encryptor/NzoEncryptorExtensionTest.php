<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Tests\Encryptor;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

class NzoEncryptorExtensionTest extends \PHPUnit_Framework_TestCase
{
    const CIPHER_ALGORITHM = 'aes-256-ctr';
    const PLAIN_TEXT = 'plain_text';
    const SECRET_KEY = 'encryptionKeyText';
    const SECRET_IV = 'encryptionIvText';
    const BASE64_ENCODE = true;
    const FORMAT_BASE64_OUTPUT = true;
    const RANDOM_PSEUDO_BYTES = false;

    private $encryptor;

    public function setup()
    {
        $this->encryptor = new Encryptor(
            self::SECRET_KEY,
            self::CIPHER_ALGORITHM,
            self::BASE64_ENCODE,
            self::FORMAT_BASE64_OUTPUT,
            self::RANDOM_PSEUDO_BYTES
        );

        $this->encryptor->setSecretIv(self::SECRET_IV);
    }

    public function testEncrypt()
    {
        $encrypted = $this->encryptor->encrypt(self::PLAIN_TEXT);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($decrypted, self::PLAIN_TEXT);
    }
}
