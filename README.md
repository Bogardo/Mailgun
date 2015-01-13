Bogardo/Mailgun
=======

A Mailgun package for Laravel 4 for sending emails using the Mailgun HTTP API.
It's main advantage is that the syntax is the same as the Laravel Mail component and I also tried to give it very simmilar functionality. So if you've used that component before, using the Mailgun package should be a breeze.

> This package makes use of the [Mailgun-PHP](https://github.com/mailgun/mailgun-php) library.<br />

<br />
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bogardo/Mailgun?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)
[![Total Downloads](https://poser.pugx.org/bogardo/mailgun/downloads.png)](https://packagist.org/packages/bogardo/mailgun) [![Monthly Downloads](https://poser.pugx.org/bogardo/mailgun/d/monthly.png)](https://packagist.org/packages/bogardo/mailgun)

## Table of contents ##
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
    - [Messages](#messages) 	
	    - [Views](#views)
	    - [Data](#data)
	    - [Mail options](#mail-options)
		    - [Recipients](#recipients)
		    - [Sender](#sender)
		    - [Subject](#subject)
		    - [Reply-to](#reply-to)
	    - [Attachments](#attachments)
	    - [Embedding Inline Images](#embedding-inline-images)
    	- [Scheduling](#scheduling)
	    - [Tagging](#tagging)
	    - [Campaigns](#campaigns)
	    - [Tracking](#tracking)
	    - [Dkim](#dkim)
	    - [Testmode](#testmode)
	    - [Catch all](#catch-all)
	    - [Custom Data](#custom-data)
	- [Mailing lists](#mailing-lists)
        - [All](#all)
        - [Get](#get)
        - [Create](#create)
        - [Update](#update)
        - [Delete](#delete)
        - [Get members](#get-members)
        - [Get member](#get-member)
        - [Add member](#add-member)
        - [Add members](#add-members)
        - [Update member](#update-member)
        - [Delete member](#delete-member)
	- [OptInHandler](#optinhandler)
	- [Email Validation](#email-validation)

## Installation ##

Open your `composer.json` file and add the following to the `require` key:

### Laravel 4.2 ###

	"bogardo/mailgun": "3.1.*"

### Laravel 4.1/4.0 ###

	"bogardo/mailgun": "2.*"

---

After adding the key, run composer update from the command line to install the package 

```bash
composer update
```

Add the service provider to the `providers` array in your `app/config/app.php` file.

    'Bogardo\Mailgun\MailgunServiceProvider'

## Configuration ##
Before you can start using the package we need to set some configurations.
To do so you must first publish the config file, you can do this with the following `artisan` command. 

```bash
php artisan config:publish bogardo/mailgun
```
After the config file has been published you can find it at: `app/config/packages/bogardo/mailgun/config.php`

In it you must specify the `from` details, your Mailgun `api key` and the Mailgun `domain`.

## Usage ##

## Messages ##
The Mailgun package offers most of the functionality as the Laravel 4 [Mail component](http://laravel.com/docs/mail).

The `Mailgun::send` method may be used to send an e-mail message:

```php
Mailgun::send('emails.welcome', $data, function($message)
{
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
```

### Views ###

The first argument passed to the `send` method is the name of the view that should be used as the e-mail body.
Mailgun supports 2 types of bodies: `text` and `html`.
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

The second argument passed to the `send` method is the `$data` `array` that is passed to the view.

> Note: A `$message` variable is always passed to e-mail views, and allows the inline embedding of attachments. So, it is best to avoid passing a (custom) `message` variable in your view payload.

You can access the values from the `$data` array as variables using the array key.

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
    Hi {{ $customer }},
	Please visit {{ $url }}
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

###### Batch Sending ######
To send an email to multiple recipients you can also pass an `array` as the first parameter to the `to`, `cc` and/or `bcc` methods.

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->to(array(
		'foo@bar.com',
		'bar@foo.com'
	));
});
```
The array should only contain `strings` with the email address.
If you still want to be able to set the recipient name there are two options:
- Call the `to` method multiple times:
```php
Mailgun::send('emails.welcome', $data, function($message) use ($users)
{  
	foreach ($users as $user) {
		$message->to($user->email, $user->name);
	}
});
``` 
- Give the strings in the `array` the correct format for including names: `'name' <email>`
```php
array(
	"'Mr. Bar' <foo@bar.com>",
	"'Ms. Foo' <bar@foo.com>"
);
```
>Note: Mailgun limits the number of recipients per message to 1000

##### Sender #####
In the Mailgun config file you have specified the `from` address. If you would like, you can override this using the `from` method. It accepts two arguments: `email` and `name` where the `name` field is optional.

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

##### Reply-to #####
Setting a reply-to address
```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->replyTo('reply@example.com', 'Helpdesk');
});
```
>If the reply_to config setting is set, the reply-to will be automatically set for all messages
>You can overwrite this value by adding it to the message as displayed in the example.

### Attachments ###
To add an attachment to the email you can use the `attach` method. You can add multiple attachments.
> **Since mailgun-php 1.6, the ability to rename attachments has been added due to the upgrade to guzzle 1.8**

It accepts 2 arguments:
* $path | The path to the image
* $name (optional) | The _remote_ name of the file (attachment is renamed server side)

```php
Mailgun::send('emails.welcome', $data, function($message)
{  
    $message->attach($pathToFile);
});
```
<br />
> The to, cc, bcc, sender, from, subject etc... methods are all chainable:
> ```php
> $message
> 	->to('foo@example.com', 'Recipient Name')
> 	->cc('bar@example.com', 'Recipient Name')
> 	->subject('Email subject');
> ```

<br />

### Embedding Inline Images ###
Embedding inline images into your e-mails is very easy.
In your view you can use the `embed` method and pass it the path to the file. This will return a CID (Content-ID) which will be used as the `source` for the image. You can add multiple inline images to your message. 
> **Since mailgun-php 1.6, the ability to rename attachments has been added due to the upgrade to guzzle 1.8**

The `embed` method accepts 2 arguments:
* $path | The path to the image
* $name (optional) | The _remote_ name of the file (attachment is renamed server side)
```html
<body>
    <img src="{{ $message->embed($pathToFile) }}">
</body>
```
#### Example ####

###### Input ######
```php
$data = array(
	'img' => 'assets/img/example.png',
	'otherImg' => 'assets/img/foobar.jpg'
);

Mailgun::send('emails.welcome', $data, function($message)
{
	$message->to('foo@example.com', 'Recipient Name');
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
Sometimes it’s helpful to categorize your outgoing email traffic based on some criteria, perhaps for separate signup emails, password recovery emails or for user comments. Mailgun lets you tag each outgoing message with a custom tag. When you access the _Tracking_ page  within the Mailgun control panel, they will be aggregated by these tags.

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

### Campaigns ###
If you want your emails to be part of a campaign you created in Mailgun, you can add the campaign to a message with the `campaign` method.
This method accepts a single ID `string` or an `array` of ID's (with a maximum of 3)

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->campaign('my_campaign_id');
	//or
	$message->campaign(array('campaign_1', 'campaign_2', 'campaign_3'));
});
```

### Tracking ###
You can toggle tracking on a per message basis.

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->tracking(true);
	//or
	$message->tracking(false);
});
```

#### Tracking Clicks ####
Toggle clicks tracking on a per-message basis. Has higher priority than domain-level setting.

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->trackClicks(true);
	//or
	$message->trackClicks(false);
});
```

#### Tracking Opens ####
Toggle opens tracking on a per-message basis. Has higher priority than domain-level setting.

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->trackOpens(true);
	//or
	$message->trackOpens(false);
});
```

### DKIM ###
Enable/disable DKIM signatures on per-message basis. ([see Mailgun Docs](http://documentation.mailgun.com/user_manual.html#verifying-your-domain))
```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->dkim(true);
	//or
	$message->dkim(false);
});
```

### Testmode ###
You can send messages in test mode. When you do this, Mailgun will accept the message but will not send it. This is useful for testing purposes.

> Note You are charged for messages sent in test mode.

To enabled testmode for all emails set the `testmode` option in the config file to `true`.

To enabled/disable testmode on a per message basis:
```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->testmode(true);
	//or
	$message->testmode(false);
});
```

### Catch all ###
You can setup a catch-all address in the configuration file `catch_all`. 
When enabled, all email addresses will be replaced by the catch-all address specified in the configuration file.
This is useful for testing purposes.

### Custom Data ###
When sending, you can attach data to your messages. The data will be represented as a header within the email, X-Mailgun-Variables. The data is formatted in JSON and included in any webhook events related to the email containing the custom data.
See the [Mailgun Documentation](http://documentation.mailgun.com/user_manual.html#attaching-data-to-messages) for more detailed information.

To add custom data to a message you can use the `data` method.
This method takes two parameters `key` and `value`.
The `value` parameter will be json encoded 

```php
Mailgun::send('emails.welcome', $data, function($message)
{
	$message->data('key', 'value');
});
```

---

## Mailing lists ##
You can programmatically create mailing lists using Mailgun Mailing List API. A mailing list is a group of members (recipients) which itself has an email address, like developers@example.com. This address becomes an ID for this mailing list.

When you send a message to developers@example.com, all members of the list will receive a copy of it.

### All ###
Get all mailing lists

```php
Mailgun::lists()->all();
```

```php
Mailgun::lists()->all(['limit' => 5]);
```

##### Parameters
Parameter | Type | Description  | _
--------- | ---- | ------------ | ---
address  | string | Find a mailing list by its address | optional
limit    | int    | Maximum number of records to return (100 by default) | optional
skip     | int    | Records to skip (0 by default) | optional

Returns a Laravel `Collection` of `Mailinglist` objects.


### Get ###
Get a single mailing list by address

```php
Mailgun::lists()->get("developers@example.com");
```

Returns a single `Mailinglist` object.

### Create ###
Create a new mailing list

```php
Mailgun::lists()->create([
    'address' => 'developers@example.com'
]);
```

##### Parameters
Parameter | Type | Description | _
--------- | ---- | ----------- | ---
address  | string | A valid email address for the mailing list, e.g. `developers@example.com`, or `Developers <developers@example.com>` | required
name    | string    | Mailing list name, e.g. `Developers` | optional
description | string | A description for the mailing list | optional
access_level | string | List access level, one of: `readonly` (default), `members` or `everyone` | optional

Returns the newly created `Mailinglist` object

### Update ###
Update an existing mailing list

```php
Mailgun::lists()->update("developers@example.com", [
    'name' => 'New name',
    'description' => 'Test mailing list'
]);
```

Or if you already have the `Mailinglist` object available.

```php
$list = Mailgun::lists()->get("developers@example.com");

$list->update(['name' => 'New name']);
```

##### Parameters
Parameter | Type | Description | _
--------- | ---- | ----------- | ---
address  | string | New mailing list address, e.g. `devs@example.com` | optional
name    | string    | New name | optional
description | string | New description | optional
access_level | string | List access level, one of: `readonly` (default), `members` or `everyone` | optional

Returns the updated `Mailinglist` object.

### Delete ###
Delete a mailing list

```php
Mailgun::lists()->delete('developers@example.com');
```

Or if you already have the `Mailinglist` object available.

```php
$list = Mailgun::lists()->get("developers@example.com");

$list->delete();
```

Returns `true` if successfull.

### Get members ###
Get members for a mailing list

```php
Mailgun::lists()->members("developers@example.com");
```

```php
Mailgun::lists()->members("developers@example.com", [
    'subscribed' => true,
    'limit' => 10
]);
```

Or if you already have the `Mailinglist` object available.

```php
$list->members();
```

```php
$list->members([
    'subscribed' => true,
    'limit' => 10
]);
```

##### Parameters
Parameter | Type | Description | _
--------- | ---- | ----------- | ---
subscribed  | bool | `true` to list subscribed, `false` for unsubscribed, list all if not set | optional
limit    | int    | Maximum numbers of records to return (100 default) | optional
skip | int | Records to skip (0 default) | optional



Returns a Laravel `Collection` of `Member` objects.

### Get member ###
Get a single member from a mailing list

```php
Mailgun::lists()->member("developers@example.com", "user@example.com");
```

Or if you already have the `Mailinglist` object available.

```php
$list->member("user@example.com");
```

Returns a single `Member` object.

### Add member ###
Add a member to a mailing list

```php
Mailgun::lists()->addMember('developers@example.com', [
    'address' => 'user@example.com',
    'name' => 'John Doe',
    'vars' => ['age' => 43, 'gender' => 'male'],
    'subscribed' => true,
    'upsert' => true
]);
```

Or if you already have the `Mailinglist` object available.
```php
$list->addMember([
    'address' => 'user@example.com',
    'name' => 'John Doe',
    'vars' => ['age' => 43, 'gender' => 'male'],
    'subscribed' => true,
    'upsert' => true
]);
```

##### Parameters
Parameter | Type   | Description | _
--------- | ------ | ----------- | ---
address   | string | Valid email address, e.g. `John <john@example.com>` or just `john@example.com` | required
name      | string | Member name | optional
vars      | array  | array with arbitrary keys and values | optional
subscribed| bool   | `true` to add as subscribed (default), `false` as unsubscribed | optional
upsert    | bool   | `true` to update member if present, `false` to raise error in case of a duplicate member (default) | optional

Returns the newly created `Member` object.

### Add members ###
Add multiple members to a mailing list, up to a 1000 per call

```php
Mailgun::lists()->addMembers("developers@example.com", [
    [
        "address" => "jane@example.com",
        "name" => "Jane Doe"
    ],
    [
        "address" => "john@example.com",
        "name" => "John Doe",
        "vars" => [
            "age" => 48,
            "country" => "Japan"
        ]
    ]
], true);
```

##### Parameters
Parameter | Type   | Description | _
--------- | ------ | ----------- | ---
members   | array | array of members | required
upsert    | bool | `true` to update existing members, `false` to ignore duplicates (default) | optional

Returns the `Mailinglist` the members where added to

### Update member ###
Update an existing member of a mailing list

```php
Mailgun::lists()->updateMember('developers@example.com', 'user@example.com', [
    'address' => 'user@example.com',
    'name' => 'John Doe',
    'vars' => ['age' => 43, 'gender' => 'male'],
    'subscribed' => true,
]);
```

Or if you already have the `Mailinglist` object available.
```php
$list->updateMember('user@example.com', [
    'subscribed' => false,
]);
```

##### Parameters
Parameter | Type   | Description | _
--------- | ------ | ----------- | ---
address   | string | Valid email address, e.g. `John <john@example.com>` or just `john@example.com` | optional
name      | string | Member name | optional
vars      | array  | array with arbitrary keys and values | optional
subscribed| bool   | `true` to add as subscribed (default), `false` as unsubscribed | optional

Returns the updated `Member` object.

### Delete member ###
Delete a mailinglist member

```php
Mailgun::lists()->deleteMember("developers@example.com", "john@example.com");
```

Or if you already have the `Mailinglist` object available.
```php
$list->deleteMember('user@example.com');
```

Returns `true` if successfull.

---

## OptInHandler ##

Utility for generating and validating an OptIn hash.

The typical flow for using this utility would be as follows:

> Registration

1. Recipient Requests Subscribe
2. Generate OptIn Link (with `OptInHandler`)
3. Email Recipient OptIn Link

> Validation

1. Recipient Clicks OptIn Link
2. Validate OptIn Link (with `OptInHandler`)
3. Subscribe User

###### Examples

```php
$secretKey   = 'a_very_secret_key';
```

> Registration

```php
$listaddress = 'mailinglist@example.com';
$subscriber  = 'recipient@example.com';

$hash = Mailgun::optInHandler()->generateHash($listaddress, $secretKey, $subscriber);

var_dump($hash);
// string 'eyJoIjoiODI2YWQ0OTRhNzkxMmZkYzI0MGJjYjM2MjFjMzAyY2M2YWQxZTY5MyIsInAiOiJleUp5SWpvaWNtVmphWEJwWlc1MFFHVjRZVzF3YkdVdVkyOXRJaXdpYkNJNkltMWhhV3hwYm1kc2FYTjBRR1Y0WVcxd2JHVXVZMjl0SW4wPSJ9' (length=180)
```

> Validation

```php
$result = Mailgun::optInHandler()->validateHash($secretKey, $hash);

var_dump($result);
// array (size=2)
//   'recipientAddress' => string 'recipient@example.com' (length=21)
//   'mailingList' => string 'mailinglist@example.com' (length=23)
  
Mailgun::lists()->addMember($result['mailingList'], [
    'address' => $result['recipientAddress']
]);
```

---

## Email Validation ##
Mailgun offers an email validation service which checks an email address on the following:
* Syntax checks (RFC defined grammar)
* DNS validation
* Spell checks
* Email Service Provider (ESP) specific local-part grammar (if available).

#### Single address ####
Validation a single address:

```php
Mailgun::validate("foo@bar.com")
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
Mailgun::validate("foo@gmil.com")
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
$addresses = array(
	'Alice <alice@example.com>',
	'bob@example.com',
	'example.com'
);

Mailgun::parse($addresses);
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
Mailgun::parse($addresses, false);
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



<br />
[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/Bogardo/mailgun/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
