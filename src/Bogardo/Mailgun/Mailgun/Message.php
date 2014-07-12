<?php namespace Bogardo\Mailgun\Mailgun;

use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class Message
{

	public $variables = array();

	/**
	 * Set default values
	 */
	public function __construct()
	{

		//Set parameters from config
		$this->setConfigReplyTo();
		$this->setNativeSend();
		$this->setTestMode();
	}

	/**
	 * Add a "from" address to the message.
	 *
	 * @param  string $email
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function from($email, $name = false)
	{
		if ($name) {
			$this->from = "'{$name}' <{$email}>";
		} else {
			$this->from = "{$email}";
		}
		return $this;
	}

	/**
	 * Add a recipient to the message.
	 *
	 * @param  string|array $email
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function to($email, $name = false)
	{
		if (is_array($email)) {
			foreach ($email as $key => $recipient) {

				$recipient = $this->parseRecipientVariables($recipient, $key);

				$this->addRecipient('to', $recipient);
			}
		} else {
			$this->addRecipient('to', $email, $name);
		}
		return $this;
	}

	/**
	 * Add a carbon copy to the message.
	 *
	 * @param  string|array $email
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function cc($email, $name = false)
	{
		if (is_array($email)) {
			foreach ($email as $recipient) {
				$this->addRecipient('cc', $recipient);
			}
		} else {
			$this->addRecipient('cc', $email, $name);
		}
		return $this;
	}

	/**
	 * Add a blind carbon copy to the message.
	 *
	 * @param  string|array $email
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function bcc($email, $name = false)
	{
		if (is_array($email)) {
			foreach ($email as $recipient) {
				$this->addRecipient('bcc', $recipient);
			}
		} else {
			$this->addRecipient('bcc', $email, $name);
		}
		return $this;
	}

	/**
	 * Parse optional recipient variables
	 *
	 * @param string|array $recipient
	 * @param int|string $key
	 * @return string
	 */
	protected function parseRecipientVariables($recipient, $key)
	{
		if (is_array($recipient)) {
			$this->prepareRecipientVariables($key, $recipient);
			$recipient = $key;
		}
		return $recipient;
	}

	/**
	 * Save recipient variables for later reference
	 *
	 * @param string $email
	 * @param array $variables
	 */
	protected function prepareRecipientVariables($email, $variables)
	{
		$email = $this->getEmailFromString($email);
		$this->variables[$email] = $variables;
	}

	/**
	 * Encode and register recipient variables
	 *
	 * @param array $variables
	 */
	public function recipientVariables(array $variables)
	{
		if (is_array($variables)) {
			$this->{'recipient-variables'} = json_encode($variables);
		}
	}

	/**
	 * Get email adress from string
	 * i.e.: "Foo Bar <foo@bar.com>" returns "foo@bar.com"
	 *
	 * @param $string
	 * @return string
	 */
	protected function getEmailFromString($string)
	{
		foreach(preg_split('/\s/', $string) as $token) {
			$email = filter_var(filter_var($token, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);
			if ($email !== false) {
				return $email;
			}
		}
		return $string;
	}

	/**
	 * Add recipient to message
	 *
	 * @param string $type to or cc or bcc
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function addRecipient($type, $email, $name = false)
	{
		$email = $this->checkCatchAll($email);

		if ($name) {
			$recipient = "'{$name}' <{$email}>";
		} else {
			$recipient = "{$email}";
		}

		if (!empty($this->{$type})) {
			$this->{$type} .= ', ' . $recipient;
		} else {
			$this->{$type} = $recipient;
		}
	}

	/**
	 * Add a reply-to address to the message.
	 *
	 * @param  string $email
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function replyTo($email, $name = false)
	{
		if ($name) {
			$this->{'h:Reply-To'} = "'{$name}' <{$email}>";
		} else {
			$this->{'h:Reply-To'} = $email;
		}

		return $this;
	}

	/**
	 * Set the HTML body for the message.
	 *
	 * @param  string $html
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function html($html)
	{
		$this->html = $html;
		return $this;
	}

	/**
	 * Set the text body for the message.
	 *
	 * @param  string $text
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function text($text)
	{
		$this->text = $text;
		return $this;
	}

	/**
	 * Set the subject of the message.
	 *
	 * @param  string $subject
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function subject($subject)
	{
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Add (mailgun)tags to the message.
	 * Tag limit is 3
	 *
	 * @param  string|array $tags
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function tag($tags)
	{
		$tagsArray = array_slice((array)$tags, 0, 3);
		$this->{'o:tag'} = $tagsArray;
		return $this;
	}

    /**
     * Add a Mailgun campaign ID(s) to the message
     *
     * @param mixed $value  an array of id's (maximum of 3) or a single id
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
    public function campaign($value)
    {
        $this->{'o:campaign'} = $value;
        return $this;
    }

    /**
     * Enables/disables DKIM signatures on per-message basis.
     *
     * @param bool $enabled
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
    public function dkim($enabled)
    {
        $enabled = ($enabled === true ? 'yes' : 'no');
        $this->{'o:dkim'} = $enabled;
        return $this;
    }

    /**
     * Toggles tracking on a per-message basis
     *
     * @param bool $enabled
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
    public function tracking($enabled)
    {
        $enabled = ($enabled === true ? 'yes' : 'no');
        $this->{'o:tracking'} = $enabled;
        return $this;
    }

    /**
     * Toggles clicks tracking on a per-message basis. Has higher priority than domain-level setting.
     * Pass 'true', 'false' or 'htmlonly'.
     *
     * @param mixed $value
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
    public function trackClicks($value)
    {
        $value = ($value === 'htmlonly' ?: ($value === true ? 'yes' : 'no'));
        $this->{'o:tracking-clicks'} = $value;
        return $this;
    }

    /**
     * Toggles opens tracking on a per-message basis. Has higher priority than domain-level setting.
     *
     * @param bool $enabled
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
    public function trackOpens($enabled)
    {
        $enabled = ($enabled === true ? 'yes' : 'no');
        $this->{'o:tracking-opens'} = $enabled;
        return $this;
    }

	/**
	 * Add custom data to a message
	 *
	 * @param $key
	 * @param $data
	 *
	 * @return $this
	 */
	public function data($key, $data)
	{
		$this->{"v:$key"} = json_encode($data);
		return $this;
	}

	/**
	 * Attach a file to the message.
	 *
	 * @param  string $path
	 * @param  string $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function attach($path, $name = null)
	{
		$attachment = new Attachment($path, $name);

		$this->attachment->attachment[] = $attachment->getAttachment();

		return $this;
	}

	/**
	 * Embed a file in the message and get the CID.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function embed($path, $name = null)
	{
		$inline = new Inline($path, $name);

		$this->attachment->inline[] = $inline->getAttachment();

		return 'cid:' . $inline->getCid();
	}

	/**
	 * Set the delivery time of the message.
	 *
	 * @param  string|int|array $time
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function setDeliveryTime($time)
	{
		if (is_array($time)) {
			reset($time);
			$type = key($time);
			$amount = $time[$type];
		} else {
			$type = 'seconds';
			$amount = $time;
		}

		$now = Carbon::now(Config::get('app.timezone', 'UTC'));
		$deliveryTime = Carbon::now(Config::get('app.timezone', 'UTC'));

		switch ($type) {
			case 'seconds':
				$deliveryTime->addSeconds($amount);
				break;
			case 'minutes':
				$deliveryTime->addMinutes($amount);
				break;
			case 'hours':
				$deliveryTime->addHours($amount);
				break;
			case 'days':
				$deliveryTime->addHours($amount * 24);
				break;
			default:
				$deliveryTime->addSeconds($amount);
				break;
		}

		//Calculate boundaries
		$max = Carbon::now()->addHours(3 * 24);
		if ($deliveryTime->gt($max)) {
			$deliveryTime = $max;
		} elseif ($deliveryTime->lt($now)) {
			$deliveryTime = $now;
		}

		$this->{'o:deliverytime'} = $deliveryTime->format('D, j M Y H:i:s O');
		return $this;
	}

    /**
     * Manually enable or disable testmode
     *
     * @param bool $inEnabled
     * @return \Bogardo\Mailgun\Mailgun\Message
     */
	public function testmode($inEnabled = false)
	{
		$this->{'o:testmode'} = $inEnabled;
        return $this;
	}

	/**
	 * Set default reply to address from the config
	 */
	protected function setConfigReplyTo()
	{
		$replyTo = Config::get('mailgun::reply_to');
		if ($replyTo) {
			$this->replyTo($replyTo);
		}
	}

	/**
	 * Force the from address (see description in config)
	 */
	protected function setNativeSend()
	{
		if (Config::get('mailgun::force_from_address')) {
			$this->{'o:native-send'} = 'yes';
		}
	}

	/**
	 * Enable/Disable testmode depending on config setting
	 */
	protected function setTestMode()
	{
		if (Config::get('mailgun::testmode')) {
			$this->{'o:testmode'} = true;
		}
	}

	/**
	 * Checks the config file for a catch_all email address
	 * If this is set it will overwrite all email addresses
	 * in a message. All recipient name will stay intact.
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	protected function checkCatchAll($email)
	{
		$catchAllMail = Config::get('mailgun::catch_all');
		if ($catchAllMail) {
			$extractedEmail = $this->getEmailFromString($email);
			return str_replace($extractedEmail, $catchAllMail, $email);
		}
		return $email;
	}

}
