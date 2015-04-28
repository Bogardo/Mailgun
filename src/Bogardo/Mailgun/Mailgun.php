<?php namespace Bogardo\Mailgun;

use Bogardo\Mailgun\Mailgun\Lists;
use Bogardo\Mailgun\Mailgun\MailgunApi;
use Config;
use Closure;
use Illuminate\View\Factory;
use Bogardo\Mailgun\Mailgun\Message;

class Mailgun extends MailgunApi
{

	/**
	 * The view environment instance.
	 *
	 * @var \Illuminate\View\Factory
	 */
	protected $views;

	/**
	 * The global from address and name.
	 *
	 * @var array
	 */
	protected $from;

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
     * Mailgun lists Object
     *
     * @var \Bogardo\Mailgun\Mailgun\Lists
     */
    protected $lists;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param  \Illuminate\View\Factory $views
	 *
	 * @return \Bogardo\Mailgun\Mailgun
	 */
    public function __construct(Factory $views)
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

		$this->message = new Message();
	}

	/**
	 * Set the global from address and name.
	 *
	 * @return void
	 */
	protected function alwaysFrom()
	{
		$name = $this->from['name'];
		$email = $this->from['address'];
		$this->message->from($email, $name);
	}

	/**
	 * Send a new message
	 *
	 * @param  string|array   $view
	 * @param  array          $data
	 * @param  Closure|string $callback
	 * @param bool            $mustInit
	 *
	 * @return object Mailgun response containing http_response_body and http_response_code
	 */
	public function send($view, array $data, $callback, $mustInit = true)
	{
		if ($mustInit) $this->_init();

		$this->callMessageBuilder($callback, $this->message);

		$this->getMessage($view, $data);

		return $this->mailgun(true, Config::get('mailgun::api_key'))->sendMessage(Config::get('mailgun::domain'), $this->getMessageData(), $this->getAttachmentData());
	}

	/**
	 * @param $address
	 *
	 * @return mixed
	 */
	public function validate($address)
	{
		$data = $this->mailgun(null, Config::get('mailgun::public_api_key'))->get("address/validate", ['address' => $address]);
		return $data->http_response_body;
	}

	/**
	 * @param      $addresses
	 * @param bool $syntaxOnly
	 *
	 * @return mixed
	 */
	public function parse($addresses, $syntaxOnly = true)
	{
		if (is_array($addresses)) {
			$addresses = implode(',', $addresses);
		}

		if ($syntaxOnly === true) {
			$syntaxOnly = 'true';
		} else {
			$syntaxOnly = 'false';
		}

		$data = $this->mailgun(null, Config::get('mailgun::public_api_key'))->get("address/parse", ['addresses' => $addresses, 'syntax_only' => $syntaxOnly]);
		return $data->http_response_body;
	}

	/**
	 * Queue a new e-mail message for sending after (n) seconds/minutes/hours/days.
	 *
	 * @param  int|string|array $time
	 * @param  string|array     $view
	 * @param  array            $data
	 * @param  Closure|string   $callback
	 *
	 * @return object Mailgun response containing http_response_body and http_response_code
	 */
	public function later($time, $view, array $data, $callback)
	{
		$this->_init();
		$this->message->setDeliveryTime($time);
		return $this->send($view, $data, $callback, false);
	}

    /**
     * Access mailinglists
     *
     * @return \Bogardo\Mailgun\Mailgun\Lists
     */
    public function lists()
    {
        return $this->lists = new Lists();
    }

    /**
     * OptInHandler
     *
     * @return \Mailgun\Lists\OptInHandler
     */
    public function optInHandler()
    {
        return $this->mailgun()->OptInHandler();
    }

	/**
	 * Get HTML and/or Text message
     *
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
     *
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
     *
	 * @return array Message object casted to array
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

		return (array)$this->message;
	}

	/**
	 * Get attachment data
     *
	 * @return array
	 */
	protected function getAttachmentData()
	{
		return (array)$this->attachment;
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param  Closure $callback
	 * @param  \Bogardo\Mailgun\Mailgun\Message $message
	 * @return mixed
	 */
	protected function callMessageBuilder(Closure $callback, $message)
	{
		return call_user_func($callback, $message);
	}

	/**
	 * Render the given view.
	 *
	 * @param  string $view
	 * @param  array $data
	 * @return \Illuminate\View\View
	 */
	protected function getView($view, $data)
	{
		return $this->views->make($view, $data)->render();
	}

}
