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

namespace Nzo\UrlEncryptorBundle\Tests\Functional\Fixtures;

use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemController
{
    #[ParamDecryptor(['id'])]
    public function show(string $id): Response
    {
        return new Response('item ' . $id);
    }

    #[ParamDecryptor(['id'])]
    public function search(Request $request): Response
    {
        return new Response('search ' . $request->query->get('id'));
    }

    #[ParamDecryptor(['id'], context: 'invoice')]
    public function invoice(string $id): Response
    {
        return new Response('invoice ' . $id);
    }

    public function plain(): Response
    {
        return new Response('plain');
    }

    #[ParamEncryptor(['id'])]
    public function link(string $id): Response
    {
        return new Response($id);
    }
}
