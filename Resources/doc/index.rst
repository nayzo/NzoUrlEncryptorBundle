NzoUrlEncryptorBundle
=====================

[![Build Status](https://travis-ci.org/NAYZO/NzoUrlEncryptorBundle.svg?branch=master)](https://travis-ci.org/NAYZO/NzoUrlEncryptorBundle)
[![Total Downloads](https://poser.pugx.org/nzo/url-encryptor-bundle/downloads)](https://packagist.org/packages/nzo/url-encryptor-bundle)
[![Latest Stable Version](https://poser.pugx.org/nzo/url-encryptor-bundle/v/stable)](https://packagist.org/packages/nzo/url-encryptor-bundle)

The **NzoUrlEncryptorBundle** is a Symfony Bundle used to Encrypt and Decrypt data and variables in the Web application or passed through the ``URL`` to provide more security to the project.
Also it prevent users from reading and modifying sensitive data sent through the ``URL``.


Features include:

- Url Data & parameters Encryption
- Url Data & parameters Decryption
- Data Encryption & Decryption
- Access from Twig by ease
- Flexible configuration
- Compatible php 7.1
- Uses OpenSSL extension


Installation
------------

### Through Composer:

Install the bundle:

```
$ composer require nzo/url-encryptor-bundle
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

Configure your secret encryption key:

``` yml
# app/config/config.yml

nzo_url_encryptor:
    secret_key: YourSecretEncryptionKey    # optional, max length of 100 characters.
    secret_iv:  YourIvEncryptionKey        # optional, max length of 100 characters.
    cipher_algorithm:                      # optional, default: 'aes-256-ctr'
```

Usage
-----

#### In the twig template:

Use the twig extensions filters or functions to ``encrypt`` or ``decrypt`` your data:

``` html
// Filters:

# Encryption:

    <a href="{{path('my-route', {'id': MyId | urlencrypt } )}}"> My link </a>

    {{MyVar | urlencrypt }}

# Decryption:

    <a href="{{path('my-route', {'id': MyId | urldecrypt } )}}"> My link </a>

    {{MyVar | urldecrypt }}


// Functions:

# Encryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzoEncrypt('MyID') } )}}"> My link </a>

    {{ nzoEncrypt(MyVar) }}

# Decryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzoDecrypt('MyID') } )}}"> My link </a>

    {{ nzoDecrypt(MyVar) }}
```

#### In the routing.yml:

``` yml
# routing.yml

my-path-in-the-routing:
    path: /my-url/{id}
    defaults: {_controller: MyBundle:MyController:MyFunction}
```

#### In the controller with annotation service:

Use the annotation service to ``decrypt`` automatically any parameter you want, by using the ``ParamDecryptor`` annotation service and specifying in it all the parameters to be decrypted.

```php
use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
//...

    /**
     * @ParamDecryptor(params={"id", "bar"})
     */
     public function indexAction($id, $bar)
    {
        // no need to use the decryption service here as the parameters are already decrypted by the annotation service.
        //...
    }
```

#### In the controller without annotation service:

Use the ``decrypt`` function of the service to decrypt your data:

```php
     public function indexAction($id) 
    {
        $MyId = $this->get('nzo_url_encryptor')->decrypt($id);

        //...
    }
```

You can also use the ``encrypt`` function of the service to encrypt your data:

```php
     public function indexAction() 
    {   
        //...
        
        $Encrypted = $this->get('nzo_url_encryptor')->encrypt($data);
        //...
    }
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [Resources/doc/LICENSE](https://github.com/NAYZO/NzoUrlEncryptorBundle/tree/master/Resources/doc/LICENSE)
