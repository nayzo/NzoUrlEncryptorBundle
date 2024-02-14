<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Annotations;

/**
 * @Annotation
 */
#[\Attribute]
class ParamEncryptor
{
    /** @var array  */
    private $params;

    public function __construct(array $params = [])
    {
        if (isset($params['value']) && \is_array($params['value'])) {
            $this->params = $params['value'];
        } elseif (isset($params['params']) && \is_array($params['params'])) {
            $this->params = $params['params'];
        } else {
            $this->params = $params;
        }
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
