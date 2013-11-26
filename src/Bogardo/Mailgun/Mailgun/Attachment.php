<?php namespace Bogardo\Mailgun\Mailgun;

Class Attachment {
	
	public function __construct($path)
	{
		$this->attachment = array($path);
		return $this;
	}

}