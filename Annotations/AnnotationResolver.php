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

namespace Nzo\UrlEncryptorBundle\Annotations;

use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Exception\DecryptionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnnotationResolver
{
    public function __construct(
        private Encryptor $encryptor,
        private bool $queryStringParams = false,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $attributes = $event->getAttributes();
        $decryptors = $attributes[ParamDecryptor::class] ?? [];
        $encryptors = $attributes[ParamEncryptor::class] ?? [];

        if ([] === $decryptors && [] === $encryptors) {
            $this->assertNoDocBlockAnnotation($event);

            return;
        }

        $request = $event->getRequest();

        foreach ($decryptors as $decryptor) {
            if ($decryptor instanceof ParamDecryptor) {
                $this->transformParams($request, $decryptor->getParams(), $decryptor->getContext(), false);
            }
        }

        foreach ($encryptors as $encryptor) {
            if ($encryptor instanceof ParamEncryptor) {
                $this->transformParams($request, $encryptor->getParams(), $encryptor->getContext(), true);
            }
        }
    }

    /**
     * Each parameter is looked up in the route parameters, then in the request body and, when the
     * "query_string_params" option is enabled, in the query string. Only the first one found is transformed.
     *
     * @param string[] $params
     */
    private function transformParams(Request $request, array $params, string $context, bool $encrypt): void
    {
        $bags = [$request->attributes, $request->request];
        if ($this->queryStringParams) {
            $bags[] = $request->query;
        }

        foreach ($params as $param) {
            foreach ($bags as $bag) {
                if (!$bag->has($param)) {
                    continue;
                }

                $value = $bag->get($param);
                // Nothing to transform: an optional parameter that was left out, or an empty one
                if (null !== $value && '' !== $value) {
                    $bag->set($param, $encrypt ? $this->encrypt($param, $value, $context) : $this->decrypt($value, $context));
                }

                break;
            }
        }
    }

    private function encrypt(string $param, mixed $value, string $context): string
    {
        if (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new \UnexpectedValueException(sprintf('NzoEncryptor: The parameter "%s" cannot be encrypted, a scalar is expected, "%s" given.', $param, get_debug_type($value)));
        }

        return $this->encryptor->encrypt((string) $value, $context);
    }

    private function decrypt(mixed $value, string $context): string
    {
        try {
            return $this->encryptor->decrypt($value, $context);
        } catch (DecryptionException $e) {
            // A value that was modified, or made up, designates nothing
            throw new NotFoundHttpException('Not Found', $e);
        }
    }

    /**
     * The annotations written in a doc block are no longer read: staying silent would hand the encrypted value to
     * the controller.
     */
    private function assertNoDocBlockAnnotation(ControllerEvent $event): void
    {
        $reflector = $event->getControllerReflector();
        $docComment = $reflector->getDocComment();

        if (false !== $docComment && (str_contains($docComment, '@ParamEncryptor') || str_contains($docComment, '@ParamDecryptor'))) {
            $name = $reflector instanceof \ReflectionMethod ? $reflector->class . '::' . $reflector->name : $reflector->name;

            throw new \LogicException(sprintf('NzoEncryptor: The annotations found on "%s()" are no longer supported, use the PHP attributes #[ParamEncryptor] and #[ParamDecryptor] instead.', $name));
        }
    }
}
