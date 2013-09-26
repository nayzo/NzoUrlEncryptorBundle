NzoUrlEncryptorBundle
=====================

The **NzoUrlEncryptorBundle** is a Symfony2 Bundle used to Encrypt and Decrypt the variables passed variables through url and links and to provide more security in term of access to your project.
It prevent users from reading and modifying sensitive data sent through the url.

Features include:

- Url Variables Encryption
- Url Variables Decryption
- Access from Twig by ease
- Flexible configuration


Installation
------------

### Through Composer:

Add the following lines in your `composer.json` file:

``` js
"require": {
    "nzo/url-encryptor-bundle": "dev-master"
}
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

<pre>

 <a href="{{path('my-path-in-the-routing', {'id': MyId | urlencrypt } )}}"> My link </a>

// if it needed you can use the twig decryption filter:

<code>&lt;a href="{{path('my-path-in-the-routing', {'id': MyId | urldecrypt } )}}" &gt; My link &lt;/a&gt;</code>
</pre>

In the controller use the decrypt service on the encrypted 'variable' comming from the routing:

```php
     public function indexAction($encrypted_id) 
    {
        $Id = $this->get('nzo_url_encryptor')->decrypt($encrypted_id);

        //....
    }    
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [Resources/doc/LICENSE](https://github.com/NAYZO/NzoUrlEncryptorBundle/tree/master/Resources/doc/LICENSE)