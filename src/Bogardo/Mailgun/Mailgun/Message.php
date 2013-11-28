<?php namespace Bogardo\Mailgun\Mailgun;

use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class Message {

	public function __construct()
	{

		//Set parameters from config
		$this->setReplyTo();
		$this->setNativeSend();
	}

	public function from($email, $name = false)
	{
		if ($name) {
			$this->from = "{$name} <{$email}>";
		} else {
			$this->from = "{$email} <{$email}>";
		}
		return $this;
	}

	public function to($email, $name = false)
	{
		if ($name) {
			$this->to = "{$name} <{$email}>";
		} else {
			$this->to = "{$email}";
		}
		return $this;
	}

	public function cc($email, $name = false)
	{
		if ($name) {
			$this->cc = "{$name} <{$email}>";
		} else {
			$this->cc = "{$email}";
		}
		return $this;
	}

	public function bcc($email, $name = false)
	{
		if ($name) {
			$this->bcc = "{$name} <{$email}>";
		} else {
			$this->bcc = "{$email}";
		}
		return $this;
	}
	
	public function replyTo($email)
	{
		$this->{'h:Reply-To'} = $email;
		return $this;
	}

	public function html($html)
	{
		$this->html = $html;
		return $this;
	}

	public function text($text)
	{
		$this->text = $text;
		return $this;
	}

	public function subject($subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function tag($tags)
	{
		$tagsArray = array_slice((array)$tags, 0, 3);
		$this->{'o:tag'} = $tagsArray;
		return $this;
	}

	public function attach($paths)
	{
		if (isset($this->attachment)) {
			$extraAttachment = new Attachment($paths);
			$this->attachment->attachment[] = $extraAttachment->attachment[0];
		} else {
			$this->attachment = new Attachment($paths);
		}
		
		return $this;
	}

	public function embed($path)
	{
		$this->attachment->inline[] = $path;
		$pathArray = explode(DIRECTORY_SEPARATOR, $path);
		$file = $pathArray[count($pathArray)-1];
		return 'cid:' . $file;
	}

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

	protected function setReplyTo()
	{
		$replyTo = Config::get('mailgun::reply_to');
		if ($replyTo) {
			$this->replyTo($replyTo);
		}
	}

	protected function setNativeSend()
	{
		if (Config::get('mailgun::force_from_address')) {
			$this->{'o:native-send'} = 'yes';
		}
	}



}