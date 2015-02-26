<?php  namespace Bogardo\Mailgun\Mailgun;

use Config;
use Mailgun\Mailgun as MailgunCore;

abstract class MailgunApi {

    protected $mailgun;

    /**
     * @param bool $init
     * @param string $apiKey
     *
     * @return MailgunCore
     */
    public function mailgun($init = false, $apiKey = '')
    {
        if (!$this->mailgun || $init === true) {

            if (!$apiKey) {
                $apiKey = Config::get('mailgun::api_key');
            }

            $this->mailgun = new MailgunCore($apiKey);
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
