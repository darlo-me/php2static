## Module.php

This file provides a class enabling simple viewer-controller communication.

It is used like this:
`controller.php`
```
require_once 'Module.php';

use php2static\Module;

Module::setInputFolder('views'); // Set view folder

$view = new Module('home');
$view->parameter = "value";
echo $view;
```
`views/home.php`
```
<div><?php echo $this->parameter; ?></div>
```

`php controller.php` will output `<div>value</div>`

Modules can include other modules as parameters (e.g. you could `$view->parameter = new Module('test');`)

I use this in my [personal website](https://darlo.me/) to generate a static websites, but this can also be used for dynamic websites.

There used to be a static website generator in this repo, but I removed it, you should use your own build pipeline (e.g. make) for that.

Please note that modules hanging will climb the calling tree.
This means that a module which never finishes will cause the caller to never end (and to not return anything, as the module output is cached using `ob_start`)

The reason `ob_start` is used is so that modules can return values and affect modules that may want to change preceding data (e.g. change headers, title, etc.)
