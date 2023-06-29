<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Annotations;

/**
 * @Annotation
 */
#[\Attribute]
class ParamDecryptor
{
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        if (isset($this->params['value']) && \is_array($this->params['value'])) {
            return $this->params['value'];
        }
        
        if (isset($this->params['param']) && \is_array($this->params['param'])) {
            return $this->params['param'];
        }

        return $this->params;
    }
}
