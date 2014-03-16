<?php namespace Bogardo\Mailgun\Mailgun;

Class Inline
{
	
	public $cid;

	public $attachment;

	public function __construct($path, $name)
	{
		if ($name) {
			$this->attachment = array(
				'filePath' => "@{$path}",
				'remoteName' => $name
			);
			$this->cid = $name;
		} else {
			$this->attachment = $path;

			$pathArray = explode(DIRECTORY_SEPARATOR, $path);
			$this->cid = $pathArray[count($pathArray)-1];
		}
	
		return $this;
	}

	public function getAttachment()
	{
		return $this->attachment;
	}

	public function getCid()
	{
		return $this->cid;
	}

}
