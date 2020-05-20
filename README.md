## Module.php

This file provides a class enabling simple viewer-controller communication.

It is used like this:

`controller.php`
```
require_once 'Module.php';

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

Modules can include other modules as parameters (e.g. you could `$view->paramter = new Module('test');`)

I use this in my [personal website](https://darlo.me/) to generate a static websites, but this can also be used for dynamic websites.

There used to be a static website generator in this repo, but I removed it, you should use your own build pipeline (e.g. make) for that.
