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

        // Every encryption has its own IV: the value is checked by reading it back
        $this->assertNotSame('some_data', $request->attributes->get('id'));
        $this->assertSame('some_data', $encryptor->decrypt($request->attributes->get('id')));
    }

    public function provideParameterBags(): \Iterator
    {
        yield 'route parameter' => ['attributes'];
        yield 'request body' => ['request'];
        yield 'query string' => ['query'];
    }

    /**
     * @dataProvider provideParameterBags
     */
    public function testDecryptsTheParameterWhereverItIsSent(string $bag): void
    {
        $encryptor = $this->createEncryptor();
        $request = Request::create('/');
        $request->{$bag}->set('id', $encryptor->encrypt('some_data'));

        $this->resolve($encryptor, $request, 'decryptWithAnnotation', true);

        $this->assertSame('some_data', $request->{$bag}->get('id'));
    }

    /**
     * @dataProvider provideParameterBags
     */
    public function testEncryptsTheParameterWhereverItIsSent(string $bag): void
    {
        $encryptor = $this->createEncryptor();
        $request = Request::create('/');
        $request->{$bag}->set('id', 'some_data');

        $this->resolve($encryptor, $request, 'encryptWithAnnotation', true);

        $this->assertNotSame('some_data', $request->{$bag}->get('id'));
        $this->assertSame('some_data', $encryptor->decrypt($request->{$bag}->get('id')));
    }

    public function testQueryStringIsLeftUntouchedByDefault(): void
    {
        $encryptor = $this->createEncryptor();
        $encrypted = $encryptor->encrypt('some_data');
        $request = Request::create('/');
        $request->query->set('id', $encrypted);

        $this->resolve($encryptor, $request, 'decryptWithAnnotation');

        $this->assertSame($encrypted, $request->query->get('id'));
    }

    public function testRouteParameterWinsOverTheRequestBodyAndTheQueryString(): void
    {
        $encryptor = $this->createEncryptor();
        $request = Request::create('/');
        $request->attributes->set('id', $encryptor->encrypt('from_route'));
        $bodyValue = $encryptor->encrypt('from_body');
        $request->request->set('id', $bodyValue);
        $queryValue = $encryptor->encrypt('from_query');
        $request->query->set('id', $queryValue);

        $this->resolve($encryptor, $request, 'decryptWithAnnotation', true);

        $this->assertSame('from_route', $request->attributes->get('id'));
        $this->assertSame($bodyValue, $request->request->get('id'), 'Only the first place the parameter is found in is touched');
        $this->assertSame($queryValue, $request->query->get('id'), 'Only the first place the parameter is found in is touched');
    }

    public function testParameterThatIsNotSentIsIgnored(): void
    {
        $encryptor = $this->createEncryptor();
        $request = Request::create('/');

        $this->resolve($encryptor, $request, 'decryptWithAnnotation', true);

        $this->assertFalse($request->attributes->has('id'));
        $this->assertFalse($request->request->has('id'));
        $this->assertFalse($request->query->has('id'));
    }

    public function testAttributeAcceptsItsParametersByName(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('At least PHP 8 is needed for this test');
        }

        $encryptor = $this->createEncryptor();
        $request = Request::create('/');
        $request->attributes->set('id', $encryptor->encrypt('some_data'));

        $this->resolve($encryptor, $request, 'decryptWithNamedArgument');

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    private function createEncryptor(): Encryptor
    {
        $encryptor = new Encryptor('foo', 'aes-256-ctr', true, true, true);
        $encryptor->setSecretIv('secret');

        return $encryptor;
    }

    private function resolve(Encryptor $encryptor, Request $request, string $action, bool $queryStringParams = false): void
    {
        $controllerEvent = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new DummyController(), $action],
            $request,
            1
        );

        (new AnnotationResolver($encryptor, new AnnotationReader(), $queryStringParams))->onKernelController($controllerEvent);
    }
}
