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

namespace Nzo\UrlEncryptorBundle\Annotations;

use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Symfony\Component\HttpKernel\Attribute\RecoverableAttribute;

/**
 * Lists the request parameters of a controller action that must be decrypted before the action runs.
 *
 * Since Symfony 8.2, an action can receive the failure in a "?DecryptionException $error = null" argument instead of
 * the 404 response. Before Symfony 8.2, RecoverableAttribute does not exist and is never read: nothing changes.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
#[RecoverableAttribute(DecryptionException::class)]
class ParamDecryptor
{
    /**
     * @var list<string>
     */
    private array $params;

    /**
     * @param string[]|string $params  The names of the parameters
     * @param string          $context The context the values are bound to, see Encryptor::encrypt()
     */
    public function __construct(
        array|string $params = [],
        private string $context = '',
    ) {
        $params = array_values((array) $params);
        foreach ($params as $param) {
            if (!\is_string($param)) {
                throw new \InvalidArgumentException(sprintf('NzoEncryptor: "%s" expects the names of the parameters, "%s" given.', self::class, get_debug_type($param)));
            }
        }

        $this->params = $params;
    }

    /**
     * @return list<string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
