# Bogardo/Mailgun

A package for the Laravel Framework for sending emails using the Mailgun API.
The syntax for sending emails is very similar to the Laravel Mail component.

Laravel already supports sending emails via the Mailgun API out of the box but it doesn't support Mailgun specific features.

This packages fills that gap and supports most of the mail features offered by Mailgun:

* Open & Click tracking
* Campaigns
* Tags
* Scheduled delivery
* Batch sending
* Custom data/headers

> This package makes use of the [mailgun-php](https://github.com/mailgun/mailgun-php) library.

[![Total Downloads](https://poser.pugx.org/bogardo/mailgun/downloads.png)](https://packagist.org/packages/bogardo/mailgun)
[![Monthly Downloads](https://poser.pugx.org/bogardo/mailgun/d/monthly.png)](https://packagist.org/packages/bogardo/mailgun)
[![License](https://poser.pugx.org/bogardo/mailgun/license)](https://packagist.org/packages/bogardo/mailgun)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bogardo/Mailgun?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

##### Basic Example

```php
Mailgun::send('emails.invoice', $data, function ($message) {
    $message
        ->subject('Your Invoice')
        ->to('john.doe@example.com', 'John Doe');
        ->bcc('sales@company.com')
        ->attach(storage_path('invoices/12345.pdf'))
        ->trackClicks(true)
        ->trackOpens(true)
        ->tag(['tag1', 'tag2'])
        ->campaign(2);
});
```

## Version Compatibility

This package currently supports Laravel 5.1 and up.
For older versions of Laravel please refer to [older versions](https://github.com/Bogardo/Mailgun/releases) of this package.

## Installation

Install the package via composer

```bash
composer require bogardo/mailgun
```

Register the ServiceProvider and (optionally) the Facade

```php
// config/app.php

'providers' => [
    ...
    Bogardo\Mailgun\MailgunServiceProvider::class

];

...

'aliases' => [
	...
    'Mailgun' => Bogardo\Mailgun\Facades\Mailgun::class
],
```

Next, publish the config file with the following `artisan` command.<br />

```bash
php artisan vendor:publish --provider="Bogardo\Mailgun\MailgunServiceProvider" --tag="config"
```

After publishing, configure the package in `config/mailgun.php`.


### HTTP Client Dependency

To remove the dependency for a specific HTTP client library (e.g. Guzzle) the [mailgun-php](https://github.com/mailgun/mailgun-php) library has a dependency on the virtual package
[php-http/client-implementation](https://packagist.org/providers/php-http/client-implementation) which allows you to install **any** supported client adapter, it does not care which one. Please refer to the [documentation](http://docs.php-http.org/) for more information.

This gives you the freedom to use any (supported) client for communicating with the Mailgun API.
To register your driver you must register it in the Service Container with the `mailgun.client` key.

The registration **must** occur before the `MailgunServiceProvider` is being registered.

#### Guzzle 6 example implementation

Install the dependencies:

```bash
$ composer require php-http/guzzle6-adapter
```

Add the following to your `AppServiceProvider` `register()` method.

```php
$this->app->bind('mailgun.client', function() {
	$client = new \GuzzleHttp\Client([
		// your configuration
	]);
	
	return new \Http\Adapter\Guzzle6\Client($client);
});
```
---

<br /><br />

## Usage

The Mailgun package offers most of the functionality as the [Laravel Mail component](http://laravel.com/docs/mail).

The `Mailgun::send()` method may be used to send an e-mail message:

```php
Mailgun::send('emails.welcome', $data, function ($message) {
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```
---

### Views

The first argument passed to the `send` method is the name of the view that should be used as the e-mail body.
Mailgun supports 2 types of bodies: `text` and `html`.
You can specify the type of body like so:

```php
Mailgun::send(['html' => 'emails.htmlmail', 'text' => 'emails.textmail'], $data, $callback);
```

If you have an `html` body as well as a `text` body then you don't need to specify the type, you can just pass an array where the first item is the `html` view and the second item the `text` view. 

```php
Mailgun::send(['emails.htmlmail','emails.textmail'], $data, $callback);
```

When you only want to send an `html` body you can just pass a `string`.

```php
Mailgun::send(['emails.htmlmail'], $data, $callback);
```

When only sending a `text` body, just must pass an array and specify the type.

```php
Mailgun::send(['text' => 'emails.textmail'], $data, $callback);
```

#### Raw

If you do not want to use a template you can use the `raw()` method.

```php
Mailgun::raw("This is the email body", $callback);
```

---
 
### Data ###

The second argument passed to the `send` method is the `$data` `array` that is passed to the view.

> Note: A `$message` variable is always passed to e-mail views, and allows the inline embedding of attachments. **So, it is best to avoid passing a (custom) `message` variable in your view payload.**

You can access the values from the `$data` array as variables using the array key.

Example:

```php	
$data = [
	'customer' => 'John Smith',
	'url' => 'http://laravel.com'
];

Mailgun::send('emails.welcome', $data, function ($message) {
	$message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

View `emails.welcome`:

```html
<body>
    Hi {{ $customer }},
	Please visit {{ $url }}
</body>
```

Which renders:

```html
<body>
    Hi John Doe,
	Please visit http://laravel.com
</body>
```

---

### Mail options
You can specify the mail options within the closure.

#### Recipients

The `to` method

```php
Mailgun::send('emails.welcome', $data, function($message) {
	$message->to('foo@example.com', 'Recipient Name');
});
```

The `cc` method	

```php
$message->cc('foo@example.com', 'Recipient Name');
```

The `bcc` method

```php
$message->bcc('foo@example.com', 'Recipient Name');
```

#### Batch Sending

Mailgun supports the ability send to a group of recipients through a single API call.
This is achieved by specifying multiple recipient email addresses as to parameters and using Recipient Variables.

Recipient Variables are custom variables that you define, which you can then reference in the message body. They give you the ability to send a custom message to each recipient while still using a single API Call.

**To access a recipient variable within your email, simply reference `%recipient.yourkey%`.**

> **Warning**: It is important when using Batch Sending to also use Recipient Variables. This tells Mailgun to send each recipient an individual email with only their email in the to field. If they are not used, all recipients’ email addresses will show up in the to field for each recipient.

###### Examples

```php
use Bogardo\Mailgun\Mail\Message;

Mailgun::send('email.batch', $data, function(Message $message){
    $message->to([
        'user1@example.com' => [
            'name' => 'User One',
            'age' => 37,
            'city' => 'New York'
        ],
        'user2@example.com' => [
            'name' => 'User Two',
            'age' => 41,
            'city' => 'London'
        ]
    ]);
});

// Or

Mailgun::send('email.batch', $data, function(Message $message){
    $message->to('user1@example.com', 'User One', [
        'age' => 37, 
        'city' => 'New York'
    ]);
    $message->to('user2@example.com', 'User Two', [
        'age' => 41,
        'city' => 'London'
    ]);
});

```

```php
// resources/views/email/batch.blade.php

Hi %recipient.name%,

Age: %recipient.age%
City: %recipient.city%

```

> Note: Mailgun limits the number of recipients per message to 1000

##### Sender #####
In the Mailgun config file you have specified the `from` address. If you would like, you can override this using the `from` method. It accepts two arguments: `email` and `name` where the `name` field is optional.

```php
// with name
$message->from('foo@example.com', 'Recipient Name');

// without name
$message->from('foo@example.com');
``` 

##### Subject #####
Setting the email subject

```php
$message->subject('Email subject');
```

##### Reply-to #####
Setting a reply-to address

```php
$message->replyTo('reply@example.com', 'Helpdesk');
```

>If the reply_to config setting is set, the reply-to will be automatically set for all messages
>You can overwrite this value by adding it to the message as displayed in the example.

### Attachments ###
To add an attachment to the email you can use the `attach` method. You can add multiple attachments.

The `attach` method accepts 2 arguments:

* `$path` | The path to the image
* `$name` | (optional) The _remote_ name of the file (attachment is renamed server side)

```php
$message->attach($path, $name);
```

### Embedding Inline Images

Embedding inline images into your e-mails is very easy.
In your view you can use the `embed` method and pass it the path to the file. This will return a CID (Content-ID) which will be used as the `source` for the image. You can add multiple inline images to your message. 

The `embed` method accepts 2 arguments:

* $path | The path to the image
* $name | (optional) The _remote_ name of the file (attachment is renamed server side)

```html
<body>
    <img src="{{ $message->embed($path, 'rename.png'); }}">
</body>
```
#### Example ####

###### Input ######

```php
$data = [
	'img' => 'assets/img/example.png',
	'otherImg' => 'assets/img/foobar.jpg'
];

Mailgun::send('emails.welcome', $data, function ($message) {
I	$message->to('foo@example.com', 'Recipient Name');
});
```

```html
<body>
    <img src="{{ $message->embed($img) }}">
    <img src="{{ $message->embed($otherImg, 'custom_name.jpg') }}">
</body>
```

###### Output ######
```html
<body>
    <img src="cid:example.png">
    <img src="cid:custom_name.jpg">
</body>
```

> The $message variable is always passed to e-mail views by the Mailgun class.

---

### Scheduling

Mailgun provides the ability to set a delivery time for emails **up to 3 days in the future**.
To do this you can make use of the `later` method.
While messages are not guaranteed to arrive at exactly at the requested time due to the dynamic nature of the queue, Mailgun will do it's best.

The `later` method works the same as the (default) `send` method but it accepts 1 extra argument.
The extra argument is the amount of seconds (minutes, hours or days) from now the message should be send.

> **If the specified time exceeds the 3 day limit it will set the delivery time to the maximum of 3 days.**

To send an email in 60 seconds from now you can do the following: 

```php
Mailgun::later(60, 'emails.welcome', $data, function ($message) {
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

When passing a string or integer as the first argument, it will interpret it as `seconds`. You can also specify the time in `minutes`, `hours` or `days` by passing an array where the key is the type and the value is the amount.
For example, sending in 5 hours from now:

```php
Mailgun::later(['hours' => 5], 'emails.welcome', $data, function($message) {
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

You can also pass a `DateTime` or `Carbon` date object.

### Tagging

Sometimes it’s helpful to categorize your outgoing email traffic based on some criteria, perhaps for separate signup emails, password recovery emails or for user comments. Mailgun lets you tag each outgoing message with a custom tag. When you access the [reporting](https://mailgun.com/app/reporting/overview) page  within the Mailgun control panel you can filter by those tags.

> **Warning:** A single message may be marked with up to 3 tags. Maximum tag name length is 128 characters.
> 
> Mailgun allows you to have only limited amount of tags. You can have a total of 4000 unique tags.

To add a Tag to your email you can use the `tag` method.

You can add a single tag to an email by providing a `string`. 

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->tag('myTag');
});
```

To add multiple tags to an email you can pass an `array` of tags. (Max 3)

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->tag(['Tag1', 'Tag2', 'Tag3']);
});
```

>If you pass more than 3 tags to the `tag` method it will only use the first 3, the others will be ignored.

### Campaigns
If you want your emails to be part of a campaign you created in Mailgun, you can add the campaign to a message with the `campaign` method.
This method accepts a single ID `string` or an `array` of ID's (with a maximum of 3)

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->campaign('my_campaign_id');
	//or
	$message->campaign(['campaign_1', 'campaign_2', 'campaign_3']);
});
```

### Tracking Clicks

Toggle clicks tracking on a per-message basis. Has higher priority than domain-level setting.

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->trackClicks(true);
	//or
	$message->trackClicks(false);
});
```

### Tracking Opens
Toggle opens tracking on a per-message basis. Has higher priority than domain-level setting.

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->trackOpens(true);
	//or
	$message->trackOpens(false);
});
```

### DKIM
Enable/disable DKIM signatures on per-message basis. ([see Mailgun Docs](http://documentation.mailgun.com/user_manual.html#verifying-your-domain))

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->dkim(true);
	// or
	$message->dkim(false);
});
```

### Testmode

You can send messages in test mode. When you do this, Mailgun will accept the message but will not send it. This is useful for testing purposes.

> Note: _You are charged for messages sent in test mode_.

To enabled testmode for all emails set the `testmode` option in the config file to `true`.

To enabled/disable testmode on a per message basis:

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->testmode(true);
	// or
	$message->testmode(false);
});
```
#### Alternative
Set the endpoint to Mailgun's Postbin. A Postbin is a web service that allows you to post data, which is then displayed through a browser. This allows you to quickly determine what is actually being transmitted to Mailgun's API. 

##### Step 1 - Create a new Postbin.

Go to http://bin.mailgun.net. The Postbin will generate a special URL. Save that URL.

##### Step 2 - Configure the Mailgun client for using Postbin.

> Tip: The bin id will be the URL part after bin.mailgun.net. It will be random generated letters and numbers. For example, the bin id in this URL, http://bin.mailgun.net/aecf68de, is "aecf68de".

In your `config/mailgun.php`, change the following

```php
'api' => [
    'endpoint' => 'api.mailgun.net',
    'version' => 'v3',
    'ssl' => true
],
```

to:

```php
'api' => [
    'endpoint' => 'bin.mailgun.net',
    'version' => 'abc1de23', // your Bin ID
    'ssl' => false
],
```
Now, all requests will be posted to the specified Postbin where you can review its contents.

### Header

Add a custom header to your message

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->header($name, $value);
});
```


### Data

Add custom data to your message

```php
Mailgun::send('emails.welcome', $data, function ($message) {
	$message->data($key, $value);
});
```

---
<br /><br />

## Dependency Injection

All the examples in this document are using the `Mailgun` facade.
The Mailgun service is registered in the Container as `mailgun` but you can also use the Interface `Bogardo\Mailgun\Contracts\Mailgun` for dependency injection in your app.

#### Example

```php
namespace App\Http\Controllers;

class CustomController extends Controller
{

    /**
     * @var \Bogardo\Mailgun\Contracts\Mailgun
     */
    protected $mailgun;

    /**
     * @param \Bogardo\Mailgun\Contracts\Mailgun $mailgun
     */
    public function __construct(\Bogardo\Mailgun\Contracts\Mailgun $mailgun)
    {
        $this->mailgun = $mailgun;
    }
    
    public function index()
    {
        $this->mailgun->send($view, $data, $callback);
    }
}
```



<br /><br />

## Mailing lists

You can programmatically create mailing lists using [Mailgun Mailing List API](https://documentation.mailgun.com/api-mailinglists.html#mailing-lists). A mailing list is a group of members (recipients) which itself has an email address, like developers@example.com. This address becomes an ID for this mailing list.

When you send a message to developers@example.com, all members of the list will receive a copy of it.

Complete support of the Mailing List API is not included in this package.
Though, you can communicate with the API using this package which should give you all the flexibility you need.

#### Some examples

For a full overview of all the available endpoints and the accepted parameters <br />please review the [Official API Documentation](https://documentation.mailgun.com/api-mailinglists.html#mailing-lists)

##### Get all lists (paginated)
```php
Mailgun::api()->get("lists/pages");
```

##### Get a list by address
```php
Mailgun::api()->get("lists/{$list}");
```

##### Create a new list
```php
Mailgun::api()->post("lists", [
    'address'      => 'developers@example.com',
    'name'         => 'Developers',
    'description'  => 'Developers Mailing List',
    'access_level' => 'readonly'
]);
```

##### Update a member of a list
```php
Mailgun::api()->put("lists/{$list}/members/{$member}", [
    'address'      => 'new-email@example.com',
    'name'         => 'John Doe',
    'vars'         => json_encode(['age' => 35, 'country' => 'US']),
    'subscribed'   => 'no'
]);
```

Again, for a full overview of all the available endpoints and the accepted parameters <br />please review the [Official API Documentation](https://documentation.mailgun.com/api-mailinglists.html#mailing-lists)

---

<br /><br />

## OptInHandler

Utility for generating and validating an OptIn hash.

The typical flow for using this utility would be as follows:

> Registration

1. Recipient Requests Subscription
2. Generate OptIn Link (with `OptInHandler`)
3. Email Recipient OptIn Link

> Validation

1. Recipient Clicks OptIn Link
2. Validate OptIn Link (with `OptInHandler`)
3. Subscribe User

###### Example

```php
$secretKey   = 'a_very_secret_key';
```

> Registration

```php
$listaddress = 'mailinglist@example.com';
$subscriber  = 'recipient@example.com';

$hash = Mailgun::optInHandler()->generateHash($listaddress, $secretKey, $subscriber);

var_dump($hash);
string 'eyJoIjoiODI2YWQ0OTRhNzkxMmZkYzI0MGJjYjM2MjFjMzAyY2M2YWQxZTY5MyIsInAiOiJleUp5SWpvaWNtVmphWEJwWlc1MFFHVjRZVzF3YkdVdVkyOXRJaXdpYkNJNkltMWhhV3hwYm1kc2FYTjBRR1Y0WVcxd2JHVXVZMjl0SW4wPSJ9' (length=180)
```

> Validation

```php
$result = Mailgun::optInHandler()->validateHash($secretKey, $hash);

var_dump($result);
array (size=2)
	'recipientAddress' => string 'recipient@example.com' (length=21)
	'mailingList' => string 'mailinglist@example.com' (length=23)
  
// Subscribe the user to the mailinglist
Mailgun::api()->post("lists/{$result['mailingList']}/members", [
    'address' => $result['recipientAddress'],
    'subscribed' => 'yes'
]);
```

---

<br /><br />

## Email Validation

Mailgun offers an email validation service which checks an email address on the following:

* Syntax checks (RFC defined grammar)
* DNS validation
* Spell checks
* Email Service Provider (ESP) specific local-part grammar (if available).

#### Single address ####
Validation a single address:

```php
Mailgun::validator()->validate("foo@bar.com");
```

The `validate` method returns the following object:

```php
stdClass Object
(
    [address] => foo@bar.com
    [did_you_mean] => 
    [is_valid] => 1
    [parts] => stdClass Object
        (
            [display_name] => 
            [domain] => bar.com
            [local_part] => foo
        )

)
```

It will also try to correct typo's:

```php
Mailgun::validator()->validate("foo@gmil.com")
```
returns:

```php
stdClass Object
(
    [address] => foo@gmil.com
    [did_you_mean] => foo@gmail.com
    [is_valid] => 1
    [parts] => stdClass Object
        (
            [display_name] => 
            [domain] => gmil.com
            [local_part] => foo
        )

)
```
#### Multiple addresses ####
To validate multiple addresses you can use the `parse` method.

This parses a delimiter separated list of email addresses into two lists: parsed addresses and unparsable portions. The parsed addresses are a list of addresses that are syntactically valid (and optionally have DNS and ESP specific grammar checks) the unparsable list is a list of characters sequences that the parser was not able to understand. These often align with invalid email addresses, but not always. Delimiter characters are comma (,) and semicolon (;).

The `parse` method accepts two arguments:

* `addresses`: An array of addresses **or** a delimiter separated string of addresses
* `syntaxOnly`: Perform only syntax checks or DNS and ESP specific validation as well. (true by default)

**Syntax only validation:**

```php
$addresses = 'Alice <alice@example.com>,bob@example.com,example.com';
//or
$addresses = [
	'Alice <alice@example.com>',
	'bob@example.com',
	'example.com'
];

Mailgun::validator()->parse($addresses);
```
returns:

```php
stdClass Object
(
    [parsed] => Array
        (
            [0] => Alice <alice@example.com>
            [1] => bob@example.com
        )

    [unparseable] => Array
        (
            [0] => example.com
        )

)
```

**Validation including DNS and ESP validation:**

```php
$addresses = 'Alice <alice@example.com>,bob@example.com,example.com';
Mailgun::validator()->parse($addresses, false);
```
returns:

```php
stdClass Object
(
    [parsed] => Array
        (
        )

    [unparseable] => Array
        (
            [0] => Alice <alice@example.com>
            [1] => bob@example.com
            [2] => example.com
        )

)
```

<br /><br />

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
