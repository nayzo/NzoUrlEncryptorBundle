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

namespace Nzo\UrlEncryptorBundle\Encryptor;

/**
 * The secrets of one encryptor. It is kept outside of the encryptor, so that no dump of the encryptor shows it.
 *
 * @internal
 */
final class Keyring
{
    public string $secretIv = '';

    /**
     * @var array<int, KeyMaterial> Derived on first use, by position of the secret key
     */
    public array $keyMaterials = [];

    /**
     * @param list<string> $secretKeys The current key first, then the previous ones
     */
    public function __construct(
        #[\SensitiveParameter] public readonly array $secretKeys,
    ) {
    }
}
