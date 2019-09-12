## JavaScript & CSS Queue for PHP

PHP package for registering and enqueuing JavaScript and CSS files.



### Installation

Eventually this will be on Packagist, but it's not there yet...

### Usage

Both classes have the same set of methods. In pretty much all cases an example call to `\Aelora\JS::method` below has a buddy `\Aelora\CSS::method` in the `CSS` class. 

#### Registering Files

```php
\Aelora\JS::register($url, $name='', $version=false, $dependencies=[], $attributes=[]);
```

```php
\Aelora\CSS::register($url, $name='', $version=false, $dependencies=[], $attributes=[]);
```



| Parameter     |          | Notes                                                        |
| ------------- | -------- | ------------------------------------------------------------ |
| $url          | required | The local or full url to the script to register              |
| $name         | optional | A name that can be used to later enqueue this script. It's also used as the `id` parameter when the script or style tag is output on the page. If it's omitted an MD5 hash is used instead. |
| $version      | optional | A string representing the version of the script. If included it's appended to the URL as a query string in the `script` or `style` tag. |
| $dependencies | optional | An array of other styles or scripts that this is dependent on. For example, if you have registered jQuery using the `name` `jquery`, you'd put that in this array. |
| $attributes   | optional | Any extra attributes added to the `script` or `style` tag.   |



#### Enqueueing Files

You have two options for enqueueing a `script` or `style`. 

If you just need to enqueue a URL, you can pass it into the `enqueue` method.

```php
\Aelora\JS::enqueue('https://code.jquery.com/jquery-2.2.4.min.js');
```

With this line the full URL will be enqueued for output. Doing it this way you lose the ability to have this package handle dependencies.

The better option is to register the script or style and then enqueue it when you need to use it.

```php
\Aelora\JS::register('jquery', 'https://code.jquery.com/jquery-2.2.4.min.js');
\Aelora\JS::enqueue('jquery'); 
```

When a script or style is enqueued any dependencies are also enqueued. 



#### Writing to HTML

In the head of your document you're going to call the `write()` method for both classes.

```php+HTML
<html>
    <head>
        <title>My Page</title>
        <?php \Aelora\JS::write(); ?>
        <?php \Aelora\CSS::write(); ?>
    </head>
</html>
```

This will dump out all the script and style tags linking to the files you've enqueued. 

If you're using Blade templates you can use it like this.

```php+HTML
<html>
    <head>
        <title>My Page</title>
        {{ \Aelora\JS::write() }}
        {{ \Aelora\CSS::write() }}
    </head>
</html>
```



#### Dequeueing

If there's something in the queue that you don't want for a specific view you can use the `dequeue` method.

```php
\Aelora\JS::dequeue('jquery'); 
```



#### Clearing 

If you need to clear out any registered scripts and styles and the queue.

```
\Aelora\JS::clear();
\Aelora\CSS::clear(); 
```



#### Reset

This reloads the classes with new data from a config file, or falls back to the defaults if you don't give it a config file. 

```
\Aelora\JS::reset('/path/to/config/file.php'); 	// Uses new file
\Aelora\JS::reset();							// Uses default config 
```

Both classes have a `reset` method, but only the one in `JS` will actually reset and reload. `reset` in the `CSS` class only clears the registrations and queue from `CSS` and does not reload. The `reset` method in `JS` clears both classes and reloads. 



### Configuration Files

Rather than registering and enqueueing a bunch of files in your startup scripts, you can also use a configuration file. 

```php
<?php

return [
    /* Scripts and styles to register.
     * 
     * This doesn't actually put them onto pages. You also have to
     * either globally enqueue this using this file or enqueue them
     * before your view is rendered. 
     */
    'register' => [
        'js' => [            
        ],
        'css' => [
        ]
    ],
    /* Enqueue any styles and scripts, by their handle, that should
     * be included on all pages. 
     */
    'enqueue' => [
        'js' => [
        ],
        'css' => [
        ]
    ]
];
```

The `register` section is where you add scripts or styles to the registration set, and you've got two options.

First one is if you just want to pass a URL.

```php
'register' => [
    'jquery' => 'https://code.jquery.com/jquery-2.2.4.min.js'
]
```

This will register a script with name jquery and that URL; but will not have a version, any dependencies, or attributes. If you want to include any of that, use an array instead.

```php
'register' => [
    'jquery' => [
        'url' => 'https://code.jquery.com/jquery-2.2.4.min.js',
        'version' => '2.2.4',
        'dependencies' => [],
        'attributes' => []
    ]
]
```

URL is the only required element of the array, but if that's all you're using you might as well use the shorter string only way above. 

To automatically enqueue scripts or styles include them in the enqueue section. Both `js` and `css` are arrays of names.

```php
'enqueue' => [
    'js' => ['jquery', 'bootstrap'],
    'css' => ['bootstrap', 'default-css']
]
```

Anything you enqueue here should be also in the register section. 

#### Config File Location

If you want to specify a config file location, you can use the `configFile` property.

```php
\Aelora\JS::$configFile = '/path/to/config.php';
```

If you don't specify `$configFile`, this package will look for it in the following places, in this order.

1. If there's a function named `config_path` (Laravel) and the file `jscssqueue.php` file exists in that path, it will be used.
2. It will look for a file named `jscssqueue.php` in the same folder as the `JS.php` and `CSS.php` files in this package. 

If neither is found, it starts with an empty set of registrations and an empty queue. 