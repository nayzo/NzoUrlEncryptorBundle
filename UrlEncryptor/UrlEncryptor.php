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

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function decrypt($secret)
    {
        $key = $this->secret;
        $td = mcrypt_module_open(MCRYPT_DES, "", MCRYPT_MODE_ECB, "");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $secret = mdecrypt_generic($td, $this->base64url_decode($secret));
        mcrypt_generic_deinit($td);

        if (substr($secret, 0, 1) != '!')
            return false;

        $secret = substr($secret, 1, strlen($secret) - 1);

        return $secret;
    }


    public function encrypt($secret)
    {
        $key = $this->secret;
        $td = mcrypt_module_open(MCRYPT_DES, "", MCRYPT_MODE_ECB, "");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $secret = $this->base64url_encode(mcrypt_generic($td, '!' . $secret));
        mcrypt_generic_deinit($td);

        return $secret;
    }

    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}