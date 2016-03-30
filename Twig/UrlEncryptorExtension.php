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
    private $urlencryptor;

    public function __construct(UrlEncryptor $urlencryptor)
    {
        $this->urlencryptor = $urlencryptor;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFunction('urlencrypt', array($this, 'urlencryptFilter')),
            new \Twig_SimpleFunction('urldecrypt', array($this, 'urldecryptFilter')),
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
