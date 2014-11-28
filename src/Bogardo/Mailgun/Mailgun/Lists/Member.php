<?php  namespace Bogardo\Mailgun\Lists;


use Bogardo\Mailgun\MailgunApi;

class Member extends MailgunApi {

    public $address;

    public $name;

    public $subscribed;

    public $vars;


    public function __construct($item)
    {
        $this->address    = $item->address;
        $this->name       = $item->name;
        $this->subscribed = $item->subscribed;
        $this->vars       = $item->vars;
    }

}
