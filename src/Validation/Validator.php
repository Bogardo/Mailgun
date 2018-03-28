<?php

namespace Bogardo\Mailgun\Validation;

use Mailgun\Mailgun;

class Validator
{

    /**
     * @var \Mailgun\Mailgun
     */
    protected $mailgun;

    /**
     * @param \Mailgun\Mailgun $mailgun
     */
    public function __construct(Mailgun $mailgun)
    {
        $this->mailgun = $mailgun;
    }

    /**
     * Validate an address based on:
     * - Syntax checks (RFC defined grammar)
     * - DNS validation
     * - Spell checks
     * - Email Service Provider (ESP) specific local-part grammar (if available).
     *
     * The validation service is intended to validate email addresses submitted through
     * forms like newsletters, online registrations and shopping carts.
     *
     * It is not intended to be used for bulk email list scrubbing and Mailgun reserves
     * the right to disable your account if Mailgun sees it being used as such.
     *
     * @see https://documentation.mailgun.com/en/latest/api-email-validation.html
     *
     * @param string $address
     * @param bool   $mailboxVerification
     *
     * @return \stdClass
     */
    public function validate($address, $mailboxVerification = false)
    {
        return $this->mailgun->get('address/validate', [
            'address' => $address,
            'mailbox_verification' => $mailboxVerification
        ])->http_response_body;
    }

    /**
     * Parses an array of email addresses into two lists: parsed addresses and unparsable portions.
     * The parsed addresses are a list of addresses that are syntactically valid (and optionally
     * have DNS and ESP specific grammar checks).
     * The unparsable list is a list of characters sequences that the parser was not able to
     * understand. These often align with invalid email addresses, but not always.
     *
     * @param array|string $addresses
     * @param bool         $syntaxOnly
     *
     * @return mixed
     */
    public function parse($addresses, $syntaxOnly = false)
    {
        if (is_array($addresses)) {
            $addresses = implode(',', $addresses);
        }

        $syntaxOnly = $syntaxOnly ? 'true' : 'false';

        return $this->mailgun->get('address/parse', [
            'addresses' => $addresses,
            'syntax_only' => $syntaxOnly,
        ])->http_response_body;
    }
}
