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

class EncryptorTest extends TestCase
{
    private const CIPHER_ALGORITHM = 'aes-256-ctr';
    private const SECRET_KEY = 'encryptionKeyText';
    private const SECRET_IV = 'encryptionIvText';
    private const PLAIN_TEXT = 'plain_text';

    // Outputs of the version 6.5.0 for PLAIN_TEXT, with the key and the IV above
    private const DETERMINISTIC_OUTPUT_OF_6_5 = '2M1MYc5Kxj850Z8zTndblfsbYf9p15-mVj0';
    private const RANDOM_OUTPUT_OF_6_5 = 'mX_zY5loFSGtK-FUhPZDsy2nuPOGbhD1MDE';

    public function testRandomModeGivesADifferentOutputForEveryEncryption(): void
    {
        $encryptor = $this->createEncryptor(true);

        $first = $encryptor->encrypt(self::PLAIN_TEXT);
        $second = $encryptor->encrypt(self::PLAIN_TEXT);

        $this->assertNotSame($first, $second);
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($first));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($second));
    }

    public function testRandomModeNeverEncryptsTwoValuesWithTheSameIv(): void
    {
        $encryptor = $this->createEncryptor(true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER_ALGORITHM);

        $ivs = [];
        for ($i = 0; $i < 50; ++$i) {
            $ivs[] = substr($this->decode($encryptor->encrypt('value-' . $i)), 0, $ivLength);
        }

        $this->assertCount(50, array_unique($ivs));
    }

    public function testDeterministicModeGivesTheSameOutputForTheSameInput(): void
    {
        $encryptor = $this->createEncryptor(false);

        $this->assertSame($encryptor->encrypt(self::PLAIN_TEXT), $encryptor->encrypt(self::PLAIN_TEXT));
        $this->assertSame($encryptor->encrypt(self::PLAIN_TEXT), $this->createEncryptor(false)->encrypt(self::PLAIN_TEXT));
        $this->assertNotSame($encryptor->encrypt(self::PLAIN_TEXT), $encryptor->encrypt('another_text'));
    }

    public function testDeterministicOutputIsTheOneOfThePreviousVersion(): void
    {
        $this->assertSame(self::DETERMINISTIC_OUTPUT_OF_6_5, $this->createEncryptor(false)->encrypt(self::PLAIN_TEXT));
    }

    /**
     * @dataProvider provideOutputsOfThePreviousVersion
     */
    public function testValueEncryptedByThePreviousVersionIsStillDecrypted(string $encrypted, bool $randomPseudoBytes): void
    {
        $this->assertSame(self::PLAIN_TEXT, $this->createEncryptor($randomPseudoBytes)->decrypt($encrypted));
    }

    public function provideOutputsOfThePreviousVersion(): \Iterator
    {
        yield 'deterministic' => [self::DETERMINISTIC_OUTPUT_OF_6_5, false];
        yield 'random' => [self::RANDOM_OUTPUT_OF_6_5, true];
        // The IV travels with the value: the mode it was encrypted in does not matter to read it back
        yield 'random, read in deterministic mode' => [self::RANDOM_OUTPUT_OF_6_5, false];
    }

    public function testOutputIsSafeInAnUrl(): void
    {
        $encryptor = $this->createEncryptor(true);

        for ($length = 1; $length <= 40; ++$length) {
            $plainText = str_repeat('x', $length);
            $encrypted = $encryptor->encrypt($plainText);

            $this->assertSame(1, preg_match('/^[A-Za-z0-9_-]+$/', $encrypted));
            $this->assertSame($plainText, $encryptor->decrypt($encrypted));
        }
    }

    private function createEncryptor(bool $randomPseudoBytes): Encryptor
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, $randomPseudoBytes);
        $encryptor->setSecretIv(self::SECRET_IV);

        return $encryptor;
    }

    private function decode(string $encrypted): string
    {
        return (string) base64_decode(strtr($encrypted, '-_', '+/'), true);
    }
}
