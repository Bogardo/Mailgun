<?php namespace Bogardo\Mailgun;

use Closure;
use Mailgun\Mailgun as Mg;
use Illuminate\View\Environment;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;

class Mailgun {

	/**
	 * The view environment instance.
	 *
	 * @var \Illuminate\View\Environment
	 */
	protected $views;

	/**
	 * The global from address and name.
	 *
	 * @var array
	 */
	protected $from;

	/**
	 * Mailgun Object
	 *
	 * @var \Admin\Mail\Mailgun
	 */
	protected $mailgun;

	/**
	 * Mailgun message Object
	 *
	 * @var \Admin\Mail\Mailgun\Message
	 */
	protected $message;

	/**
	 * Mailgun attachment Object
	 *
	 * @var \Admin\Mail\Mailgun\Attachment
	 */
	protected $attachment;	

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container
	 */
	protected $container;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param  \Illuminate\View\Environment  $views
	 * @return void
	 */
	public function __construct(Environment $views)
	{
		$this->views = $views;

		$this->from = Config::get('mailgun::from');

		$this->mailgun = new Mg(Config::get('mailgun::api_key'));

		$this->message = new Mailgun\Message();
	}

	/**
	 * Set the global from address and name.
	 *
	 * @param  string  $address
	 * @param  string  $name
	 * @return void
	 */
	protected function alwaysFrom()
	{
		$name 	= $this->from['name'];
		$email 	= $this->from['address'];
		$this->message->from($email, $name);
	}

	/**
	 * Send a new message
	 *
	 * @param  string|array  $view
	 * @param  array  $data
	 * @param  Closure|string  $callback
	 * @return int
	 */
	public function send($view, array $data, $callback)
	{
		$this->callMessageBuilder($callback, $this->message);

		$this->getMessage($view, $data);

		return $this->mailgun->sendMessage(Config::get('mailgun::domain'), $this->getMessageData(), $this->getAttachmentData());
	}

	public function later($time, $view, array $data, $callback)
	{
		$this->message->setDeliveryTime($time);
		return $this->send($view, $data, $callback);
	}

	/**
	 * Get HTML and/or Text message
	 */
	protected function getMessage($view, $data)
	{
		$data['message'] = $this->message;
		if (is_string($view)) {
			$this->getHtmlMessage($view, $data);
		}

		if (is_array($view) and isset($view[0])) {
			$this->getHtmlMessage($view[0], $data);
			if (isset($view[1])) {
				$this->getTextMessage($view[1], $data);
			}
		} elseif (is_array($view)) {
			if (isset($view['html'])) {
				$this->getHtmlMessage($view['html'], $data);
			}
			if (isset($view['text'])) {
				$this->getTextMessage($view['text'], $data);
			}
		}
	}

	protected function getHtmlMessage($view, $data)
	{
		$renderedView = $this->getView($view, $data);
		$this->message->html($renderedView);
	}

	protected function getTextMessage($view, $data)
	{
		$renderedView = $this->getView($view, $data);
		$this->message->text($renderedView);
	}

	protected function getMessageData()
	{
		if (!isset($this->message->from)) {
			$this->alwaysFrom();
		}

		if (isset($this->message->attachment)) {
			$this->attachment = $this->message->attachment;
			unset($this->message->attachment);
		}
		
		return (array) $this->message;
	}

	protected function getAttachmentData()
	{
		return (array) $this->attachment;
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param  Closure|string  $callback
	 * @param  \Illuminate\Mail\Message  $message
	 * @return mixed
	 */
	protected function callMessageBuilder($callback, $message)
	{
		if ($callback instanceof Closure)
		{
			return call_user_func($callback, $message);
		}
		elseif (is_string($callback))
		{
			return $this->container[$callback]->mail($message);
		}

		throw new \InvalidArgumentException("Callback is not valid.");
	}

	/**
	 * Render the given view.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @return \Illuminate\View\View
	 */
	protected function getView($view, $data)
	{
		return $this->views->make($view, $data)->render();
	}

}