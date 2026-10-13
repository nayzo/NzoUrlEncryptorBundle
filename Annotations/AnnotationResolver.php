<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakfpro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class AnnotationResolver
{
    private $encryptor;
    private $reader;
    private $queryStringParams;

    public function __construct(Encryptor $encryptor, ?AnnotationReader $reader = null, bool $queryStringParams = false)
    {
        $this->encryptor = $encryptor;
        $this->reader = $reader;
        $this->queryStringParams = $queryStringParams;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (is_array($controller = $event->getController())) {
            $objectController = new \ReflectionObject($controller[0]);
            $method = $objectController->getMethod($controller[1]);
        } elseif (is_object($controller) && method_exists($controller, '__invoke')) {
            $objectController = new \ReflectionObject($controller);
            $method = $objectController->getMethod('__invoke');
        } else {
            return;
        }

        // Handle PHP8 Attributes
        if (class_exists('ReflectionAttribute')) {
            if ($this->hasAnnotation($method) && !$this->reader instanceof AnnotationReader) {
                throw new \InvalidArgumentException('NzoEncryptor:  Annotation service not loaded, PHP Attributes should be used instead.');
            }
            $annotations = $this->getAnnotation($method);
        } elseif ($this->reader instanceof AnnotationReader) { // Handle Annotation only without Attributes
            $annotations = $this->reader->getMethodAnnotations($method);
        } else {
            throw new \InvalidArgumentException('NzoEncryptor: Doctrine Annotation package must be installed, doctrine/annotations.');
        }

        foreach ($annotations as $configuration) {
            // handle php8 attribute
            if (class_exists('ReflectionAttribute')) {
                $configuration = $this->handleReflectionAttribute($configuration);
            }

            if ($configuration instanceof ParamEncryptor) {
                $this->transformParams($event->getRequest(), $configuration->getParams(), true);
            } elseif ($configuration instanceof ParamDecryptor) {
                $this->transformParams($event->getRequest(), $configuration->getParams(), false);
            }
        }
    }

    /**
     * Each parameter is looked up in the route parameters, then in the request body and, when the
     * "query_string_params" option is enabled, in the query string. Only the first one found is transformed.
     *
     * @param string[] $params
     */
    private function transformParams(Request $request, array $params, bool $encrypt): void
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
                $bag->set($param, $encrypt ? $this->encryptor->encrypt($value) : $this->encryptor->decrypt($value));

                break;
            }
        }
    }

    private function handleReflectionAttribute($configuration)
    {
        if ($configuration instanceof \ReflectionAttribute
            && \in_array($configuration->getName(), [ParamEncryptor::class, ParamDecryptor::class], true)) {
            $class = $configuration->getName();
            $arguments = $configuration->getArguments();
            // First positional argument, or the same one passed by name: #[ParamDecryptor(params: ['id'])]
            $params = $arguments[0] ?? $arguments['params'] ?? [];
            $params = \is_array($params) ? $params : [];

            return new $class($params);
        }

        return $configuration;
    }

    /**
     * @return array|mixed
     */
    private function getAnnotation($method)
    {
        return $this->reader instanceof AnnotationReader && !empty($this->reader->getMethodAnnotations($method))
            ? $this->reader->getMethodAnnotations($method)
            : $method->getAttributes();
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return bool
     */
    private function hasAnnotation($method)
    {
        $docComment = $method->getDocComment();

        return false !== $docComment && (false !== strpos($docComment, '@ParamEncryptor') || false !== strpos($docComment, '@ParamDecryptor'));
    }
}
