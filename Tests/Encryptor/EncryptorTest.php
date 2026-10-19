<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nzo\UrlEncryptorBundle\Tests\Encryptor;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Nzo\UrlEncryptorBundle\Exception\EncryptionException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class EncryptorTest extends TestCase
{
    private const CIPHER_ALGORITHM = 'aes-256-ctr';
    private const SECRET_KEY = 'encryptionKeyText';
    private const SECRET_IV = 'encryptionIvText';
    private const PLAIN_TEXT = 'plain_text';

    // Layout of an encrypted value: version (1 byte), IV (16 bytes with AES), cipher text, tag (16 bytes)
    private const IV_OFFSET = 1;
    private const IV_LENGTH = 16;

    // Deterministic outputs for PLAIN_TEXT with the key and the IV above: they must never change
    private const DETERMINISTIC_OUTPUT = 'ARr_qxYPpKBf4rteov0UL7bQ-fomehcSgJKT6TB7CBU8AuNbhJotCZ52Ag';
    private const DETERMINISTIC_OUTPUT_WITH_CONTEXT = 'AV5AI-30JmEnvVhSnPO3woYuv0Fk_Z60Rmd4g1THkqKc6FteG1t7VZnDWQ';

    // Outputs of the version 6.5.0 for PLAIN_TEXT, with the key and the IV above
    private const DETERMINISTIC_OUTPUT_OF_6_5 = '2M1MYc5Kxj850Z8zTndblfsbYf9p15-mVj0';
    private const RANDOM_OUTPUT_OF_6_5 = 'mX_zY5loFSGtK-FUhPZDsy2nuPOGbhD1MDE';

    /**
     * @dataProvider providePlainTexts
     */
    public function testValueIsReadBackExactlyAsItWasEncrypted(string $plainText): void
    {
        foreach ([true, false] as $randomPseudoBytes) {
            $encryptor = $this->createEncryptor($randomPseudoBytes);

            $this->assertSame($plainText, $encryptor->decrypt($encryptor->encrypt($plainText)));
        }
    }

    public function providePlainTexts(): \Iterator
    {
        yield 'text' => [self::PLAIN_TEXT];
        yield 'empty' => [''];
        yield 'one character' => ['1'];
        yield 'surrounding spaces' => ["  spaced \n"];
        yield 'multibyte' => ['clé secrète ✓'];
        yield 'binary' => ["\x00\x01\xff\xfe"];
        yield 'long' => [str_repeat('0123456789', 500)];
    }

    /**
     * @dataProvider provideScalars
     */
    public function testScalarIsEncryptedAsItsStringForm($value, string $expected): void
    {
        $encryptor = $this->createEncryptor();

        $this->assertSame($expected, $encryptor->decrypt($encryptor->encrypt($value)));
    }

    public function provideScalars(): \Iterator
    {
        yield 'null' => [null, ''];
        yield 'integer' => [42, '42'];
        yield 'float' => [1.5, '1.5'];
        yield 'stringable' => [new \Exception('message'), (string) new \Exception('message')];
    }

    /**
     * @dataProvider provideCipherAlgorithms
     */
    public function testCipherAlgorithmIsSupported(string $cipherAlgorithm): void
    {
        foreach ([true, false] as $randomPseudoBytes) {
            $encryptor = new Encryptor(self::SECRET_KEY, $cipherAlgorithm, true, true, $randomPseudoBytes);
            $encryptor->setSecretIv(self::SECRET_IV);

            foreach (['', 'a', str_repeat('a', 40)] as $plainText) {
                $this->assertSame($plainText, $encryptor->decrypt($encryptor->encrypt($plainText)));
            }
        }
    }

    public function provideCipherAlgorithms(): \Iterator
    {
        yield ['aes-256-ctr'];
        yield ['aes-256-cbc'];
        yield ['aes-256-cfb'];
        yield ['aes-256-ofb'];
        yield ['aes-192-cbc'];
        yield ['aes-128-ctr'];
        yield ['aes-256-ecb'];
        yield ['AES-256-CBC'];
    }

    public function testCaseOfTheCipherAlgorithmDoesNotMatter(): void
    {
        $encrypted = (new Encryptor(self::SECRET_KEY, 'AES-256-CTR'))->encrypt(self::PLAIN_TEXT);

        $this->assertSame(self::PLAIN_TEXT, (new Encryptor(self::SECRET_KEY, 'aes-256-ctr'))->decrypt($encrypted));
    }

    public function testEncryptorCannotBeSerialized(): void
    {
        $this->expectException(\BadMethodCallException::class);

        serialize($this->createEncryptor());
    }

    public function testEncryptorCannotBeUnserialized(): void
    {
        $this->expectException(\BadMethodCallException::class);

        unserialize(sprintf('O:%d:"%s":0:{}', \strlen(Encryptor::class), Encryptor::class));
    }

    /**
     * @dataProvider provideDumpers
     */
    public function testDumpDoesNotShowTheKeys(callable $dumper): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, false, ['previousKeyText']);
        $encryptor->setSecretIv(self::SECRET_IV);
        $encryptor->encrypt(self::PLAIN_TEXT);

        $dump = $dumper($encryptor);

        $this->assertStringContainsString(self::CIPHER_ALGORITHM, $dump);
        $this->assertStringNotContainsString(self::SECRET_KEY, $dump);
        $this->assertStringNotContainsString('previousKeyText', $dump);
        $this->assertStringNotContainsString(self::SECRET_IV, $dump);
        $this->assertStringNotContainsString('KeyMaterial', $dump);
        $this->assertStringNotContainsString('Keyring', $dump);
    }

    public function provideDumpers(): \Iterator
    {
        yield 'print_r' => [static fn (Encryptor $encryptor): string => print_r($encryptor, true)];
        yield 'var_export' => [static fn (Encryptor $encryptor): string => var_export($encryptor, true)];
        yield 'var_dump' => [static function (Encryptor $encryptor): string {
            ob_start();
            var_dump($encryptor);

            return (string) ob_get_clean();
        }];
        yield 'dump' => [static fn (Encryptor $encryptor): string => (string) (new CliDumper())->dump((new VarCloner())->cloneVar($encryptor), true)];
    }

    public function testCloneHasItsOwnSecretIv(): void
    {
        $encryptor = $this->createEncryptor(false);
        $clone = clone $encryptor;
        $clone->setSecretIv('anotherIvText');

        $this->assertSame(self::DETERMINISTIC_OUTPUT, $encryptor->encrypt(self::PLAIN_TEXT));
        $this->assertNotSame(self::DETERMINISTIC_OUTPUT, $clone->encrypt(self::PLAIN_TEXT));
        $this->assertSame(self::PLAIN_TEXT, $clone->decrypt($encryptor->encrypt(self::PLAIN_TEXT)));
    }

    public function testRandomModeGivesADifferentOutputForEveryEncryption(): void
    {
        $encryptor = $this->createEncryptor();

        $first = $encryptor->encrypt(self::PLAIN_TEXT);
        $second = $encryptor->encrypt(self::PLAIN_TEXT);

        $this->assertNotSame($first, $second);
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($first));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($second));
    }

    public function testRandomModeNeverEncryptsTwoValuesWithTheSameIv(): void
    {
        $encryptor = $this->createEncryptor();

        $ivs = [];
        for ($i = 0; $i < 50; ++$i) {
            $ivs[] = substr($this->decode($encryptor->encrypt('value-' . $i)), self::IV_OFFSET, self::IV_LENGTH);
        }

        $this->assertCount(50, array_unique($ivs));
    }

    public function testDeterministicModeGivesTheSameOutputForTheSameInput(): void
    {
        $encryptor = $this->createEncryptor(false);

        $this->assertSame($encryptor->encrypt(self::PLAIN_TEXT), $encryptor->encrypt(self::PLAIN_TEXT));
        $this->assertSame($encryptor->encrypt(self::PLAIN_TEXT), $this->createEncryptor(false)->encrypt(self::PLAIN_TEXT));
    }

    public function testDeterministicModeNeverEncryptsTwoDifferentInputsWithTheSameIv(): void
    {
        $encryptor = $this->createEncryptor(false);

        $ivs = [];
        foreach ([[self::PLAIN_TEXT, ''], ['another_text', ''], [self::PLAIN_TEXT, 'context'], ['plain_', 'text']] as [$plainText, $context]) {
            $ivs[] = substr($this->decode($encryptor->encrypt($plainText, $context)), self::IV_OFFSET, self::IV_LENGTH);
        }

        $this->assertCount(4, array_unique($ivs));
    }

    public function testDeterministicOutputDependsOnTheSecretIv(): void
    {
        $encryptor = $this->createEncryptor(false);
        $other = $this->createEncryptor(false);
        $other->setSecretIv('anotherIv');

        $this->assertNotSame($encryptor->encrypt(self::PLAIN_TEXT), $other->encrypt(self::PLAIN_TEXT));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($other->encrypt(self::PLAIN_TEXT)));
    }

    public function testDeterministicOutputNeverChangesBetweenVersions(): void
    {
        $encryptor = $this->createEncryptor(false);

        $this->assertSame(self::DETERMINISTIC_OUTPUT, $encryptor->encrypt(self::PLAIN_TEXT));
        $this->assertSame(self::DETERMINISTIC_OUTPUT_WITH_CONTEXT, $encryptor->encrypt(self::PLAIN_TEXT, 'context'));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt(self::DETERMINISTIC_OUTPUT));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt(self::DETERMINISTIC_OUTPUT_WITH_CONTEXT, 'context'));
    }

    public function testValueIsReadWhateverTheModeItWasEncryptedIn(): void
    {
        $this->assertSame(self::PLAIN_TEXT, $this->createEncryptor(false)->decrypt($this->createEncryptor(true)->encrypt(self::PLAIN_TEXT)));
        $this->assertSame(self::PLAIN_TEXT, $this->createEncryptor(true)->decrypt($this->createEncryptor(false)->encrypt(self::PLAIN_TEXT)));
    }

    public function testOutputIsSafeInAnUrl(): void
    {
        $encryptor = $this->createEncryptor();

        for ($length = 0; $length <= 40; ++$length) {
            $plainText = str_repeat('x', $length);
            $encrypted = $encryptor->encrypt($plainText);

            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encrypted);
            $this->assertSame($plainText, $encryptor->decrypt($encrypted));
        }
    }

    public function testOutputCanBeStandardBase64(): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, false);

        for ($length = 0; $length <= 40; ++$length) {
            $plainText = str_repeat('x', $length);
            $encrypted = $encryptor->encrypt($plainText);

            $this->assertSame($encrypted, base64_encode((string) base64_decode($encrypted, true)));
            $this->assertSame($plainText, $encryptor->decrypt($encrypted));
        }
    }

    public function testOutputCanBeRawBytes(): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, false);
        $encrypted = $encryptor->encrypt(self::PLAIN_TEXT);

        $this->assertSame($this->decode($this->createEncryptor(false)->encrypt(self::PLAIN_TEXT))[0], $encrypted[0]);
        $this->assertSame(1 + self::IV_LENGTH + \strlen(self::PLAIN_TEXT) + 16, \strlen($encrypted));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($encrypted));
    }

    public function testModifyingAnyByteMakesTheValueRefused(): void
    {
        $encryptor = $this->createEncryptor();
        $data = $this->decode($encryptor->encrypt(self::PLAIN_TEXT));

        for ($position = 0, $length = \strlen($data); $position < $length; ++$position) {
            $modified = $data;
            $modified[$position] = \chr(\ord($modified[$position]) ^ 1);

            $this->assertIsRefused($encryptor, $this->encode($modified));
        }
    }

    public function testShortenedOrExtendedValueIsRefused(): void
    {
        $encryptor = $this->createEncryptor();
        $data = $this->decode($encryptor->encrypt(self::PLAIN_TEXT));

        for ($length = 0, $max = \strlen($data); $length < $max; ++$length) {
            $this->assertIsRefused($encryptor, $this->encode(substr($data, 0, $length)));
        }

        $this->assertIsRefused($encryptor, $this->encode($data . "\x00"));
        $this->assertIsRefused($encryptor, $this->encode("\x00" . $data));
    }

    public function testOnlyTheExactStringReturnedByEncryptIsAccepted(): void
    {
        $encryptor = $this->createEncryptor();
        // 35 bytes: the last base64 character carries 2 bits that the decoding ignores
        $encrypted = $encryptor->encrypt('42');
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $last = strpos($alphabet, substr($encrypted, -1));
        $sameBytes = substr($encrypted, 0, -1) . $alphabet[$last ^ 1];

        $this->assertSame($this->decode($encrypted), $this->decode($sameBytes));
        $this->assertIsRefused($encryptor, $sameBytes);
        $this->assertIsRefused($encryptor, $encrypted . '=');
        $this->assertIsRefused($encryptor, ' ' . $encrypted);
        $this->assertIsRefused($encryptor, substr($encrypted, 0, 10) . "\n" . substr($encrypted, 10));
        $this->assertIsRefused($encryptor, strtr($encrypted, '-_', '+/') . '-');
        $this->assertSame('42', $encryptor->decrypt($encrypted));
    }

    /**
     * @dataProvider provideValuesThatWereNeverEncrypted
     */
    public function testValueThatWasNeverEncryptedIsRefused($value): void
    {
        $this->assertIsRefused($this->createEncryptor(), $value);
    }

    public function provideValuesThatWereNeverEncrypted(): \Iterator
    {
        yield 'empty' => [''];
        yield 'plain value' => ['42'];
        yield 'not base64' => ['%%%'];
        yield 'random bytes' => [rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=')];
        yield 'null' => [null];
        yield 'integer' => [42];
        yield 'array' => [['42']];
    }

    public function testValueEncryptedWithAnotherKeyIsRefused(): void
    {
        $encrypted = (new Encryptor('anotherKey'))->encrypt(self::PLAIN_TEXT);

        $this->assertIsRefused($this->createEncryptor(), $encrypted);
    }

    public function testValueEncryptedWithAnotherCipherAlgorithmIsRefused(): void
    {
        $encrypted = (new Encryptor(self::SECRET_KEY, 'aes-256-cfb'))->encrypt(self::PLAIN_TEXT);

        $this->assertIsRefused($this->createEncryptor(), $encrypted);
    }

    public function testContextMustBeTheOneTheValueWasEncryptedWith(): void
    {
        $encryptor = $this->createEncryptor();
        $encrypted = $encryptor->encrypt(self::PLAIN_TEXT, 'user-1');

        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($encrypted, 'user-1'));
        $this->assertIsRefused($encryptor, $encrypted, 'user-2');
        $this->assertIsRefused($encryptor, $encrypted);
        $this->assertIsRefused($encryptor, $encryptor->encrypt(self::PLAIN_TEXT), 'user-1');
    }

    public function testValueEncryptedWithAPreviousKeyIsStillDecrypted(): void
    {
        $encrypted = (new Encryptor('oldKey'))->encrypt(self::PLAIN_TEXT, 'context');
        $older = (new Encryptor('olderKey'))->encrypt(self::PLAIN_TEXT, 'context');
        $encryptor = new Encryptor('newKey', self::CIPHER_ALGORITHM, true, true, true, ['oldKey', 'olderKey']);

        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($encrypted, 'context'));
        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($older, 'context'));
        $this->assertIsRefused($encryptor, $encrypted, 'another-context');
    }

    public function testPreviousKeyNeverEncrypts(): void
    {
        $encryptor = new Encryptor('newKey', self::CIPHER_ALGORITHM, true, true, true, ['oldKey']);
        $encrypted = $encryptor->encrypt(self::PLAIN_TEXT);

        $this->assertSame(self::PLAIN_TEXT, (new Encryptor('newKey'))->decrypt($encrypted));
        $this->assertIsRefused(new Encryptor('oldKey'), $encrypted);
    }

    public function testValueEncryptedWithAPreviousKeyNeedsReencryption(): void
    {
        $encryptor = new Encryptor('newKey', self::CIPHER_ALGORITHM, true, true, true, ['oldKey']);

        $this->assertTrue($encryptor->needsReencryption((new Encryptor('oldKey'))->encrypt(self::PLAIN_TEXT, 'context'), 'context'));
        $this->assertFalse($encryptor->needsReencryption($encryptor->encrypt(self::PLAIN_TEXT, 'context'), 'context'));
    }

    public function testNeedsReencryptionRefusesAValueThatCannotBeDecrypted(): void
    {
        $encryptor = $this->createEncryptor();

        $this->expectException(DecryptionException::class);

        $encryptor->needsReencryption((new Encryptor('anotherKey'))->encrypt(self::PLAIN_TEXT));
    }

    /**
     * @dataProvider provideOutputsOfTheVersion6
     */
    public function testValueEncryptedByTheVersion6IsRefusedByDefault(string $encrypted): void
    {
        $this->assertIsRefused($this->createEncryptor(), $encrypted);
    }

    /**
     * @dataProvider provideOutputsOfTheVersion6
     */
    public function testValueEncryptedByTheVersion6IsDecryptedWhenLegacyDecryptionIsEnabled(string $encrypted): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, true, [], true);

        $this->assertSame(self::PLAIN_TEXT, $encryptor->decrypt($encrypted));
        $this->assertTrue($encryptor->needsReencryption($encrypted));
    }

    public function provideOutputsOfTheVersion6(): \Iterator
    {
        yield 'deterministic' => [self::DETERMINISTIC_OUTPUT_OF_6_5];
        yield 'random' => [self::RANDOM_OUTPUT_OF_6_5];
    }

    public function testLegacyDecryptionKeepsReadingTheNewValues(): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, true, [], true);
        $encrypted = $encryptor->encrypt("  spaced \n", 'context');

        $this->assertSame("  spaced \n", $encryptor->decrypt($encrypted, 'context'));
        $this->assertFalse($encryptor->needsReencryption($encrypted, 'context'));
        $this->assertSame("  spaced \n", $this->createEncryptor()->decrypt($encrypted, 'context'));
    }

    public function testLegacyDecryptionRefusesAValueThatIsNotBase64(): void
    {
        $this->assertIsRefused(new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, true, [], true), '%%%');
    }

    public function testLegacyDecryptionRefusesAValueTheCipherAlgorithmRejects(): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, 'aes-256-cbc', true, true, true, [], true);

        $this->assertIsRefused($encryptor, 'abc');

        $this->expectException(DecryptionException::class);
        $encryptor->needsReencryption('abc');
    }

    public function testKeyLengthReadFromTheNameIsTheOneOfOpenSsl(): void
    {
        if (!\function_exists('openssl_cipher_key_length')) {
            $this->markTestSkipped('PHP 8.2 is needed to ask OpenSSL.');
        }

        $keyLengthFromName = new \ReflectionMethod(Encryptor::class, 'keyLengthFromName');

        $checked = 0;
        foreach (openssl_get_cipher_methods(true) as $cipherAlgorithm) {
            $keyLength = $keyLengthFromName->invoke(null, $cipherAlgorithm);
            // Some listed algorithms cannot be loaded: PHP knows nothing about them, and the bundle refuses them
            $expected = @openssl_cipher_key_length($cipherAlgorithm);
            if (null === $keyLength || false === $expected) {
                continue;
            }

            $this->assertSame($expected, $keyLength, $cipherAlgorithm);
            ++$checked;
        }

        $this->assertGreaterThan(20, $checked);
        $this->assertSame(32, $keyLengthFromName->invoke(null, 'aes-256-ctr'));
        $this->assertSame(16, $keyLengthFromName->invoke(null, 'AES-128-CBC'));
        $this->assertNull($keyLengthFromName->invoke(null, 'des-ede3-cbc'));
        $this->assertNull($keyLengthFromName->invoke(null, 'aes-256-xts'));
        $this->assertNull($keyLengthFromName->invoke(null, 'aes-256-siv'));
    }

    /**
     * @dataProvider provideUnsupportedCipherAlgorithms
     */
    public function testUnsupportedCipherAlgorithmIsRefused(string $cipherAlgorithm, string $exception): void
    {
        $encryptor = new Encryptor(self::SECRET_KEY, $cipherAlgorithm);

        $this->expectException($exception);

        // OpenSSL raises a warning before failing
        @$encryptor->encrypt(self::PLAIN_TEXT);
    }

    public function provideUnsupportedCipherAlgorithms(): \Iterator
    {
        yield 'authenticated mode' => ['aes-256-gcm', EncryptionException::class];
        yield 'unknown' => ['not-a-cipher', \InvalidArgumentException::class];
    }

    /**
     * @dataProvider provideInvalidSecretKeys
     */
    public function testInvalidSecretKeyIsRefusedOnFirstUse(string $secretKey, array $previousSecretKeys): void
    {
        // Created without the keys being known, as when a container image is built
        $encryptor = new Encryptor($secretKey, self::CIPHER_ALGORITHM, true, true, true, $previousSecretKeys);

        foreach (['encrypt', 'decrypt', 'needsReencryption'] as $method) {
            try {
                $encryptor->{$method}(self::PLAIN_TEXT);
                $this->fail(sprintf('%s() accepted the key.', $method));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('A secret key', $e->getMessage());
            }
        }
    }

    public function provideInvalidSecretKeys(): \Iterator
    {
        yield 'empty key' => ['', []];
        yield 'empty previous key' => [self::SECRET_KEY, ['oldKey', '']];
        yield 'previous key that is not a string' => [self::SECRET_KEY, [42]];
    }

    private function assertIsRefused(Encryptor $encryptor, $encrypted, string $context = ''): void
    {
        try {
            $encryptor->decrypt($encrypted, $context);
        } catch (DecryptionException $e) {
            $this->assertSame('NzoEncryptor: The value cannot be decrypted.', $e->getMessage());

            return;
        }

        $this->fail('The value was decrypted.');
    }

    private function createEncryptor(bool $randomPseudoBytes = true): Encryptor
    {
        $encryptor = new Encryptor(self::SECRET_KEY, self::CIPHER_ALGORITHM, true, true, $randomPseudoBytes);
        $encryptor->setSecretIv(self::SECRET_IV);

        return $encryptor;
    }

    private function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function decode(string $encrypted): string
    {
        return (string) base64_decode(strtr($encrypted, '-_', '+/'), true);
    }
}
