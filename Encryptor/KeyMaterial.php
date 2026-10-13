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
 * The keys derived from one secret key: one per purpose, so that none is ever used for two things.
 *
 * @internal
 */
final class KeyMaterial
{
    public function __construct(
        #[\SensitiveParameter] public readonly string $encryptionKey,
        #[\SensitiveParameter] public readonly string $authenticationKey,
        #[\SensitiveParameter] public readonly string $ivKey,
    ) {
    }
}
