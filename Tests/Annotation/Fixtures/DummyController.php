<?php

namespace Nzo\UrlEncryptorBundle\Tests\Annotation\Fixtures;

use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;

class DummyController
{
    #[ParamDecryptor(['id'])]
    public function decryptWithAttribute()
    {
    }

    /**
     * @ParamDecryptor({"id"})
     */
    public function decryptWithAnnotation()
    {
    }

    #[ParamEncryptor(['id'])]
    public function encryptWithAttribute()
    {
    }

    /**
     * @ParamEncryptor({"id"})
     */
    public function encryptWithAnnotation()
    {
    }
}
