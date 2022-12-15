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
class ParamEncryptor
{
    public $params;

    public function __construct()
    {
    }
}
