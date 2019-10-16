<?php

namespace Bogardo\Mailgun\Mail;

use Bogardo\Mailgun\Http\Response;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as View;
use Mailgun\Mailgun;

class Mailer
{

    /**
     * @var \Mailgun\Mailgun
     */
    protected $mailgun;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var Message
     */
    protected $message;


    /**
     * @param \Mailgun\Mailgun                        $mailgun
     * @param \Illuminate\Contracts\View\Factory      $view
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Mailgun $mailgun, View $view, Config $config)
    {
        $this->mailgun = $mailgun;
        $this->view = $view;
        $this->config = $config;
    }


    /**
     * @param string|array $view
     * @param array        $data
     * @param \Closure     $callback
     * @param null         $message
     *
     * @return \Bogardo\Mailgun\Http\Response
     */
    public function send($view, array $data, Closure $callback, $message = null)
    {
        $this->message = $message ?: new Message($this->mailgun->MessageBuilder(), $this->config);

        $this->callMessageBuilder($callback, $this->message);
        $this->renderBody($view, $data);

        $message = $this->message->getMessage();
        $files = $this->message->getFiles();

        $domain = $this->config->get('mailgun.domain');
        $response = new Response($this->mailgun->post("{$domain}/messages", $message, $files));

        return $response;
    }

    /**
     * @param int|array|\DateTime|Carbon $time
     * @param string|array               $view
     * @param array                      $data
     * @param \Closure                   $callback
     *
     * @return \Bogardo\Mailgun\Http\Response
     */
    public function later($time, $view, array $data, Closure $callback)
    {
        $message = new Message($this->mailgun->MessageBuilder(), $this->config);
        $message->builder()
                ->setDeliveryTime($this->parseTime($time), $this->config->get('app.timezone', 'UTC'));

        return $this->send($view, $data, $callback, $message);
    }

    /**
     * Call the provided message builder.
     *
     * @param \Closure                      $callback
     * @param \Bogardo\Mailgun\Mail\Message $message
     *
     * @return mixed
     */
    protected function callMessageBuilder(Closure $callback, Message $message)
    {
        return call_user_func($callback, $message);
    }

    /**
     * Render HTML and/or text body.
     *
     * When only a string is passed as the view we default to an HTML body.
     *
     * When an array without keys is passed as the view; the first value
     * will be handled as the HTML body. An optional second value will
     * be handled as the text body.
     *
     * When an array with keys (html, text or raw) is passed as the view; the
     * values for those keys will be handled according to the key.
     *
     * @param string|array $view
     * @param array        $data
     */
    protected function renderBody($view, array $data)
    {
        $data['message'] = $this->message;

        if (is_string($view)) {
            $this->setHtmlBody($view, $data);
        }

        if (is_array($view) && isset($view[0])) {
            $this->setHtmlBody($view[0], $data);

            if (isset($view[1])) {
                $this->setTextBody($view[1], $data);
            }
        } elseif (is_array($view)) {
            if (isset($view['html'])) {
                $this->setHtmlBody($view['html'], $data);
            }

            if (isset($view['text'])) {
                $this->setTextBody($view['text'], $data);
            }

            if (isset($view['raw'])) {
                $this->setRawBody($view['raw']);
            }
        }
    }

    /**
     * Set rendered HTML body.
     *
     * @param string $view
     * @param array  $data
     */
    protected function setHtmlBody($view, array $data)
    {
        $this->message->builder()->setHtmlBody($this->renderView($view, $data));
    }

    /**
     * Set rendered text body.
     *
     * @param string $view
     * @param array  $data
     */
    protected function setTextBody($view, array $data)
    {
        $this->message->builder()->setTextBody($this->renderView($view, $data));
    }

    /**
     * Set the raw body
     *
     * @param string $view
     */
    protected function setRawBody($view)
    {
        // If raw body is a string, set HTML and use strip_tags to generate text content
        if (is_string($view)) {
            $this->message->builder()->setHtmlBody($view);
            $this->message->builder()->setTextBody(strip_tags($view, '<a>'));
        } elseif (is_array($view) && isset($view[0])) {
            // Get HTML from first element of view array
            $this->message->builder()->setHtmlBody($view[0]);
            if (isset($view[1])) {
                // Get text content if present in second element
                $this->message->builder()->setTextBody($view[1]);
            }
        } elseif (is_array($view)) {
            // Set HTML content from view array
            if (isset($view['html'])) {
                $this->message->builder()->setHtmlBody($view['html']);
            }
            // Set text content from view array
            if (isset($view['text'])) {
                $this->message->builder()->setTextBody($view['text']);
            }
        }
    }

    /**
     * Render the view.
     *
     * @param string $view
     * @param array  $data
     *
     * @return string
     */
    protected function renderView($view, array $data)
    {
        return $this->view->make($view, $data)->render();
    }

    /**
     * Parse given time and convert it to the required format
     *
     * @param int|array|Carbon|\DateTime $time
     *
     * @return string
     */
    private function parseTime($time)
    {
        $timezone = $this->config->get('app.timezone', 'UTC');

        /** @var Carbon $now */
        $now = Carbon::now($timezone);

        if (is_object($time)) {
            $deliveryTime = Carbon::instance($time);
        } else {
            if (is_array($time)) {
                reset($time);
                $type = key($time);
                $amount = $time[$type];
            } else {
                $type = 'seconds';
                $amount = $time;
            }

            /** @var Carbon $deliveryTime */
            $deliveryTime = Carbon::now($timezone);

            switch ($type) {
                case 'seconds':
                    $deliveryTime->addSeconds($amount);
                    break;
                case 'minutes':
                    $deliveryTime->addMinutes($amount);
                    break;
                case 'hours':
                    $deliveryTime->addHours($amount);
                    break;
                case 'days':
                    $deliveryTime->addHours($amount * 24);
                    break;
                default:
                    $deliveryTime->addSeconds($amount);
                    break;
            }
        }

        //Calculate boundaries
        $max = Carbon::now()->addHours(3 * 24);
        if ($deliveryTime->gt($max)) {
            $deliveryTime = $max;
        } elseif ($deliveryTime->lt($now)) {
            $deliveryTime = $now;
        }

        return $deliveryTime->format(\DateTime::RFC2822);
    }
}
