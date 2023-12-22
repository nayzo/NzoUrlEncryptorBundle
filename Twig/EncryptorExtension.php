<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Twig;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EncryptorExtension extends AbstractExtension
{
    private $encryptor;

    public function __construct(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function getFilters(): array
    {
        return array(
            new TwigFilter('nzo_encrypt', array($this, 'encryptFilter')),
            new TwigFilter('nzo_decrypt', array($this, 'decryptFilter')),
        );
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('nzo_encrypt', array($this, 'encryptFunction')),
            new TwigFunction('nzo_decrypt', array($this, 'decryptFunction')),
        );
    }

    /**
     * @param string $key
     * @return string
     */
    public function encryptFilter($key)
    {
        return $this->encryptor->encrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function decryptFilter($key)
    {
        return $this->encryptor->decrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function encryptFunction($key)
    {
        return $this->encryptor->encrypt($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public function decryptFunction($key)
    {
        return $this->encryptor->decrypt($key);
    }
}
