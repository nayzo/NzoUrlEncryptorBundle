<?php

namespace Nzo\UrlEncryptorBundle\Tests\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Tests\Annotation\Fixtures\DummyController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AnnotationResolverTest extends TestCase
{
    public function provideDecryptOnKernelController(): \Iterator
    {
        yield ['decryptWithAttribute', true];
        yield ['decryptWithAnnotation', false];
    }

    /**
     * @dataProvider provideDecryptOnKernelController
     */
    public function testDecryptOnKernelController(string $action, bool $needsPhp8): void
    {
        if ($needsPhp8 && PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('At least PHP 8 is needed for this test');
        }

        $encryptor = new Encryptor('foo', 'aes-256-ctr', true, true, true);
        $encryptor->setSecretIv('secret');

        $request = Request::create('/');
        $request->attributes->set('id', $encryptor->encrypt('some_data'));

        $controllerEvent = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new DummyController(), $action],
            $request,
            1
        );

        $sut = new AnnotationResolver($encryptor, new AnnotationReader());
        $sut->onKernelController($controllerEvent);

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    public function provideEncryptOnKernelController(): \Iterator
    {
        yield ['encryptWithAttribute', true];
        yield ['encryptWithAnnotation', false];
    }

    /**
     * @dataProvider provideEncryptOnKernelController
     */
    public function testEncryptOnKernelController(string $action, bool $needsPhp8): void
    {
        if ($needsPhp8 && PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('At least PHP 8 is needed for this test');
        }

        $encryptor = new Encryptor('foo', 'aes-256-ctr', true, true, true);
        $encryptor->setSecretIv('secret');

        $request = Request::create('/');
        $request->attributes->set('id', 'some_data');

        $controllerEvent = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new DummyController(), $action],
            $request,
            1
        );

        $sut = new AnnotationResolver($encryptor, new AnnotationReader());
        $sut->onKernelController($controllerEvent);

        $this->assertSame($encryptor->encrypt('some_data'), $request->attributes->get('id'));
    }
}
