<?php

namespace Nzo\UrlEncryptorBundle\UrlEncryptor;

/**
 * UrlEncryptor.
 *
 * @author Ala Eddine Khefifi <alakhefifi@gmail.com>
 * Website   www.alakhefifi.com
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
        $td = mcrypt_module_open(MCRYPT_DES,"",MCRYPT_MODE_ECB,"");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td,$key,$iv);
        $secret = mdecrypt_generic($td, base64_decode($secret));
        mcrypt_generic_deinit($td);

        if (substr($secret,0,1) != '!')
            return false;

        $secret = substr($secret,1,strlen($secret)-1);
        return unserialize($secret);
    }

    
    public function encrypt($secret)
    {
        $key = $this->secret;  // Clé de 8 caractères max
        $secret = serialize($secret);
        $td = mcrypt_module_open(MCRYPT_DES,"",MCRYPT_MODE_ECB,"");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td,$key,$iv);
        $secret = base64_encode(mcrypt_generic($td, '!'.$secret));
        mcrypt_generic_deinit($td);
        return $secret;
    }
}