NzoUrlEncryptorBundle
=====================

[![Build Status](https://travis-ci.org/NAYZO/NzoUrlEncryptorBundle.svg?branch=master)](https://travis-ci.org/NAYZO/NzoUrlEncryptorBundle)

The **NzoUrlEncryptorBundle** is a Symfony2 Bundle used to Encrypt and Decrypt data and variables in the Web application or passed through the ``URL`` to provide more security to the project.
Also it prevent users from reading and modifying sensitive data sent through the ``URL``.


Features include:

- Url Data & parameters Encryption
- Url Data & parameters Decryption
- Access from Twig by ease
- Flexible configuration


Installation
------------

### Through Composer:

Add the following lines in your `composer.json` file:

``` js
"require": {
    "nzo/url-encryptor-bundle": "~1.0"
}
```
Install the bundle:

```
$ composer update
```

### Register the bundle in app/AppKernel.php:

``` php
// app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new Nzo\UrlEncryptorBundle\NzoUrlEncryptorBundle(),
    );
}
```

### Configure your application's config.yml:

The secret option in the configuration must contain a random key string of maximum 8 caracters and minimum of one caracter.

``` yml
# app/config/config.yml

nzo_url_encryptor:
    secret: YourSecretEncryptionKey 
```

Usage
-----

In your twig template use the filter to encrypt the variable in the url:

``` html

 <a href="{{path('my-path-in-the-routing', {'id': MyId | urlencrypt } )}}"> My link </a>

 # if it needed you can use the twig decryption filter:

 <a href="{{path('my-path-in-the-routing', {'id': MyId | urldecrypt } )}}"> My link </a>

```

Also you can ``encrypt`` and ``decrypt`` variables and data using the ``Twig filter``:

``` html
// Encrypt data:

        {{MyVar | urlencrypt }}

 // Decrypt data:

         {{MyVar | urldecrypt }}
```

In the routing.yml:

``` yml
# routing.yml

my-path-in-the-routing:
    path: /my-url/{id}
    defaults: {_controller: MyBundle:MyController:MyFunction}

```

In the controller use the ``decrypt`` function of the service on the encrypted ``id``:

```php
     public function indexAction($id) 
    {
        $MyId = $this->get('nzo_url_encryptor')->decrypt($id);

        //....

    }
```

You can also use the ``encrypt`` function of the service to encrypt your data:

```php
     public function indexAction() 
    {   
        //....
        
        $Encrypted = $this->get('nzo_url_encryptor')->encrypt($data);

        //....

    }
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [Resources/doc/LICENSE](https://github.com/NAYZO/NzoUrlEncryptorBundle/tree/master/Resources/doc/LICENSE)