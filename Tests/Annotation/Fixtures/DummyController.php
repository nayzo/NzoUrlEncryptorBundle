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

namespace Nzo\UrlEncryptorBundle\Tests\Annotation\Fixtures;

use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;

class DummyController
{
    #[ParamDecryptor(['id'])]
    public function __invoke(): void
    {
    }

    #[ParamDecryptor(['id'])]
    public function decrypt(): void
    {
    }

    #[ParamDecryptor(params: ['id'])]
    public function decryptWithNamedArgument(): void
    {
    }

    #[ParamDecryptor(['id'])]
    #[ParamDecryptor(['token'])]
    public function decryptWithRepeatedAttribute(): void
    {
    }

    #[ParamDecryptor('id')]
    public function decryptWithASingleName(): void
    {
    }

    #[ParamDecryptor(['id'], context: 'invoice')]
    public function decryptWithContext(): void
    {
    }

    #[ParamEncryptor(['id'])]
    public function encrypt(): void
    {
    }

    #[ParamEncryptor(['id'], context: 'invoice')]
    public function encryptWithContext(): void
    {
    }

    #[ParamDecryptor(['id'])]
    #[ParamEncryptor(['name'])]
    public function decryptAndEncrypt(): void
    {
    }

    public function withoutAttribute(): void
    {
    }

    /**
     * @ParamDecryptor({"id"})
     */
    public function decryptWithDocBlockAnnotation(): void
    {
    }
}
