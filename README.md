Bogardo/Mailgun
=======

A Mailgun package for Laravel 4 for sending emails using the Mailgun HTTP API. 

This package uses the [Mailgun-PHP](https://github.com/mailgun/mailgun-php) library version 1.4.<br />

## Installation ##
Open your `composer.json` file and add the following to the `require` key:

    "bogardo/mailgun": "0.1"

Like so:

    "require": {
    	"laravel/framework": "4.0.*",
	    "bogardo/mailgun": "0.1"
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
