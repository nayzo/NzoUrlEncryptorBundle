<?php

/*
 * UrlEncryptor file.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\UrlEncryptor;

/**
 * Class UrlEncryptor
 * @package Nzo\UrlEncryptorBundle\UrlEncryptor
 */
class UrlEncryptor
{
    const HASH_ALGORITHM = 'sha256';

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var bool
     */
    private $base64Encode;

    /**
     * @var bool
     */
    private $randomPseudoBytes;

    /**
     * @var string
     */
    private $cipherAlgorithm;

    /**
     * @var string
     */
    private $iv;

    /**
     * UrlEncryptor constructor.
     *
     * @param string $secretKey
     * @param bool $base64Encode
     * @param bool $randomPseudoBytes
     * @param string $cipherAlgorithm
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($secretKey, $base64Encode, $randomPseudoBytes, $cipherAlgorithm)
    {
        $this->secretKey = $secretKey;
        $this->base64Encode = $base64Encode;
        $this->randomPseudoBytes = $randomPseudoBytes;
        $this->cipherAlgorithm = $cipherAlgorithm;
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
     * @return string
     */
    public function encrypt($plainText)
    {
        $encrypted = openssl_encrypt($plainText, $this->cipherAlgorithm, $this->secretKey, OPENSSL_RAW_DATA, $this->iv);

        return $this->base64Encode ? $this->base64UrlEncode($encrypted) : $encrypted;
    }

    /**
     * @param string $encrypted
     * @return string
     */
    public function decrypt($encrypted)
    {
        $decrypted = openssl_decrypt(
            $this->base64Encode ? $this->base64UrlDecode($encrypted) : $encrypted,
            $this->cipherAlgorithm,
            $this->secretKey,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        return trim($decrypted);
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
