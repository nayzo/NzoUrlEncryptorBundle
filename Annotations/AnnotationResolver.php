<?php

/*
 * Configuration file.
 *
 * (c) Ala Eddine Khefifi <alakhefifi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nzo\UrlEncryptorBundle\Annotations;

use Doctrine\Common\Annotations\Reader;
use Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AnnotationResolver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var UrlEncryptor
     */
    private $decryptor;

    /**
     * AnnotationResolver constructor.
     *
     * @param Reader $reader
     * @param UrlEncryptor $decryptor
     */
    public function __construct(Reader $reader, UrlEncryptor $decryptor)
    {
        $this->reader = $reader;
        $this->decryptor = $decryptor;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $objectController = new \ReflectionObject($controller[0]);
        $method = $objectController->getMethod($controller[1]);
        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {

            if ($configuration instanceof ParamEncryptor) {
                if (isset($configuration->params)) {
                    $request = $event->getRequest();
                    foreach ($configuration->params as $key => $param) {
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
                    foreach ($configuration->params as $key => $param) {
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
}
