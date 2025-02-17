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
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class AnnotationResolver
{
    private $encryptor;
    private $reader;

    public function __construct(Encryptor $encryptor, ?AnnotationReader $reader = null)
    {
        $this->encryptor = $encryptor;
        $this->reader = $reader;
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
                if (null !== $configuration->getParams()) {
                    $request = $event->getRequest();
                    foreach ($configuration->getParams() as $param) {
                        if ($request->attributes->has($param)) {
                            $decrypted = $this->encryptor->encrypt($request->attributes->get($param));
                            $request->attributes->set($param, $decrypted);
                        } elseif ($request->request->has($param)) {
                            $decrypted = $this->encryptor->encrypt($request->request->get($param));
                            $request->request->set($param, $decrypted);
                        }
                    }
                }
            } elseif ($configuration instanceof ParamDecryptor) {
                if (null !== $configuration->getParams()) {
                    $request = $event->getRequest();
                    foreach ($configuration->getParams() as $param) {
                        if ($request->attributes->has($param)) {
                            $decrypted = $this->encryptor->decrypt($request->attributes->get($param));
                            $request->attributes->set($param, $decrypted);
                        } elseif ($request->request->has($param)) {
                            $decrypted = $this->encryptor->decrypt($request->request->get($param));
                            $request->request->set($param, $decrypted);
                        }
                    }
                }
            }
        }
    }

    private function handleReflectionAttribute($configuration)
    {
        if ($configuration instanceof \ReflectionAttribute
            && \in_array($configuration->getName(), [ParamEncryptor::class, ParamDecryptor::class], true)) {
            $class = $configuration->getName();
            $arguments = $configuration->getArguments();
            $params = \is_array($arguments) && [] !== $arguments && \is_array($arguments[0]) ? $arguments[0] : [];

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
