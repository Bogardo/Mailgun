<?php namespace Bogardo\Mailgun\Mailgun;

class Message {

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

}