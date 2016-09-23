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
    private $secret;
    private $iv;

    public function __construct($secret)
    {
        $this->secret = $secret;
        $mod = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $this->iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($mod), MCRYPT_RAND);
    }

    /**
     * @param string $plainText
     * @return string
     */
    public function encrypt($plainText)
    {
        $encrypted = mcrypt_encrypt(MCRYPT_3DES, $this->secret, $plainText, MCRYPT_MODE_ECB, $this->iv);

        return $this->base64UrlEncode($encrypted);
    }

    /**
     * @param string $encrypted
     * @return string
     */
    public function decrypt($encrypted)
    {

        $decrypted = mcrypt_decrypt(MCRYPT_3DES, $this->secret, $this->base64UrlDecode($encrypted), MCRYPT_MODE_ECB, $this->iv);

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
