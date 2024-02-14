<?php

namespace Nzo\UrlEncryptorBundle\Tests\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;
use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Tests\Annotations\Fixtures\DummyController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AnnotationResolverTest extends TestCase
{
    public function provideDecryptOnKernelController(): array
    {
        return [
            ['decryptWithAttribute', true],
            ['decryptWithAttributeAndNamedProperty', true],
            ['decryptWithAnnotation', false],
            ['decryptWithAnnotationAndNamedProperty', false],
        ];
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
        $encryptor->setSecretIv(null);

        $request = Request::create('/');
        $request->attributes->set('id', $encryptor->encrypt('12'));

        $controllerEvent = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new DummyController(), $action],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $sut = new AnnotationResolver($encryptor, new AnnotationReader());
        $sut->onKernelController($controllerEvent);

        $this->assertSame('12', $request->attributes->get('id'));
    }

    public function provideEncryptOnKernelController(): array
    {
        return [
            ['encryptWithAttribute', true],
            ['encryptWithAttributeAndNamedProperty', true],
            ['encryptWithAnnotation', false],
            ['encryptWithAnnotationAndNamedProperty', false],
        ];
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
        $encryptor->setSecretIv(null);

        $request = Request::create('/');
        $request->attributes->set('id', '12');

        $controllerEvent = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new DummyController(), $action],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $sut = new AnnotationResolver($encryptor, new AnnotationReader());
        $sut->onKernelController($controllerEvent);

        $this->assertSame($encryptor->encrypt('12'), $request->attributes->get('id'));
    }
}
