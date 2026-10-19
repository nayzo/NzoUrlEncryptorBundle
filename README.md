NzoUrlEncryptorBundle
=====================

[![tests](https://github.com/nayzo/NzoUrlEncryptorBundle/actions/workflows/tests.yaml/badge.svg)](https://github.com/nayzo/NzoUrlEncryptorBundle/actions/workflows/tests.yaml)
[![Total Downloads](https://poser.pugx.org/nzo/url-encryptor-bundle/downloads)](https://packagist.org/packages/nzo/url-encryptor-bundle)
[![Latest Stable Version](https://poser.pugx.org/nzo/url-encryptor-bundle/v/stable)](https://packagist.org/packages/nzo/url-encryptor-bundle)

A Symfony bundle that encrypts the values you put in a URL, and decrypts them before they reach your controller.

Your users see `/invoice/AbC3x...` instead of `/invoice/42`. They cannot read the value, and they cannot change it: a value that was modified is refused.

It also encrypts any other value, for example a secret you store in a database.

Which version do I need?
------------------------

| Your application | Bundle version | Install |
|---|---|---|
| Symfony 6.4, 7 or 8 | **7** | `composer require nzo/url-encryptor-bundle` |
| Symfony 4.4 to 6.3 | **6** | `composer require nzo/url-encryptor-bundle:^6.6` |

The documentation of version 6 is [on the 6.x branch](https://github.com/nayzo/NzoUrlEncryptorBundle/tree/6.x#readme).

Coming from version 6? Read [UPGRADE-7.0.md](UPGRADE-7.0.md) first: the encrypted values have a new format.

Installation
------------

```
composer require nzo/url-encryptor-bundle
```

Without Symfony Flex, register the bundle yourself:

```php
// config/bundles.php

return [
    // ...
    Nzo\UrlEncryptorBundle\NzoUrlEncryptorBundle::class => ['all' => true],
];
```

Then give it a secret key:

```yaml
# config/packages/nzo_encryptor.yaml

nzo_encryptor:
    secret_key: '%env(NZO_ENCRYPTOR_KEY)%'
```

Generate the key once, and keep it out of your repository:

```
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

That is all you need. Every other option has a safe default.

Usage
-----

### In a Twig template

Encrypt the value when you build the link:

```twig
<a href="{{ path('invoice_show', {id: invoice.id | nzo_encrypt}) }}">My invoice</a>
```

`nzo_encrypt` and `nzo_decrypt` exist as filters and as functions:

```twig
{{ value | nzo_encrypt }}
{{ nzo_encrypt(value) }}

{{ value | nzo_decrypt }}
{{ nzo_decrypt(value) }}
```

`nzo_decrypt` throws an exception when the value cannot be decrypted, so the page fails. Decrypt in the controller when the value comes from the user.

### In a controller

List the parameters to decrypt. They are decrypted before your action runs:

```php
use Nzo\UrlEncryptorBundle\Annotations\ParamDecryptor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InvoiceController
{
    #[Route('/invoice/{id}', name: 'invoice_show')]
    #[ParamDecryptor(['id'])]
    public function show(string $id): Response
    {
        // $id is "42"
    }
}
```

`#[ParamEncryptor(['id'])]` does the opposite: the action receives the encrypted value. For a single parameter, `#[ParamDecryptor('id')]` is enough.

Each parameter is looked up in the route parameters first, then in the request body. To also look in the query string (`?id=...`), enable `query_string_params`.

If a value cannot be decrypted, because someone changed it or made it up, the response is a **404**. Your action is not called.

### As a service

```php
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Nzo\UrlEncryptorBundle\Exception\DecryptionException;

class InvoiceLinks
{
    public function __construct(private Encryptor $encryptor)
    {
    }

    public function example(): void
    {
        $encrypted = $this->encryptor->encrypt('42');

        try {
            $id = $this->encryptor->decrypt($encrypted);
        } catch (DecryptionException $e) {
            // not a value this application encrypted
        }
    }
}
```

`decrypt()` never returns a wrong value: it returns what was encrypted, or it throws.

Going further
-------------

### Keep a link from being reused elsewhere

By default, the encrypted form of `42` is accepted by every action that decrypts an `id`: a link to invoice 42 also opens order 42.

Give each kind of link a context, any name you like, and use the same one on both sides:

```twig
<a href="{{ path('invoice_show', {id: invoice.id | nzo_encrypt('invoice')}) }}">My invoice</a>
```

```php
#[ParamDecryptor(['id'], context: 'invoice')]
public function show(string $id): Response
```

A value encrypted for another context, or for none, gives a 404.

### Bind a stored value to its owner

The context is also the second argument of the service. Use it to tie a value to a user, a tenant or a record:

```php
$encrypted = $this->encryptor->encrypt($apiToken, 'user-' . $user->getId());

$this->encryptor->decrypt($encrypted, 'user-' . $user->getId()); // the token
$this->encryptor->decrypt($encrypted, 'user-' . $other->getId()); // DecryptionException
```

A value copied from one user to another is refused. The context is not secret and is not stored in the encrypted value.

### Get the same output every time

By default, encrypting the same value twice gives two different outputs. This is the safest behaviour.

If you need a stable output, for a URL that must not change or to search a column by its encrypted value, disable `random_pseudo_bytes` and set a `secret_iv`:

```yaml
nzo_encryptor:
    secret_key: '%env(NZO_ENCRYPTOR_KEY)%'
    secret_iv: '%env(NZO_ENCRYPTOR_IV)%'
    random_pseudo_bytes: false
```

The trade-off: anyone who sees two outputs can tell whether they hide the same value.

### Change the secret key

Put the new key in `secret_key` and move the old one to `previous_secret_keys`:

```yaml
nzo_encryptor:
    secret_key: '%env(NZO_ENCRYPTOR_KEY)%'
    previous_secret_keys:
        - '%env(NZO_ENCRYPTOR_OLD_KEY)%'
```

New values are encrypted with the new key. Values encrypted with the old one are still decrypted, so nothing breaks for your users.

For values you store, `needsReencryption()` tells you which ones to encrypt again:

```php
if ($this->encryptor->needsReencryption($stored)) {
    $stored = $this->encryptor->encrypt($this->encryptor->decrypt($stored));
}
```

Once nothing uses the old key any more, remove it.

Configuration reference
-----------------------

```yaml
nzo_encryptor:
    secret_key: '%env(NZO_ENCRYPTOR_KEY)%'  # required
    previous_secret_keys: []                # keys that were replaced: they still decrypt
    secret_iv: ''                           # required when random_pseudo_bytes is false
    cipher_algorithm: aes-256-ctr
    base64_encode: true                     # false: the output is raw bytes, unusable in a URL
    format_base64_output: true              # true: the output is safe in a URL
    random_pseudo_bytes: true               # false: the same input gives the same output
    query_string_params: false              # true: the attributes also read the query string
    legacy_decryption: false                # true: values encrypted by version 6 are still read
```

A key or an IV written in the configuration file is limited to 100 characters. Read from the environment, it has no limit.

`cipher_algorithm` accepts the OpenSSL algorithms that work with a key and an IV, such as `aes-256-ctr` or `aes-256-cbc`. GCM, CCM, OCB and ChaCha20-Poly1305 are refused: the bundle already authenticates every value, whatever the algorithm. Changing the algorithm makes the existing values unreadable.

On PHP 8.1, only the AES, ARIA and Camellia algorithms are available.

How it is protected
-------------------

- Each value is encrypted with a new random IV (unless you asked for a stable output), then signed with HMAC-SHA256 (encrypt-then-MAC). The signature is checked before anything is decrypted.
- The encryption key and the signing key are two different keys, both derived from your `secret_key` with HKDF.
- A value encrypted with one algorithm, one key or one context is never accepted with another.
- The service cannot be serialized and hides its keys from a dump, so they do not leak into a session, a cache or a log.

The security of all this is the security of your key. Use a random one, as shown in the installation.

License
-------

This bundle is under the MIT license. See the [LICENSE](LICENSE) file.
