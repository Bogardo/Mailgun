Bogardo/Mailgun
=======

A Mailgun package for Laravel 4 for sending emails using the Mailgun HTTP API.
It's main advantage is that the syntax is the same as the Laravel Mail component and I also tried to give it very simmilar functionality. So if you've used that component before, using the Mailgun package should be a breeze.

This package uses the [Mailgun-PHP](https://github.com/mailgun/mailgun-php) library version 1.4 behind the scenes.<br />

> This package is available on Packagist: [https://packagist.org/packages/bogardo/mailgun](https://packagist.org/packages/bogardo/mailgun)

## Table of contents ##
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
	- [Views](#views)
	- [Data](#data)
	- [Mail options](#mail-options)
		- [Recipients](#recipients)
		- [Sender](#sender)
		- [Subject](#subject)
	- [Attachments](#attachments)
	- [Embedding Inline Images](#embedding-inline-images)
	- [Scheduling](#scheduling)
	- [Tagging](#tagging)

## Installation ##
Open your `composer.json` file and add the following to the `require` key:

    "bogardo/mailgun": "dev-master"

Example:

    "require": {
    	"laravel/framework": "4.0.*",
	    "bogardo/mailgun": "dev-master"
	}

Run composer update from the command line to install the package
```bash
composer update
```

Add the service provider to the `providers` array in your `app/config/app.php` file.

    'Bogardo\Mailgun\MailgunServiceProvider'

## Configuration ##
Before you can start using the package we need to set some configurations.
To do so you must first publish to config file, you can do this with the following `artisan` command. 

```bash
php artisan config:publish bogardo/mailgun
```
After the config file has been published you can find it at: `app/config/packages/bogardo/mailgun/config.php`

In it you must specify the `from` details, your Mailgun `api key` and the Mailgun `domain`.

## Usage ##
The Mailgun package offers most of the functionality as the Laravel 4 [Mail component](http://laravel.com/docs/mail).

The `Mailgun::send` method may be used to send an e-mail message:

```php
Mailgun::send('emails.welcome', $data, function($message)
{
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

### Views ###

The first argument passed to the `send` method is the name of the view that should be used as the e-mail body. Mailgun supports 2 types of bodies: `text` and `html`.
You can specify the type of body like so:

```php
Mailgun::send(array('html' => 'html.view', 'text' => 'text.view'), $data, $callback);
```

If you have a `html` body as well as a `text` body then you don't need to specify the type, you can just pass an array where the first item is the `html` view and the second item the `text` view. 

```php
Mailgun::send(array('html.view','text.view'), $data, $callback);
```

When you only want to send an `html` body you can just pass a `string`.

```php
Mailgun::send(array('html.view'), $data, $callback);
```

When only sending a `text` body, just must pass an array and specify the type.

```php
Mailgun::send(array('text' => 'text.view'), $data, $callback);
```
 
### Data ###

The second argument passed to the `send` method is the `$data` `array` that is be passed to the view.

> Note: A `$message` variable is always passed to e-mail views, and allows the inline embedding of attachments. So, it is best to avoid passing a (custom) `message` variable in your view payload.

You can access the values from the `$data` array in your view using the `$message` variable and specifying the array key.

Example:

```php	
$data = array(
	'customer' => 'John Smith',
	'url' => 'http://laravel.com'
);

Mailgun::send('emails.welcome', $data, function($message)
{
	$message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

View `emails.welcome`:

```html
<body>
    Hi {{ $message->customer }},
	Please visit {{ $message->url }}
</body>
```

Renders to:
```html
<body>
    Hi John Doe,
	Please visit http://laravel.com
</body>
```

### Mail options ###
You can specify the mail options within the closure.

##### Recipients #####
The recipient methods all accept two arguments: `email` and `name` where the `name` field is optional.
 

The `to` method
```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->to('foo@example.com', 'Recipient Name');
});
```

The `cc` method

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->cc('foo@example.com', 'Recipient Name');
});
```

The `bcc` method

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->bcc('foo@example.com', 'Recipient Name');
});
```

##### Sender #####
In the Mailgun config file you have specified the `from` data. If you would like, you can override this using the `from` method it accepts two arguments: `email` and `name` where the `name` field is optional.

```php
#with name
$message->from('foo@example.com', 'Recipient Name');

#without name
$message->from('foo@example.com');
``` 

##### Subject #####
Setting the email subject
```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->subject('Email subject');
});
```

> The to, cc, bcc, sender, from and subject methods are all chainable:
> ```php
> $message
> 	->to('foo@example.com', 'Recipient Name')
> 	->cc('bar@example.com', 'Recipient Name')
> 	->subject('Email subject');
> ```

### Attachments ###
To send an attachment with the email you can use the `attach` method. It accepts 1 argument; The file path.

```php
Mailgun::send('emails.welcome', $data, function($message)
{  
    $message->attach($pathToFile);
});
```

### Embedding Inline Images ###
Embedding inline images into your e-mails is very easy.
In your view you can use the `embed` method and pass it the path to the file. This will return a CID (Content-ID) which you can use as the `source` for the image.

```html
<body>
    <img src="{{ $message->embed($pathToFile) }}">
</body>
```

> The $message variable is always passed to e-mail views by the Mailgun class.

### Scheduling ###
Mailgun provides the ability to set a delivery time for emails **up to 3 days in the future**.
To do this you can make use of the `later` method.
While messages are not guaranteed to arrive at exactly at the requested time due to the dynamic nature of the queue, Mailgun will do it's best.

The `later` method works the same as the (default) `send` method but it accepts 1 extra argument.
The extra argument is the amount of seconds (minutes, hours or days) from now the message should be send.
> **If the specified time exceeds the 3 day limit it will set the delivery time to the maximum of 3 days.**

To send an email in 10 seconds from now you can do the following: 
```php
Mailgun::later(10, 'emails.welcome', $data, function($message)
{
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

When passing a string or integer as the first argument, it will interpret it as `seconds`. You can also specify the time in `minutes`, `hours` or `days` by passing an array where the key is the type and the value is the amount.
For example, sending in 5 hours from now:
```php
Mailgun::later(array('hours' => 5), 'emails.welcome', $data, function($message)
{
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

> When scheduling messages, make sure you've set the correct timezone in your `app/config/app.php` file.
> 

### Tagging ###
Sometimes it’s helpful to categorize your outgoing email traffic based on some criteria, perhaps separate signup emails from password recovery emails or from user comments. Mailgun lets you tag each outgoing message with a custom value. When you access stats on you messages within the Mailgun control panel, they will be aggregated by these tags.

> **Warning:** A single message may be marked with up to 3 tags. Maximum tag name length is 128 characters.
> Mailgun allows you to have only limited amount of tags. You can have a total of 4000 unique tags.

To add a Tag to your email you can use the `tag` method.

You can add a single tag to an email by providing a `string`. 

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->tag('myTag');
});
```

To add multiple tags to an email you can pass an `array` of tags. (Max 3)

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->tag(array('Tag1', 'Tag2', 'Tag3'));
});
```

>If you pass more than 3 tags to the `tag` method it will only use the first 3, the others will be ignored.


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/Bogardo/mailgun/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
