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

namespace Nzo\UrlEncryptorBundle\Tests\Functional;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Tests\Functional\Fixtures\TestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Boots a real application: the bundle is configured, wired and called the way an application does it.
 */
class BundleTest extends TestCase
{
    private const SECRET_KEY = 'encryptionKeyText';

    // Output of the version 6.5.0 for "plain_text" with the key above
    private const OUTPUT_OF_6_5 = 'mX_zY5loFSGtK-FUhPZDsy2nuPOGbhD1MDE';

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(sys_get_temp_dir() . '/nzo_url_encryptor');
    }

    public function testEncryptorIsAutowirable(): void
    {
        $kernel = $this->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $this->assertInstanceOf(Encryptor::class, $container->get(Encryptor::class));
        $this->assertSame($container->get('nzo_encryptor'), $container->get(Encryptor::class));
    }

    public function testRouteParameterIsDecryptedBeforeTheControllerRuns(): void
    {
        $kernel = $this->boot();

        $response = $kernel->handle(Request::create('/item/' . $this->encryptor($kernel)->encrypt(42)));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('item 42', $response->getContent());
    }

    public function testModifiedRouteParameterGivesANotFoundResponse(): void
    {
        $kernel = $this->boot();
        $encrypted = $this->encryptor($kernel)->encrypt(42);
        $modified = substr($encrypted, 0, 5) . ('A' === $encrypted[5] ? 'B' : 'A') . substr($encrypted, 6);

        $this->assertSame(404, $kernel->handle(Request::create('/item/' . $modified))->getStatusCode());
        $this->assertSame(404, $kernel->handle(Request::create('/item/42'))->getStatusCode());
    }

    public function testValueOfOneRouteIsRefusedByAnotherWhenTheyHaveAContext(): void
    {
        $kernel = $this->boot();
        $forItems = $this->encryptor($kernel)->encrypt(42);
        $forInvoices = $this->encryptor($kernel)->encrypt(42, 'invoice');

        $this->assertSame('invoice 42', $kernel->handle(Request::create('/invoice/' . $forInvoices))->getContent());
        $this->assertSame(404, $kernel->handle(Request::create('/invoice/' . $forItems))->getStatusCode());
        $this->assertSame(404, $kernel->handle(Request::create('/item/' . $forInvoices))->getStatusCode());
    }

    public function testRouteParameterIsEncryptedBeforeTheControllerRuns(): void
    {
        $kernel = $this->boot();

        $response = $kernel->handle(Request::create('/link/42'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('42', $this->encryptor($kernel)->decrypt($response->getContent()));
    }

    public function testQueryStringParameterIsDecryptedWhenTheOptionIsEnabled(): void
    {
        $kernel = $this->boot(['query_string_params' => true]);

        $response = $kernel->handle(Request::create('/search', 'GET', ['id' => $this->encryptor($kernel)->encrypt(42)]));

        $this->assertSame('search 42', $response->getContent());
    }

    public function testQueryStringParameterIsLeftUntouchedByDefault(): void
    {
        $kernel = $this->boot();
        $encrypted = $this->encryptor($kernel)->encrypt(42);

        $response = $kernel->handle(Request::create('/search', 'GET', ['id' => $encrypted]));

        $this->assertSame('search ' . $encrypted, $response->getContent());
    }

    public function testValueEncryptedByTheVersion6IsReadWhenLegacyDecryptionIsEnabled(): void
    {
        $this->assertSame(404, $this->boot()->handle(Request::create('/item/' . self::OUTPUT_OF_6_5))->getStatusCode());

        $response = $this->boot(['legacy_decryption' => true])->handle(Request::create('/item/' . self::OUTPUT_OF_6_5));

        $this->assertSame('item plain_text', $response->getContent());
    }

    public function testValueEncryptedWithAPreviousKeyIsRead(): void
    {
        $encrypted = (new Encryptor(self::SECRET_KEY))->encrypt(42);
        $kernel = $this->boot(['secret_key' => 'newKey', 'previous_secret_keys' => [self::SECRET_KEY]]);

        $this->assertSame('item 42', $kernel->handle(Request::create('/item/' . $encrypted))->getContent());
        $this->assertTrue($this->encryptor($kernel)->needsReencryption($encrypted));
    }

    public function testKeysCanBeReadFromTheEnvironment(): void
    {
        // Long names: the placeholders that stand for them at compile time are longer than a key may be
        $current = 'NZO_ENCRYPTOR_TEST_CURRENT_SECRET_KEY_READ_FROM_THE_ENVIRONMENT_AT_RUNTIME_ONLY';
        $previous = 'NZO_ENCRYPTOR_TEST_PREVIOUS_SECRET_KEY_READ_FROM_THE_ENVIRONMENT_AT_RUNTIME_ONLY';
        $_ENV[$current] = $_SERVER[$current] = 'newKey';
        $_ENV[$previous] = $_SERVER[$previous] = self::SECRET_KEY;

        try {
            $kernel = $this->boot(['secret_key' => '%env(' . $current . ')%', 'previous_secret_keys' => ['%env(' . $previous . ')%']]);
            $encryptor = $this->encryptor($kernel);

            $this->assertSame('42', (new Encryptor('newKey'))->decrypt($encryptor->encrypt(42)));
            $this->assertSame('42', $encryptor->decrypt((new Encryptor(self::SECRET_KEY))->encrypt(42)));
        } finally {
            unset($_ENV[$current], $_SERVER[$current], $_ENV[$previous], $_SERVER[$previous]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function boot(array $config = []): TestKernel
    {
        $kernel = new TestKernel($config + ['secret_key' => self::SECRET_KEY]);
        $kernel->boot();

        return $kernel;
    }

    private function encryptor(TestKernel $kernel): Encryptor
    {
        return $kernel->getContainer()->get('test.service_container')->get(Encryptor::class);
    }
}
