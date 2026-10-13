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

namespace Nzo\UrlEncryptorBundle\Tests\Annotation;

use Nzo\UrlEncryptorBundle\Annotations\AnnotationResolver;
use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Tests\Annotation\Fixtures\DummyController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AnnotationResolverTest extends TestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new Encryptor('encryptionKeyText');
    }

    /**
     * @dataProvider provideDecryptingActions
     */
    public function testRouteParameterIsDecrypted(string $action): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data'));

        $this->resolve($request, $action);

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    public function provideDecryptingActions(): \Iterator
    {
        yield 'positional argument' => ['decrypt'];
        yield 'named argument' => ['decryptWithNamedArgument'];
        yield 'single name' => ['decryptWithASingleName'];
        yield 'repeated attribute' => ['decryptWithRepeatedAttribute'];
    }

    public function testEveryRepeatedAttributeIsApplied(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data'));
        $request->attributes->set('token', $this->encryptor->encrypt('some_token'));

        $this->resolve($request, 'decryptWithRepeatedAttribute');

        $this->assertSame('some_data', $request->attributes->get('id'));
        $this->assertSame('some_token', $request->attributes->get('token'));
    }

    public function testValueIsDecryptedWithTheContextOfTheAttribute(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data', 'invoice'));

        $this->resolve($request, 'decryptWithContext');

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    /**
     * @dataProvider provideActionsAndContextsThatDoNotMatch
     */
    public function testValueEncryptedWithAnotherContextGivesANotFoundResponse(string $action, string $context): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data', $context));

        $this->expectException(NotFoundHttpException::class);

        $this->resolve($request, $action);
    }

    public function provideActionsAndContextsThatDoNotMatch(): \Iterator
    {
        yield 'value without context' => ['decryptWithContext', ''];
        yield 'value of another context' => ['decryptWithContext', 'user'];
        yield 'action without context' => ['decrypt', 'invoice'];
    }

    public function testValueIsEncryptedWithTheContextOfTheAttribute(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', 'some_data');

        $this->resolve($request, 'encryptWithContext');

        $this->assertSame('some_data', $this->encryptor->decrypt($request->attributes->get('id'), 'invoice'));
    }

    public function testAttributeRefusesWhatIsNotAParameterName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expects the names of the parameters');

        new ParamDecryptor(['id', 42]);
    }

    public function testInvokableControllerIsSupported(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data'));

        $this->dispatch($request, new DummyController(), false);

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    public function testClosureControllerIsSupported(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data'));

        $this->dispatch($request, #[ParamDecryptor(['id'])] static function (): void {}, false);

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    public function testRouteParameterIsEncrypted(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', 'some_data');

        $this->resolve($request, 'encrypt');

        $this->assertNotSame('some_data', $request->attributes->get('id'));
        $this->assertSame('some_data', $this->encryptor->decrypt($request->attributes->get('id')));
    }

    public function testIntegerRouteParameterIsEncrypted(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', 42);

        $this->resolve($request, 'encrypt');

        $this->assertSame('42', $this->encryptor->decrypt($request->attributes->get('id')));
    }

    public function testMissingValueIsNotEncrypted(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', null);

        $this->resolve($request, 'encrypt');

        $this->assertNull($request->attributes->get('id'));
    }

    public function testValueThatIsNotAScalarCannotBeEncrypted(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', ['42']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The parameter "id" cannot be encrypted');

        $this->resolve($request, 'encrypt');
    }

    public function testDecryptorAndEncryptorCanBeCombined(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $this->encryptor->encrypt('some_data'));
        $request->attributes->set('name', 'some_name');

        $this->resolve($request, 'decryptAndEncrypt');

        $this->assertSame('some_data', $request->attributes->get('id'));
        $this->assertSame('some_name', $this->encryptor->decrypt($request->attributes->get('name')));
    }

    public function testRequestBodyParameterIsDecrypted(): void
    {
        $request = Request::create('/', 'POST', ['id' => $this->encryptor->encrypt('some_data')]);

        $this->resolve($request, 'decrypt');

        $this->assertSame('some_data', $request->request->get('id'));
    }

    public function testQueryStringParameterIsDecryptedWhenTheOptionIsEnabled(): void
    {
        $request = Request::create('/', 'GET', ['id' => $this->encryptor->encrypt('some_data')]);

        $this->resolve($request, 'decrypt', true);

        $this->assertSame('some_data', $request->query->get('id'));
    }

    public function testQueryStringParameterIsLeftUntouchedByDefault(): void
    {
        $encrypted = $this->encryptor->encrypt('some_data');
        $request = Request::create('/', 'GET', ['id' => $encrypted]);

        $this->resolve($request, 'decrypt');

        $this->assertSame($encrypted, $request->query->get('id'));
    }

    public function testOnlyTheFirstPlaceTheParameterIsFoundInIsTransformed(): void
    {
        $encrypted = $this->encryptor->encrypt('from_the_query_string');
        $request = Request::create('/?id=' . $encrypted);
        $request->attributes->set('id', $this->encryptor->encrypt('from_the_route'));

        $this->resolve($request, 'decrypt', true);

        $this->assertSame('from_the_route', $request->attributes->get('id'));
        $this->assertSame($encrypted, $request->query->get('id'));
    }

    /**
     * @dataProvider provideValuesThatAreNotDecrypted
     */
    public function testMissingValueIsLeftUntouched(?string $value): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $value);

        $this->resolve($request, 'decrypt');

        $this->assertSame($value, $request->attributes->get('id'));
    }

    public function provideValuesThatAreNotDecrypted(): \Iterator
    {
        yield 'optional route parameter left out' => [null];
        yield 'empty value' => [''];
    }

    public function testParameterThatIsNotInTheRequestIsIgnored(): void
    {
        $request = Request::create('/');

        $this->resolve($request, 'decrypt', true);

        $this->assertFalse($request->attributes->has('id'));
        $this->assertFalse($request->request->has('id'));
        $this->assertFalse($request->query->has('id'));
    }

    /**
     * @dataProvider provideValuesThatCannotBeDecrypted
     */
    public function testValueThatCannotBeDecryptedGivesANotFoundResponse(string $value): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', $value);

        $this->expectException(NotFoundHttpException::class);

        $this->resolve($request, 'decrypt');
    }

    public function provideValuesThatCannotBeDecrypted(): \Iterator
    {
        yield 'plain value' => ['42'];
        yield 'not base64' => ['%%%'];
        yield 'encrypted with another key' => [(new Encryptor('anotherKey'))->encrypt('some_data')];
    }

    public function testActionWithoutAttributeIsLeftUntouched(): void
    {
        $request = Request::create('/');
        $request->attributes->set('id', 'some_data');

        $this->resolve($request, 'withoutAttribute');

        $this->assertSame('some_data', $request->attributes->get('id'));
    }

    public function testDocBlockAnnotationIsRefused(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(DummyController::class . '::decryptWithDocBlockAnnotation()');

        $this->resolve(Request::create('/'), 'decryptWithDocBlockAnnotation');
    }

    private function resolve(Request $request, string $action, bool $queryStringParams = false): void
    {
        $this->dispatch($request, [new DummyController(), $action], $queryStringParams);
    }

    private function dispatch(Request $request, callable $controller, bool $queryStringParams): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        (new AnnotationResolver($this->encryptor, $queryStringParams))->onKernelController($event);
    }
}
