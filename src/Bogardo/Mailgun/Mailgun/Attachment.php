<?php namespace Bogardo\Mailgun\Mailgun;

Class Attachment
{
	
	public $attachment;

	public function __construct($path, $name)
	{
		if ($name) {
			$this->attachment =	array(
				'filePath' => "@{$path}",
				'remoteName' => $name
			);
		} else {
			$this->attachment = $path;
		}
	
		return $this;
	}

	public function getAttachment()
	{
		return $this->attachment;
	}

}
