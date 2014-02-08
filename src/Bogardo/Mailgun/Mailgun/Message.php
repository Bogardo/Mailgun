<?php namespace Bogardo\Mailgun\Mailgun;

use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class Message {

	/**
	 * Set default values
	 */
	public function __construct()
	{

		//Set parameters from config
		$this->setConfigReplyTo();
		$this->setNativeSend();
	}

	/**
	 * Add a "from" address to the message.
	 *
	 * @param  string  $email
	 * @param  string  $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function from($email, $name = false)
	{
		if ($name) {
			$this->from = "{$name} <{$email}>";
		} else {
			$this->from = "{$email} <{$email}>";
		}
		return $this;
	}

	/**
	 * Add a recipient to the message.
	 *
	 * @param  string  $email
	 * @param  string  $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function to($email, $name = false)
	{
		if ($name) {
			$this->to = "{$name} <{$email}>";
		} else {
			$this->to = "{$email}";
		}
		return $this;
	}

	/**
	 * Add a carbon copy to the message.
	 *
	 * @param  string  $email
	 * @param  string  $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function cc($email, $name = false)
	{
		if ($name) {
			$this->cc = "{$name} <{$email}>";
		} else {
			$this->cc = "{$email}";
		}
		return $this;
	}

	/**
	 * Add a blind carbon copy to the message.
	 *
	 * @param  string  $email
	 * @param  string  $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function bcc($email, $name = false)
	{
		if ($name) {
			$this->bcc = "{$name} <{$email}>";
		} else {
			$this->bcc = "{$email}";
		}
		return $this;
	}
	
	/**
	 * Add a reply to address to the message.
	 *
	 * @param  string  $email
	 * @param  string  $name
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */	
	public function replyTo($email, $name = false)
	{
		if ($name) {
			$this->{'h:Reply-To'} = "{$name} <{$email}>";
		} else {
			$this->{'h:Reply-To'} = $email;	
		}
		
		return $this;
	}

	/**
	 * Set the HTML body for the message.
	 *
	 * @param  string  $html
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
	 * @param  string  $text
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
	 * @param  string  $subject
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
	 * @param  string|array  $tags
	 * @return \Bogardo\Mailgun\Mailgun\Message
	 */
	public function tag($tags)
	{
		$tagsArray = array_slice((array)$tags, 0, 3);
		$this->{'o:tag'} = $tagsArray;
		return $this;
	}

	/**
	 * Attach a file to the message.
	 *
	 * @param  string  $path
	 * @param  string  $name
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
	 * @param  string  $path
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
	 * @param  string|int|array  $time
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

}