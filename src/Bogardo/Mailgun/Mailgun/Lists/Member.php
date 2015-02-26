<?php  namespace Bogardo\Mailgun\Mailgun\Lists;


use Bogardo\Mailgun\Mailgun\MailgunApi;

class Member extends MailgunApi {

    public $address;

    public $name;

    public $subscribed;

    public $vars;


    public function __construct($address = "")
    {
        if ($address) {
            $this->address;
        }
    }

    public function setMember($member)
    {
        $this->address    = $member->address;
        $this->name       = $member->name;
        $this->subscribed = $member->subscribed;
        $this->vars       = $member->vars;
        unset($this->mailgun);

        return $this;
    }

}
