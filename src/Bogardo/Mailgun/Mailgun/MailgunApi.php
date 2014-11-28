<?php  namespace Bogardo\Mailgun;

use Config;
use Mailgun\Mailgun as MailgunCore;

abstract class MailgunApi {

    protected $mailgun;

    /**
     * @param bool $init
     *
     * @return MailgunCore
     */
    public function mailgun($init = false)
    {
        if (!$this->mailgun || $init === true) {
            $this->mailgun = new MailgunCore(Config::get('mailgun::api_key'));
        }

        return $this->mailgun;
    }

    protected function _parseParams($params)
    {
        if (empty($params)) {
            return null;
        }

        $array = [];
        foreach ($params as $key => $value) {
            if ($value === true) {
                $value = 'yes';
            }

            if ($value === false) {
                $value = 'no';
            }
            $array[$key] = $value;
        }
        return $array;
    }


} 
