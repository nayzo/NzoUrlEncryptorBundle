<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Tests\Encryptor;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use PHPUnit\Framework\TestCase;

class NzoEncryptorExtensionTest extends TestCase
{
    public const CIPHER_ALGORITHM = 'aes-256-ctr';
    public const PLAIN_TEXT = 'plain_text';
    public const SECRET_KEY = 'encryptionKeyText';
    public const SECRET_IV = 'encryptionIvText';
    public const BASE64_ENCODE = true;
    public const FORMAT_BASE64_OUTPUT = true;
    public const RANDOM_PSEUDO_BYTES = false;

    private $encryptor;

    public function setup(): void
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

        $this->assertSame(self::PLAIN_TEXT, $decrypted);
    }
}
