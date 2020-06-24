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
    const CIPHER_ALGORITHM = 'aes-256-ctr';
    const PLAIN_TEXT = 'plain_text';
    const SECRET_KEY = 'encryptionKeyText';
    const SECRET_IV = 'encryptionIvText';
    const BASE64_ENCODE = true;
    const RANDOM_PSEUDO_BYTES = false;

    /**
     * @var UrlEncryptor
     */
    private $urlEncryptor;

    public function setup()
    {
        $this->urlEncryptor = new UrlEncryptor(
            self::SECRET_KEY,
            self::BASE64_ENCODE,
            self::RANDOM_PSEUDO_BYTES,
            self::CIPHER_ALGORITHM
        );

        $this->urlEncryptor->setSecretIv(self::SECRET_IV);
    }

    public function testEncrypt()
    {
        $encrypted = $this->urlEncryptor->encrypt(self::PLAIN_TEXT);
        $decrypted = $this->urlEncryptor->decrypt($encrypted);

        $this->assertEquals($decrypted, self::PLAIN_TEXT);
    }
}
