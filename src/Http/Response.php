<?php

namespace Bogardo\Mailgun\Http;

use stdClass;

class Response
{

    /**
     * @var int
     */
    public $status;

    /**
     * @var array|null
     */
    public $data;

    /**
     * @var string
     */
    public $message;

    /**
     * The ID of the sent message, if it exists
     * @var string
     */
    public $id;

    /**
     * @param \stdClass $response
     */
    public function __construct(stdClass $response)
    {
        $this->status = $response->http_response_code;
        $this->message = $response->http_response_body->message;
        $this->id = property_exists($response->http_response_body, 'id') ? $response->http_response_body->id : '';
        $this->data = null;
    }

    /**
     * @return bool
     */
    public function success()
    {
        return $this->status === 200;
    }

    /**
     * @return bool
     */
    public function failed()
    {
        return !$this->success();
    }
}
