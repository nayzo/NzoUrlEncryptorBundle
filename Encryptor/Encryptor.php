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

namespace Nzo\UrlEncryptorBundle\Encryptor;

use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Nzo\UrlEncryptorBundle\Exception\EncryptionException;

/**
 * Encrypts then authenticates (encrypt-then-MAC): a value that was modified, or encrypted with another key,
 * is refused instead of being decrypted to something else.
 *
 * Layout of an encrypted value, before the base64 encoding:
 *
 *     version (1 byte) | IV | cipher text | tag (16 bytes)
 *
 * The tag is an HMAC-SHA256 of the context and of everything that precedes it.
 */
class Encryptor
{
    private const FORMAT_VERSION = "\x01";
    private const HASH_ALGORITHM = 'sha256';
    private const TAG_LENGTH = 16;
    private const SUBKEY_LENGTH = 32;
    private const KEY_PURPOSE = 'nzo-url-encryptor/v1/';

    /**
     * @var list<string> The current key first, then the previous ones
     */
    private array $secretKeys;
    private string $secretIv = '';
    private ?int $ivLength = null;

    /**
     * @var array<int, KeyMaterial>
     */
    private array $keys = [];

    /**
     * @param string[] $previousSecretKeys Keys that were replaced by $secretKey: they still decrypt, they never encrypt
     * @param bool     $legacyDecryption   Whether values encrypted by a version lower than 7 are still decrypted
     */
    public function __construct(
        #[\SensitiveParameter] string $secretKey,
        private string $cipherAlgorithm = 'aes-256-ctr',
        private bool $base64Encode = true,
        private bool $formatBase64Output = true,
        private bool $randomPseudoBytes = true,
        #[\SensitiveParameter] array $previousSecretKeys = [],
        private bool $legacyDecryption = false,
    ) {
        $this->secretKeys = [$secretKey];
        foreach ($previousSecretKeys as $previousSecretKey) {
            if (!\is_string($previousSecretKey)) {
                throw new \InvalidArgumentException(sprintf('NzoEncryptor: A secret key must be a string, "%s" given.', get_debug_type($previousSecretKey)));
            }

            $this->secretKeys[] = $previousSecretKey;
        }

        if (\in_array('', $this->secretKeys, true)) {
            throw new \InvalidArgumentException('NzoEncryptor: A secret key cannot be empty.');
        }
    }

