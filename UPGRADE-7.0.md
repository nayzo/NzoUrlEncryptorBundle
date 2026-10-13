Upgrade from 6.x to 7.0
=======================

The version 7 signs every encrypted value. A value that was modified is now refused instead of being decrypted to something else. This needed a new format, so read the first two points before you deploy.

Requirements
------------

- PHP 8.1 or higher
- Symfony 6.4, 7 or 8

If you cannot meet them, stay on the version 6: `composer require nzo/url-encryptor-bundle:^6.6`.

1. Values encrypted by the version 6
------------------------------------

They are refused by default. If such values are still around, in links you sent by e-mail, in bookmarks or in your database, enable `legacy_decryption`:

```yaml
nzo_encryptor:
    secret_key: '%env(NZO_ENCRYPTOR_KEY)%'
    legacy_decryption: true
```

While it is enabled:

- keep the same `secret_key` and the same `cipher_algorithm` as before, the old values depend on them;
- every new value is encrypted in the new format;
- the old values are read as the version 6 read them, without any check. A modified value is still accepted when it is presented in the old format.

So turn it off as soon as the old values are gone: when your old links have expired, or when you have encrypted your stored values again.

```php
if ($encryptor->needsReencryption($stored)) {
    $stored = $encryptor->encrypt($encryptor->decrypt($stored));
}
```

2. The encrypted values are longer
----------------------------------

A value is 17 bytes longer than before, about 23 characters once encoded. Check the route requirements and the database columns that hold an encrypted value.

The characters used have not changed.

3. A value that cannot be decrypted
-----------------------------------

`decrypt()` used to return an empty string, or a wrong value. It now throws `Nzo\UrlEncryptorBundle\Exception\DecryptionException`.

- With `#[ParamDecryptor]`, the response is a 404. You have nothing to do.
- Where you call `decrypt()` yourself, or `nzo_decrypt` in a template, catch the exception if a wrong value is something you expect.

`decrypt()` also returns the value exactly as it was encrypted. It used to remove the spaces around it.

4. Annotations
--------------

The doc block annotations are no longer read. Use the attributes:

```php
// Before
/**
 * @ParamDecryptor({"id", "foo"})
 */

// After
#[ParamDecryptor(['id', 'foo'])]
```

An action that still has the annotation throws an exception that says so, the first time it is called.

`doctrine/annotations` is no longer used by the bundle.

5. Same output for the same input
---------------------------------

With `random_pseudo_bytes: false`, the output for a given input is not the one the version 6 gave. If you stored such outputs to compare them or to search by them, compute them again.

6. Parameters that are empty
----------------------------

`#[ParamDecryptor]` and `#[ParamEncryptor]` leave a parameter untouched when it is `null` or an empty string, for example an optional route parameter that was left out. Your action receives `null`, not an empty string.

7. Cipher algorithms
--------------------

The algorithm is checked when the container is built. The ones that cannot encrypt every value with a key and an IV alone (GCM, CCM, OCB, ChaCha20-Poly1305, XTS, key wrapping) are refused there, with a message. They did not work in the version 6 either.

8. If you extend or instantiate the classes of the bundle
---------------------------------------------------------

- `Encryptor::encrypt()` and `Encryptor::decrypt()` take a second, optional argument, and have native types.
- `Encryptor::__construct()` takes two more optional arguments: the previous keys and the legacy flag. The first five are unchanged.
- `AnnotationResolver::__construct()` no longer takes the annotation reader: `(Encryptor $encryptor, bool $queryStringParams = false)`.
- `ParamEncryptor` and `ParamDecryptor` can only be put on a method or a function.
- `DependencyInjection\Compiler\LoadAnnotationService` and `Resources/config/services.yaml` are removed. The service names have not changed: `nzo_encryptor`, `nzo.annotation_resolver`, `nzo.twig.encryptor_extension`.

What you gain
-------------

- Modified values are refused.
- `previous_secret_keys`: change your key without breaking the existing values.
- A context, to bind a value to its owner.
- Keys read from the environment are used as they are, whatever their length.
