<?php
namespace Nzo\UrlEncryptorBundle\Twig;

/**
 * UrlEncryptorExtension.
 *
 * @author Ala Eddine Khefifi <alakhefifi@gmail.com>
 * Website   www.alakhefifi.com
 */
class UrlEncryptorExtension extends \Twig_Extension
{
     private $urlencryptor;
     public function __construct(\Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor $urlencryptor) {
        $this->urlencryptor = $urlencryptor;
    }
    
    public function getFilters()
    {
        return array(
            'urlencrypt' => new \Twig_Filter_Method($this, 'urlencryptFilter'),
            'urldecrypt' => new \Twig_Filter_Method($this, 'urldecryptFilter'),
        );
    }

    public function urlencryptFilter($key)
    {
        return $this->urlencryptor->encrypt($key);
    }
    
    public function urldecryptFilter($key)
    {
        return $this->urlencryptor->decrypt($key);
    }

    public function getName()
    {
        return 'nzo_urlencryptor_extension'; 
    }
}