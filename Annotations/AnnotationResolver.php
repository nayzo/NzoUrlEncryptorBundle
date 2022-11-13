<?php

/*
 * This file is part of the NzoUrlEncryptorBundle package.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Annotations;

use Doctrine\Common\Annotations\Reader;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class AnnotationResolver
{
    private $reader;
    private $decryptor;

    public function __construct(Reader $reader, Encryptor $decryptor)
    {
        $this->reader = $reader;
        $this->decryptor = $decryptor;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            $objectController = new \ReflectionObject($controller);
            $method = $objectController->getMethod('__invoke');
        } else {
            $objectController = new \ReflectionObject($controller[0]);
            $method = $objectController->getMethod($controller[1]);
        }

        $objectController = new \ReflectionObject($controller[0]);
        $method = $objectController->getMethod($controller[1]);

        // handle php8 annotation
        if (class_exists('ReflectionAttribute')) {
            $annotations = $this->getAnnotation($method);
        } else {
            $annotations = $this->reader->getMethodAnnotations($method);
        }


        foreach ($annotations as $configuration) {
            // handle php8 annotation
            if (class_exists('ReflectionAttribute')) {
                $configuration = $this->handleReflectionAttribute($configuration);
            }

            if ($configuration instanceof ParamEncryptor) {
                if (isset($configuration->params)) {
                    $request = $event->getRequest();
                    foreach ($configuration->params as $param) {
                        if ($request->attributes->has($param)) {
                            $decrypted = $this->decryptor->encrypt($request->attributes->get($param));
                            $request->attributes->set($param, $decrypted);
                        } elseif ($request->request->has($param)) {
                            $decrypted = $this->decryptor->encrypt($request->request->get($param));
                            $request->request->set($param, $decrypted);
                        }
                    }
                }
            } elseif ($configuration instanceof ParamDecryptor) {
                if (isset($configuration->params)) {
                    $request = $event->getRequest();
                    foreach ($configuration->params as $param) {
                        if ($request->attributes->has($param)) {
                            $decrypted = $this->decryptor->decrypt($request->attributes->get($param));
                            $request->attributes->set($param, $decrypted);
                        } elseif ($request->request->has($param)) {
                            $decrypted = $this->decryptor->decrypt($request->request->get($param));
                            $request->request->set($param, $decrypted);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return mixed
     */
    private function handleReflectionAttribute($configuration)
    {
        if ($configuration instanceof \ReflectionAttribute
            && \in_array($configuration->getName(), [ParamEncryptor::class, ParamDecryptor::class])) {

            $class = $configuration->getName();
            $arguments = $configuration->getArguments();
            $customConfiguration = new $class();
            $customConfiguration->params = \is_array($arguments) && [] !== $arguments && \is_array($arguments[0])
                ? $arguments[0]
                : [];

            return $customConfiguration;
        }

        return $configuration;
    }

    /**
     * @return array|mixed
     */
    private function getAnnotation($method)
    {
        return !empty($this->reader->getMethodAnnotations($method))
            ? $this->reader->getMethodAnnotations($method)
            : $method->getAttributes();
    }
}
