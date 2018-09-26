<?php

/*
 * UrlEncryptorExtension file.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Twig;

use Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor;

/**
 * Class UrlEncryptorExtension
 * @package Nzo\UrlEncryptorBundle\Twig
 */
class UrlEncryptorExtension extends \Twig_Extension
{
    /**
     * @var UrlEncryptor
     */
    private $urlencryptor;

    /**
     * UrlEncryptorExtension constructor.
     * @param UrlEncryptor $urlencryptor
     */
    public function __construct(UrlEncryptor $urlencryptor)
    {
        $this->urlencryptor = $urlencryptor;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('urlencrypt', array($this, 'urlencryptFilter')),
            new \Twig_SimpleFilter('urldecrypt', array($this, 'urldecryptFilter')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('nzoEncrypt', array($this, 'nzoEncryptFunction')),
            new \Twig_SimpleFunction('nzoDecrypt', array($this, 'nzoDecryptFunction')),
        );
    }

    /**
     * @param string $key
     * @return string
     */
    public function urlencryptFilter($key)
    {
        return $this->urlencryptor->encrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function urldecryptFilter($key)
    {
        return $this->urlencryptor->decrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function nzoEncryptFunction($key)
    {
        return $this->urlencryptor->encrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function nzoDecryptFunction($key)
    {
        return $this->urlencryptor->decrypt($key);
    }

    public function getName()
    {
        return 'nzo_urlencryptor_extension';
    }
}
