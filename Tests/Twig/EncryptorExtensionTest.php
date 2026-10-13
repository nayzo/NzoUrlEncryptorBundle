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

namespace Nzo\UrlEncryptorBundle\Tests\Twig;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Nzo\UrlEncryptorBundle\Twig\EncryptorExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

class EncryptorExtensionTest extends TestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new Encryptor('encryptionKeyText');
    }

    /**
     * @dataProvider provideEncryptingTemplates
     */
    public function testValueIsEncrypted(string $template, string $context): void
    {
        $encrypted = $this->render($template, ['value' => 42]);

        $this->assertSame('42', $this->encryptor->decrypt($encrypted, $context));
    }

    public function provideEncryptingTemplates(): \Iterator
    {
        yield 'filter' => ['{{ value | nzo_encrypt }}', ''];
        yield 'function' => ['{{ nzo_encrypt(value) }}', ''];
        yield 'filter with a context' => ["{{ value | nzo_encrypt('invoice') }}", 'invoice'];
        yield 'function with a context' => ["{{ nzo_encrypt(value, 'invoice') }}", 'invoice'];
    }

    /**
     * @dataProvider provideDecryptingTemplates
     */
    public function testValueIsDecrypted(string $template, string $context): void
    {
        $this->assertSame('42', $this->render($template, ['value' => $this->encryptor->encrypt(42, $context)]));
    }

    public function provideDecryptingTemplates(): \Iterator
    {
        yield 'filter' => ['{{ value | nzo_decrypt }}', ''];
        yield 'function' => ['{{ nzo_decrypt(value) }}', ''];
        yield 'filter with a context' => ["{{ value | nzo_decrypt('invoice') }}", 'invoice'];
        yield 'function with a context' => ["{{ nzo_decrypt(value, 'invoice') }}", 'invoice'];
    }

    public function testValueThatCannotBeDecryptedMakesTheRenderingFail(): void
    {
        try {
            $this->render('{{ value | nzo_decrypt }}', ['value' => '42']);
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(DecryptionException::class, $e->getPrevious());

            return;
        }

        $this->fail('The value was decrypted.');
    }

    public function testEncryptedValueNeedsNoEscaping(): void
    {
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $this->render('{{ value | nzo_encrypt }}', ['value' => '<b>']));
    }

    private function render(string $template, array $variables): string
    {
        $twig = new Environment(new ArrayLoader(['template' => $template]), ['strict_variables' => true]);
        $twig->addExtension(new EncryptorExtension($this->encryptor));

        return $twig->render('template', $variables);
    }
}
