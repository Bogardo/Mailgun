<?php

namespace Bogardo\Mailgun\Mail;

use Illuminate\Contracts\Config\Repository as Config;
use Mailgun\Messages\MessageBuilder;

class Message
{

    /**
     * @var \Mailgun\Messages\MessageBuilder
     */
    protected $messageBuilder;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * Message constructor.
     *
     * @param \Mailgun\Messages\MessageBuilder        $messageBuilder
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(MessageBuilder $messageBuilder, Config $config)
    {
        $this->messageBuilder = $messageBuilder;
        $this->config = $config;

        $this->setConfigReplyTo();
        $this->setConfigNativeSend();
        $this->setConfigTestMode();
    }

    /**
     * Set from address
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function from($address, $name = "")
    {
        $this->messageBuilder->setFromAddress($address, ['full_name' => $name]);

        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param string|array $address
     * @param string       $name
     * @param array        $variables
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function to($address, $name = "", array $variables = [])
    {
        if (is_array($address)) {
            foreach ($address as $email => $variables) {
                $this->variables[$email] = $variables;

                $name = isset($variables['name']) ? $variables['name'] : null;
                $this->messageBuilder->addToRecipient($email, ['full_name' => $name]);
            }
        } else {
            if (!empty($variables)) {
                $this->variables[$address] = $variables;
            }
            $this->messageBuilder->addToRecipient($address, ['full_name' => $name]);
        }

        return $this;
    }

    /**
     * Add a carbon copy to the message.
     *
     * @param string|array $address
     * @param string       $name
     * @param array        $variables
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function cc($address, $name = "", array $variables = [])
    {
        if (!empty($variables)) {
            $this->variables[$address] = $variables;
        }
        $this->messageBuilder->addCcRecipient($address, ['full_name' => $name]);

        return $this;
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * @param string|array $address
     * @param string       $name
     * @param array        $variables
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function bcc($address, $name = "", array $variables = [])
    {
        if (!empty($variables)) {
            $this->variables[$address] = $variables;
        }
        $this->messageBuilder->addBccRecipient($address, ['full_name' => $name]);

        return $this;
    }

    /**
     * Set/Overwrite recipientVariables
     *
     * @param array $variables
     */
    public function recipientVariables(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * Add a reply-to address to the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function replyTo($address, $name = "")
    {
        $this->messageBuilder->setReplyToAddress($address, ['full_name' => $name]);

        return $this;
    }

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function subject($subject)
    {
        $this->messageBuilder->setSubject($subject);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param string $path
     * @param string $name
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function attach($path, $name = '')
    {
        $this->messageBuilder->addAttachment($path, $name);

        return $this;
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param string $path
     * @param string $name
     *
     * @return string
     */
    public function embed($path, $name = null)
    {
        $name = $name ?: basename($path);
        $this->messageBuilder->addInlineImage("@{$path}", $name);

        return "cid:{$name}";
    }

    /**
     * Add Mailgun tags to the message.
     * Tag limit is 3.
     *
     * @param string|array $tags
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function tag($tags)
    {
        $tags = array_slice((array)$tags, 0, 3);
        foreach ($tags as $tag) {
            $this->messageBuilder->addTag($tag);
        }

        return $this;
    }

    /**
     * Add Mailgun campaign ID(s) to the message
     * Campaign ID limit is 3.
     *
     * @param int|string|array $campaigns
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function campaign($campaigns)
    {
        $campaigns = array_slice((array)$campaigns, 0, 3);
        foreach ($campaigns as $campaign) {
            $this->messageBuilder->addCampaignId($campaign);
        }

        return $this;
    }

    /**
     * Enable/disable DKIM signature on per-message basis.
     *
     * @param bool $enabled
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function dkim($enabled)
    {
        $this->messageBuilder->setDkim($enabled);

        return $this;
    }

    /**
     * Toggles clicks tracking on a per-message basis.
     * This setting has a higher priority than the domain-level setting.
     * Pass `true`, `false` or 'html' or 'htmlonly'.
     *
     * @param bool|string $value
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function trackClicks($value)
    {
        $value = $value === 'htmlonly' ? 'html' : $value;
        $this->messageBuilder->setClickTracking($value);

        return $this;
    }

    /**
     * Toggles opens-tracking on a per-message basis.
     * This setting has a higher priority than the domain-level setting.
     *
     * @param bool $enabled
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function trackOpens($enabled)
    {
        $this->messageBuilder->setOpenTracking($enabled);

        return $this;
    }

    /**
     * Enable or disable test-mode on a per-message basis.
     *
     * @param bool|string $enabled
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function testmode($enabled = true)
    {
        $this->messageBuilder->setTestMode($enabled);

        return $this;
    }

    /**
     * Append a custom MIME header to a message.
     *
     * @param string $key
     * @param string $value
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function header($key, $value)
    {
        $this->messageBuilder->addCustomHeader($key, $value);

        return $this;
    }

    /**
     * Attach custom data to a message.
     *
     * @param string $key
     * @param mixed  $data
     *
     * @return \Bogardo\Mailgun\Mail\Message
     */
    public function data($key, $data)
    {
        $this->messageBuilder->addCustomData($key, $data);

        return $this;
    }

    /**
     * Force the from address (see description in config).
     */
    protected function setConfigNativeSend()
    {
        if ($this->config->get('mailgun.force_from_address')) {
            $this->{'o:native-send'} = 'yes';
        }
    }

    /**
     * Apply reply-to address from config.
     */
    protected function setConfigReplyTo()
    {
        $address = $this->config->get('mailgun.reply_to.address');
        $name = $this->config->get('mailgun.reply_to.name');
        if ($address) {
            $name = $name ? ['full_name' => $name] : null;
            $this->messageBuilder->setReplyToAddress($address, $name);
        }
    }

    /**
     * Enable/Disable test-mode depending on config setting.
     */
    protected function setConfigTestMode()
    {
        $this->messageBuilder->setTestMode($this->config->get('mailgun.testmode'));
    }

    /**
     * Set from address from config
     */
    protected function setConfigFrom()
    {
        $this->from($this->config->get('mailgun.from.address'), $this->config->get('mailgun.from.name'));
    }

    /**
     * @return \Mailgun\Messages\MessageBuilder
     */
    public function builder()
    {
        return $this->messageBuilder;
    }

    /**
     * Get the message from MessageBuilder and apply custom/extra data
     *
     * @return array
     */
    public function getMessage()
    {
        $message = $this->messageBuilder->getMessage();

        if (!isset($message['from'])) {
            $this->setConfigFrom();
            $message = $this->messageBuilder->getMessage();
        }
        if ($this->variables) {
            $message['recipient-variables'] = json_encode($this->variables);
        }
        if (isset($this->{'o:native-send'})) {
            $message['o:native-send'] = $this->{'o:native-send'};
        }

        return $message;
    }

    /**
     * Get the files from MessageBuilder
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->messageBuilder->getFiles();
    }
}
