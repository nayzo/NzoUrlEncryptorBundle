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

class EncryptionException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $cipherAlgorithm, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('NzoEncryptor: The value cannot be encrypted with the cipher algorithm "%s".', $cipherAlgorithm), 0, $previous);
    }
}
