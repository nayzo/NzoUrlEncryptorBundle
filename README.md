InnoceadCaptchaBundle
=====================

The InnoceadCaptchaBundle adds support for a captcha in Symfony 2.1.* and later.

Features include:

- Constraints Validator
- Form type
- Flexible configuration
- Uses translations (ru, en)


Installation
------------

See [Resources/doc/installation.md](https://github.com/innocead/CaptchaBundle/blob/master/Resources/doc/installation.md)

Usage
-----

You can use the "innocead_captcha" type in your forms this way:

```php
<?php
    // ...
    $builder->add('captcha', 'innocead_captcha');
    // ...
```

And Constraints Validator in model:

```php
<?php

namespace ..\Model;

use Innocead\CaptchaBundle\Validator\Constraints as CaptchaAssert;

class RecoverAccount
{
    /**
     * @CaptchaAssert\Captcha
     */
    public $captcha;
}
```

Configuration
-------------

See [Resources/doc/configuration.md](https://github.com/innocead/CaptchaBundle/blob/master/Resources/doc/configuration.md)
    

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

See [Resources/meta/LICENSE](https://github.com/innocead/CaptchaBundle/blob/master/Resources/meta/LICENSE)