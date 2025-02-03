NzoUrlEncryptorBundle
=====================

[![tests](https://github.com/nayzo/NzoUrlEncryptorBundle/actions/workflows/tests.yaml/badge.svg)](https://github.com/nayzo/NzoUrlEncryptorBundle/actions/workflows/tests.yaml)
[![Total Downloads](https://poser.pugx.org/nzo/url-encryptor-bundle/downloads)](https://packagist.org/packages/nzo/url-encryptor-bundle)
[![Latest Stable Version](https://poser.pugx.org/nzo/url-encryptor-bundle/v/stable)](https://packagist.org/packages/nzo/url-encryptor-bundle)

The **NzoUrlEncryptorBundle** is a Symfony Bundle used to Encrypt and Decrypt data and variables in the Web application or passed through the ``URL`` to provide more security to the project.
Also it prevent users from reading and modifying sensitive data sent through the ``URL``.


#### The Version (^6.0) is compatible with **Symfony >= 4.4**


Features include:

- Url Data & parameters Encryption
- Url Data & parameters Decryption
- Data Encryption & Decryption
- Access from Twig by ease
- Flexible configuration
- Uses OpenSSL extension


By default, this bundle use the **aes-256-ctr** algorithm.

CTR mode (without any additional authentication step) is malleable, which means that it is possible to change the meaning of the ciphertext and if the plaintext is guessable then it could lead to IDOR.

##### For more secure output, you must configure the bundle to use a **unique and random IV** (`random_pseudo_bytes: TRUE`)


Installation
------------

### Through Composer:

Install the bundle:

```
$ composer require nzo/url-encryptor-bundle
```

### Register the bundle in config/bundles.php (without Flex):

``` php
// config/bundles.php

return [
    // ...
    Nzo\UrlEncryptorBundle\NzoUrlEncryptorBundle::class => ['all' => true],
];
```

### Configure the bundle:

``` yml
# config/packages/nzo_encryptor.yaml

nzo_encryptor:
    annotations: false                       # optional, if you want to disable the use of annotations (keep only PHP attributes)
    secret_key: Your_Secret_Encryption_Key   # Required, max length of 100 characters.
    secret_iv:  Your_Secret_Iv               # Required only if "random_pseudo_bytes" is FALSE. Max length of 100 characters.
    cipher_algorithm:                        # optional, default: 'aes-256-ctr'
    base64_encode:                           # optional, default: TRUE
    format_base64_output:                    # optional, default: TRUE, used only when 'base64_encode' is set to TRUE
    random_pseudo_bytes:                     # optional, default: TRUE (generate a random encrypted text output each time => MORE SECURE !)
```

**!!! If you set `nzo_encryptor.annotations` to `true`, you must require `composer require doctrine/annotations` in your project.**

##### * To generate the same cypher text each time: `random_pseudo_bytes: FALSE` (Not Secure)
##### * To generate a different cypher text each time: `random_pseudo_bytes: TRUE` (Secure)

Usage
-----

#### In the twig template:
 
Use the twig extensions filters or functions to ``encrypt`` or ``decrypt`` your data:

``` html
// Filters:

# Encryption:

    <a href="{{path('my-route', {'id': myId | nzo_encrypt } )}}"> My link </a>

    {{myVar | nzo_encrypt }}

# Decryption:

    <a href="{{path('my-route', {'id': myId | nzo_decrypt } )}}"> My link </a>

    {{myVar | nzo_decrypt }}


// Functions:

# Encryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzo_encrypt('myId') } )}}"> My link </a>

    {{ nzo_encrypt(myVar) }}

# Decryption:

    <a href="{{path('my-path-in-the-routing', {'id': nzo_decrypt('myId') } )}}"> My link </a>

    {{ nzo_decrypt(myVar) }}
```

#### In the controller with annotation service:

Use the annotation service to ``decrypt`` / ``encrypt`` automatically any parameter you want, by using the ``ParamDecryptor`` / ``ParamEncryptor`` annotation service and specifying in it all the parameters to be decrypted/encrypted.

```php
use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Nzo\UrlEncryptorBundle\Annotations\ParamEncryptor;

class MyController
{
    /**
    * @ParamDecryptor({"id", "foo"})
    */
    // OR
    #[ParamDecryptor(["id", "foo"])]
    public function decryptionAction($id, $foo)
    {
        // no need to use the decryption service here as the parameters are already decrypted by the annotation service.
        //...
    }

    /**
    * @ParamEncryptor({"id", "foo"})
    */
    // OR
    #[ParamEncryptor(["id", "foo"])]
    public function encryptionAction($id, $foo)
    {
        // no need to use the encryption service here as the parameters are already encrypted by the annotation service.
        //...
    }
}
```

#### With autowiring:

```php
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;

class MyController
{
    private $encryptor;

    public function __construct(Encryptor $encryptor)
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

#### Without autowiring:

```php
class MyController
{
    public function indexAction($data) 
    {
        $encrypted = $this->get('nzo_encryptor')->encrypt($data);
        
        $decrypted = $this->get('nzo_encryptor')->decrypt($data);
    }
}    
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [LICENSE](https://github.com/nayzo/NzoUrlEncryptorBundle/tree/master/LICENSE)
