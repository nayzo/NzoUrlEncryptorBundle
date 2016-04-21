<?php

namespace Nzo\UrlEncryptorBundle\Annotations;

use Doctrine\Common\Annotations\Reader;
use Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AnnotationResolver
{
    private $reader;
    private $decryptor;

    public function __construct(Reader $reader, UrlEncryptor $decryptor)
    {
        $this->reader = $reader;
        $this->decryptor = $decryptor;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $objectController = new \ReflectionObject($controller[0]);
        $method = $objectController->getMethod($controller[1]);
        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
            if (isset($configuration->params)) {
                $request = $event->getRequest();
                foreach ($configuration->params as $key => $param) {
                    if (is_int($key)) {
                        if ($request->attributes->has($param)) {
                            $decrypted = $this->decryptor->decrypt($request->attributes->get($param));
                            $request->attributes->set($param, $decrypted);
                        } elseif ($request->request->has($param)) {
                            $decrypted = $this->decryptor->decrypt($request->request->get($param));
                            $request->request->set($param, $decrypted);
                        }
                    } else {
                        if ($request->attributes->has($key)) {
                            $request->attributes->set($key, $param);
                        } elseif ($request->request->has($key)) {
                            $request->request->set($key, $param);
                        }
                    }
                }
            }
        }
    }
}
