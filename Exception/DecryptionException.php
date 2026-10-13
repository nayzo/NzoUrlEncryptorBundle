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

namespace Nzo\UrlEncryptorBundle\Exception;

/**
 * The value was not produced by this encryptor, was modified, or was encrypted with another key,
 * another cipher algorithm or another context.
 */
class DecryptionException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(?\Throwable $previous = null)
    {
        // One message for every cause: it must not tell an attacker which check failed
        parent::__construct('NzoEncryptor: The value cannot be decrypted.', 0, $previous);
    }
}
