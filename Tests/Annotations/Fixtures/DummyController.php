<?php

namespace Nzo\UrlEncryptorBundle\Tests\Annotations\Fixtures;

use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;
use Symfony\Component\HttpFoundation\Response;

class DummyController
{
    #[ParamDecryptor(['id'])]
    public function decryptWithAttribute()
    {
    }

    #[ParamDecryptor(params: ['id'])]
    public function decryptWithAttributeAndNamedProperty()
    {
    }

    /**
     * @ParamDecryptor({"id"})
     */
    public function decryptWithAnnotation()
    {
    }

    /**
     * @ParamDecryptor(params={"id"})
     */
    public function decryptWithAnnotationAndNamedProperty()
    {
    }

    #[ParamEncryptor(['id'])]
    public function encryptWithAttribute()
    {
    }

    #[ParamEncryptor(params: ['id'])]
    public function encryptWithAttributeAndNamedProperty()
    {
    }

    /**
     * @ParamEncryptor({"id"})
     */
    public function encryptWithAnnotation()
    {
    }

    /**
     * @ParamEncryptor(params={"id"})
     */
    public function encryptWithAnnotationAndNamedProperty()
    {
    }
}
