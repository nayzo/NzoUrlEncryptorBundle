NzoUrlEncryptorBundle
=====================

[![Build Status](https://travis-ci.org/nayzo/NzoUrlEncryptorBundle.svg?branch=master)](https://travis-ci.org/nayzo/NzoUrlEncryptorBundle)
[![Total Downloads](https://poser.pugx.org/nzo/url-encryptor-bundle/downloads)](https://packagist.org/packages/nzo/url-encryptor-bundle)
[![Latest Stable Version](https://poser.pugx.org/nzo/url-encryptor-bundle/v/stable)](https://packagist.org/packages/nzo/url-encryptor-bundle)

The **NzoUrlEncryptorBundle** is a Symfony Bundle used to Encrypt and Decrypt data and variables in the Web application or passed through the ``URL`` to provide more security to the project.
Also it prevent users from reading and modifying sensitive data sent through the ``URL``.


#### This Version (^5.0) is compatible with **Symfony >= 5**, for Symfony 2, 3 and 4 use the composer tag: ^4.3 instead.


Features include:

- Compatible Symfony version 5
- Url Data & parameters Encryption
- Url Data & parameters Decryption
- Data Encryption & Decryption
- Access from Twig by ease
- Flexible configuration
- Uses OpenSSL extension


By default, this bundle is using **aes-256-ctr** algorithm.

CTR mode (without any additional authentication step) is malleable, which means that it is possible to change the meaning of the ciphertext and if the plaintext is guessable then it could lead to [IDOR](https://portswigger.net/web-security/access-control/idor).

Thus, **this bundle should not be used to encrypt sensitive data** since by default it does not prevent users from modifying encrypted ones.

**Since the key is reused, if a user is able to guess the plaintext of one ciphertext he will be able to decrypt any ciphertext.**

###### For the record, before releases v5.0.1 and v4.3.2, the key and iv where not mandatory which means that anyone could have decrypted and modified the encrypted data.


Installation
------------

### Through Composer:

Install the bundle:

```
$ composer require nzo/url-encryptor-bundle
```

### Register the bundle in app/AppKernel.php (without Flex):

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
# app/config/config.yml (Symfony V2 or V3)
# config/packages/nzo_url_encryptor.yaml (Symfony V4)

nzo_url_encryptor:
    secret_key: YourSecretEncryptionKey    # Required, max length of 100 characters.
    secret_iv:  YourSecretIv               # Required only if "random_pseudo_bytes" is FALSE. Max length of 100 characters.
    cipher_algorithm:                      # optional, default: 'aes-256-ctr'
    base64_encode:                         # optional, default: TRUE
    random_pseudo_bytes:                   # optional, default: FALSE (generate a random encrypted text output)
```

Usage
-----

#### In the twig template:
 
Use the twig extensions filters or functions to ``encrypt`` or ``decrypt`` your data:

``` html
// Filters:

# Encryption:

    <a href="{{path('my-route', {'id': myId | urlencrypt } )}}"> My link </a>

    {{myVar | urlencrypt }}

# Decryption:

    <a href="{{path('my-route', {'id': myId | urldecrypt } )}}"> My link </a>

    {{myVar | urldecrypt }}


// Functions:

# Encryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzoEncrypt('myId') } )}}"> My link </a>

    {{ nzoEncrypt(myVar) }}

# Decryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzoDecrypt('myId') } )}}"> My link </a>

    {{ nzoDecrypt(myVar) }}
```

#### In the controller with annotation service:

Use the annotation service to ``decrypt`` / ``encrypt`` automatically any parameter you want, by using the ``ParamDecryptor`` / ``ParamEncryptor`` annotation service and specifying in it all the parameters to be decrypted/encrypted.

```php
use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;

class MyController extends AbstractController
{
    /**
    * @ParamDecryptor(params={"id", "bar"})
    */
    public function decryptionAction($id, $bar)
    {
        // no need to use the decryption service here as the parameters are already decrypted by the annotation service.
        //...
    }

    /**
    * @ParamEncryptor(params={"id", "bar"})
    */
    public function encryptionAction($id, $bar)
    {
        // no need to use the encryption service here as the parameters are already encrypted by the annotation service.
        //...
    }
}
```

#### In the controller (With autowiring):

```php
use Nzo\UrlEncryptorBundle\UrlEncryptor\UrlEncryptor;

class MyController extends AbstractController
{
    private $encryptor;

    public function __construct(UrlEncryptor $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function indexAction($data) 
    {
        $encrypted = $this->encryptor->encrypt($data);
        
        $decrypted = $this->encryptor->decrypt($data);
    }
}    
```

#### In the controller (Without autowiring):

```php
class MyController extends Controller
{
    public function indexAction($data) 
    {
        $encrypted = $this->get('nzo_url_encryptor')->encrypt($data);
        
        $decrypted = $this->get('nzo_url_encryptor')->decrypt($data);
    }
}    
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [Resources/doc/LICENSE](https://github.com/nayzo/NzoUrlEncryptorBundle/tree/master/Resources/doc/LICENSE)
