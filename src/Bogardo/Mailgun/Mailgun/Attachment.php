<?php

namespace Bogardo\Mailgun\Mailgun;

class Attachment
{
    public $attachment;

    public function __construct($path, $name)
    {
        if ($name) {
            $this->attachment = [
                'filePath'   => "@{$path}",
                'remoteName' => $name,
            ];
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
