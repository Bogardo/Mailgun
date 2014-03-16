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
	 * @var \Bogardo\Mailgun\Mailgun
	 */
	protected $mailgun;

	/**
	 * Mailgun message Object
	 *
	 * @var \Bogardo\Mailgun\Mailgun\Message
	 */
	protected $message;

	/**
	 * Mailgun attachment Object
	 *
	 * @var array
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
	 * @param  \Illuminate\View\Environment $views
	 * @return void
	 */
	public function __construct(Environment $views)
	{
		$this->views = $views;
	}

	/**
 	* Initialise message configuration
 	* @return void
 	*/
	protected function _init()
	{
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
	 * @return object Mailgun response containing http_response_body and http_response_code
	 */
	public function send($view, array $data, $callback, $mustInit = true)
	{
		if ($mustInit) $this->_init();

		$this->callMessageBuilder($callback, $this->message);

		$this->getMessage($view, $data);

		return $this->mailgun->sendMessage(Config::get('mailgun::domain'), $this->getMessageData(), $this->getAttachmentData());
	}

	/**
	 * Queue a new e-mail message for sending after (n) seconds/minutes/hours/days.
	 *
	 * @param  int|string|array  $delay
	 * @param  string|array  $view
	 * @param  array  $data
	 * @param  Closure|string  $callback
	 * @return object Mailgun response containing http_response_body and http_response_code
	 */
	public function later($time, $view, array $data, $callback)
	{
		$this->_init();
		$this->message->setDeliveryTime($time);
		return $this->send($view, $data, $callback, false);
	}

	/**
	 * Get HTML and/or Text message
	 * @param  string $view
	 * @param  array $data
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

	/**
	 * Get rendered HTML body
	 * @param  string $view
	 * @param  array $data
	 */
	protected function getHtmlMessage($view, $data)
	{
		$renderedView = $this->getView($view, $data);
		$this->message->html($renderedView);
	}

	/**
	 * Get rendered text body
	 * @param  string $view
	 * @param  array $data
	 */
	protected function getTextMessage($view, $data)
	{
		$renderedView = $this->getView($view, $data);
		$this->message->text($renderedView);
	}

	/**
	 * Get message data
	 * @return array \Bogardo\Mailgun\Mailgun\Message object casted to array
	 */
	protected function getMessageData()
	{
		//Check `from` address
		if (!isset($this->message->from)) {
			$this->alwaysFrom();
		}

		//Check recipient variables
		if (!isset($this->message->{'recipient-variables'}) && !empty($this->message->variables)) {
			$this->message->recipientVariables($this->message->variables);
		}
		unset($this->message->variables);

		//Get attachment data
		$this->attachment = null;
		if (isset($this->message->attachment)) {
			$this->attachment = $this->message->attachment;
			unset($this->message->attachment);
		}
		
		return (array) $this->message;
	}

	/**
	 * Get attachment data
	 * @return array
	 */
	protected function getAttachmentData()
	{
		return (array) $this->attachment;
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param  Closure|string  $callback
	 * @param  \Bogardo\Mailgun\Mailgun\Message  $message
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