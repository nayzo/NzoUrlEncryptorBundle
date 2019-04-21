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
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class UrlEncryptorExtension
 * @package Nzo\UrlEncryptorBundle\Twig
 */
class UrlEncryptorExtension extends AbstractExtension
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
            new TwigFilter('urlencrypt', array($this, 'urlencryptFilter')),
            new TwigFilter('urldecrypt', array($this, 'urldecryptFilter')),
        );
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('nzoEncrypt', array($this, 'nzoEncryptFunction')),
            new TwigFunction('nzoDecrypt', array($this, 'nzoDecryptFunction')),
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
}
