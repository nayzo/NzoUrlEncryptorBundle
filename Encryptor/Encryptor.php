<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Encryptor;

class Encryptor
{
    private const HASH_ALGORITHM = 'sha256';

    private $secretKey;
    private $cipherAlgorithm;
    private $base64Encode;
    private $formatBase64Output;
    private $randomPseudoBytes;
    private $iv;

    public function __construct(
        string $secretKey,
        string $cipherAlgorithm,
        bool $base64Encode,
        bool $formatBase64Output,
        bool $randomPseudoBytes
    ) {
        $this->secretKey = $secretKey;
        $this->cipherAlgorithm = $cipherAlgorithm;
        $this->base64Encode = $base64Encode;
        $this->formatBase64Output = $formatBase64Output;
        $this->randomPseudoBytes = $randomPseudoBytes;
    }

    /**
     * @param string $secretIv
     */
    public function setSecretIv($secretIv)
    {
        $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
        $secretIv = $this->randomPseudoBytes ? openssl_random_pseudo_bytes($ivLength) : $secretIv;

        $this->iv = substr(
            hash_hmac(self::HASH_ALGORITHM, $secretIv, $this->secretKey, true),
            0,
            $ivLength
        );
    }

    /**
     * @param string $plainText
     *
     * @return string
     */
    public function encrypt($plainText)
    {
        $encrypted = openssl_encrypt($plainText, $this->cipherAlgorithm, $this->secretKey, OPENSSL_RAW_DATA, $this->iv);
        $encrypted = $this->iv . $encrypted;

        return $this->base64Encode ? $this->base64Encode($encrypted) : $encrypted;
    }

    /**
     * @param string $encrypted
     *
     * @return string
     */
    public function decrypt($encrypted)
    {
        $ivLength = openssl_cipher_iv_length($this->cipherAlgorithm);
        $encrypted = $this->base64Encode ? $this->base64Decode($encrypted) : $encrypted;
        $iv = substr($encrypted, 0, $ivLength);
        $raw = substr($encrypted, $ivLength);

        $decrypted = openssl_decrypt(
            $raw,
            $this->cipherAlgorithm,
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return trim($decrypted);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function base64Encode($data)
    {
        if ($this->formatBase64Output) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }

        return base64_encode($data);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function base64Decode($data)
    {
        if ($this->formatBase64Output) {
            return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT), true);
        }

        return base64_decode($data, true);
    }
}