    /**
     * Only used when "random_pseudo_bytes" is disabled, to give the same output for the same input.
     */
    public function setSecretIv(#[\SensitiveParameter] ?string $secretIv): void
    {
        $this->secretIv = (string) $secretIv;
    }

    /**
     * @param string|int|float|\Stringable|null $plainText Null is encrypted as an empty string
     * @param string                            $context   Optional value the result is bound to, such as the identifier
     *                                                     of its owner: decrypt() must be given the same one
     *
     * @throws EncryptionException When the cipher algorithm cannot encrypt the value
     */
    public function encrypt(string|int|float|\Stringable|null $plainText, string $context = ''): string
    {
        $plainText = (string) $plainText;
        $key = $this->key(0);
        $iv = $this->createIv($key, $plainText, $context);

        $cipherText = openssl_encrypt($plainText, $this->cipherAlgorithm, $key->encryptionKey, \OPENSSL_RAW_DATA, $iv);
        if (false === $cipherText) {
            throw new EncryptionException($this->cipherAlgorithm);
        }

        $message = self::FORMAT_VERSION . $iv . $cipherText;

        return $this->encode($message . $this->tag($message, $key, $context));
    }

    /**
     * @param mixed  $encrypted The string returned by encrypt(). Anything else is refused like a wrong value: it often
     *                          comes straight from a request
     * @param string $context   The one given to encrypt()
     *
     * @throws DecryptionException When the value is not one this encryptor produced with this context
     */
    public function decrypt(mixed $encrypted, string $context = ''): string
    {
        $data = $this->decode($encrypted);
        $index = $this->findKey($data, $context, $encrypted);

        if (null !== $index) {
            return $this->open($data, $this->key($index));
        }

        if ($this->legacyDecryption) {
            return $this->decryptLegacy($data);
        }

        throw new DecryptionException();
    }

    /**
     * Whether the value should be encrypted again: it was encrypted with a previous key or by a version lower than 7.
     *
     * @param mixed  $encrypted The string returned by encrypt()
     * @param string $context   The one given to encrypt()
     *
     * @throws DecryptionException When the value is not one this encryptor produced with this context
     */
    public function needsReencryption(mixed $encrypted, string $context = ''): bool
    {
        $data = $this->decode($encrypted);
        $index = $this->findKey($data, $context, $encrypted);

        if (null === $index && !$this->legacyDecryption) {
            throw new DecryptionException();
        }

        return 0 !== $index;
    }

    /**
     * @return int|null The position of the secret key the value was authenticated with, null when there is none
     */
    private function findKey(string $data, string $context, mixed $encrypted): ?int
    {
        if (\strlen($data) < \strlen(self::FORMAT_VERSION) + $this->ivLength() + self::TAG_LENGTH
            || !str_starts_with($data, self::FORMAT_VERSION)
        ) {
            return null;
        }

        // Base64 lets several strings decode to the same bytes: only the one encrypt() returns is accepted
        if ($this->encode($data) !== $encrypted) {
            return null;
        }

        $message = substr($data, 0, -self::TAG_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);

        foreach (array_keys($this->secretKeys) as $index) {
            if (hash_equals($this->tag($message, $this->key($index), $context), $tag)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Decrypts a value whose tag was verified with the given key.
     */
    private function open(string $data, KeyMaterial $key): string
    {
        $ivOffset = \strlen(self::FORMAT_VERSION);
        $iv = substr($data, $ivOffset, $this->ivLength());
        $cipherText = substr($data, $ivOffset + $this->ivLength(), -self::TAG_LENGTH);

        $plainText = openssl_decrypt($cipherText, $this->cipherAlgorithm, $key->encryptionKey, \OPENSSL_RAW_DATA, $iv);
        if (false === $plainText) {
            throw new DecryptionException();
        }

        return $plainText;
    }

    /**
     * Reads a value encrypted by a version lower than 7: the IV, then the cipher text, encrypted with the secret
     * key itself. Nothing authenticates such a value, so a modified one is decrypted to something else.
     */
    private function decryptLegacy(string $data): string
    {
        $iv = substr($data, 0, $this->ivLength());
        $cipherText = substr($data, $this->ivLength());

        // A wrong value makes OpenSSL complain; the exception below is the only signal the caller needs
        $plainText = @openssl_decrypt($cipherText, $this->cipherAlgorithm, $this->secretKeys[0], \OPENSSL_RAW_DATA, $iv);
        if (false === $plainText) {
            throw new DecryptionException();
        }

        return trim($plainText);
    }

    private function tag(string $message, KeyMaterial $key, string $context): string
    {
        // The length prefix keeps the context and the message apart: no other pair gives the same input
        $input = pack('N', \strlen($context)) . $context . $message;

        return substr(hash_hmac(self::HASH_ALGORITHM, $input, $key->authenticationKey, true), 0, self::TAG_LENGTH);
    }

    /**
     * A random IV by default. When "random_pseudo_bytes" is disabled, an IV computed from the secret IV, the
     * context and the plain text: the same input gives the same output, and two different inputs never share an IV.
     */
    private function createIv(KeyMaterial $key, string $plainText, string $context): string
    {
        $ivLength = $this->ivLength();
        if ($ivLength < 1) {
            return '';
        }

        if ($this->randomPseudoBytes) {
            return random_bytes($ivLength);
        }

        $input = pack('N', \strlen($this->secretIv)) . $this->secretIv . pack('N', \strlen($context)) . $context . $plainText;

        return substr(hash_hmac(self::HASH_ALGORITHM, $input, $key->ivKey, true), 0, $ivLength);
    }

    /**
     * The keys are derived on first use and kept: the service can live for many requests.
     */
    private function key(int $index): KeyMaterial
    {
        if (isset($this->keys[$index])) {
            return $this->keys[$index];
        }

        $keyLength = openssl_cipher_key_length($this->cipherAlgorithm);
        if (false === $keyLength || $keyLength < 1) {
            throw $this->unsupportedCipherAlgorithm();
        }

        // The algorithm is part of the derivation: a value is refused, not garbled, once the algorithm changes
        $purpose = self::KEY_PURPOSE . strtolower($this->cipherAlgorithm);
        $secretKey = $this->secretKeys[$index];

        return $this->keys[$index] = new KeyMaterial(
            hash_hkdf(self::HASH_ALGORITHM, $secretKey, $keyLength, $purpose . '/encryption'),
            hash_hkdf(self::HASH_ALGORITHM, $secretKey, self::SUBKEY_LENGTH, $purpose . '/authentication'),
            hash_hkdf(self::HASH_ALGORITHM, $secretKey, self::SUBKEY_LENGTH, $purpose . '/iv')
        );
    }

    private function ivLength(): int
    {
        if (null !== $this->ivLength) {
            return $this->ivLength;
        }

        $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
        if (false === $ivLength || $ivLength > self::SUBKEY_LENGTH) {
            throw $this->unsupportedCipherAlgorithm();
        }

        return $this->ivLength = $ivLength;
    }

    private function unsupportedCipherAlgorithm(): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('NzoEncryptor: Unsupported cipher algorithm "%s".', $this->cipherAlgorithm));
    }

    private function encode(string $data): string
    {
        if (!$this->base64Encode) {
            return $data;
        }

        $encoded = base64_encode($data);

        return $this->formatBase64Output ? rtrim(strtr($encoded, '+/', '-_'), '=') : $encoded;
    }

    private function decode(mixed $encrypted): string
    {
        if (!\is_string($encrypted)) {
            throw new DecryptionException();
        }

        if (!$this->base64Encode) {
            return $encrypted;
        }

        $decoded = base64_decode($this->formatBase64Output ? strtr($encrypted, '-_', '+/') : $encrypted, true);
        if (false === $decoded) {
            throw new DecryptionException();
        }

        return $decoded;
    }
}
