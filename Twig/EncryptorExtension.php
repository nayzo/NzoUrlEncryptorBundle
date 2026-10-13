<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nzo\UrlEncryptorBundle\Twig;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EncryptorExtension extends AbstractExtension
{
    public function __construct(
        private Encryptor $encryptor,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('nzo_encrypt', [$this, 'encryptFilter']),
            new TwigFilter('nzo_decrypt', [$this, 'decryptFilter']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nzo_encrypt', [$this, 'encryptFunction']),
            new TwigFunction('nzo_decrypt', [$this, 'decryptFunction']),
        ];
    }

    public function encryptFilter(string|int|float|\Stringable|null $key, string $context = ''): string
    {
        return $this->encryptor->encrypt($key, $context);
    }

    public function decryptFilter(mixed $key, string $context = ''): string
    {
        return $this->encryptor->decrypt($key, $context);
    }

    public function encryptFunction(string|int|float|\Stringable|null $key, string $context = ''): string
    {
        return $this->encryptor->encrypt($key, $context);
    }

    public function decryptFunction(mixed $key, string $context = ''): string
    {
        return $this->encryptor->decrypt($key, $context);
    }
}
