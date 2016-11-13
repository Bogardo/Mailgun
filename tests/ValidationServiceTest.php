<?php

use Carbon\Carbon;

class ValidationServiceTest extends MailgunTestCase
{

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
    }


    /** @test */
    public function it_validates_an_email_address_and_returns_a_positive_response_for_a_valid_address()
    {

        $args = [
            'address/validate',
            [
                'address' => 'email@example.com'
            ]
        ];

        $this->mailgunApi->shouldReceive('get')->once()->withArgs($args)->andReturn(
            json_decode(json_encode([
                'http_response_body' => [
                    'address' => 'email@example.com',
                    'did_you_mean' => null,
                    'is_valid' => true,
                    'parts' => [
                        'display_name' => null,
                        'domain' => 'example.com',
                        'local_part' => 'email'
                    ]
                ],
                'http_response_code' => 200
            ]))
        );

        $result = Mailgun::validator()->validate('email@example.com');

        $this->assertTrue($result->is_valid);
    }

    /** @test */
    public function it_validates_an_email_address_and_returns_a_negative_response_for_an_invalid_address()
    {

        $args = [
            'address/validate',
            [
                'address' => 'emailexample.com'
            ]
        ];

        $this->mailgunApi->shouldReceive('get')->once()->withArgs($args)->andReturn(
            json_decode(json_encode([
                'http_response_body' => [
                    'address' => 'emailexample.com',
                    'did_you_mean' => null,
                    'is_valid' => false,
                    'parts' => [
                        'display_name' => null,
                        'domain' => null,
                        'local_part' => null
                    ]
                ],
                'http_response_code' => 200
            ]))
        );

        $result = Mailgun::validator()->validate('emailexample.com');

        $this->assertFalse($result->is_valid);
    }

    /** @test */
    public function is_parses_email_addresses()
    {
        $args = [
            "address/parse",
            [
                'addresses' => 'email@exmple.com,invalid_email.com,weird@extension.beepbep,no@tld,new@tlds.shop',
                'syntax_only' => 'false'
            ]
        ];

        $response = [
            'parsed' => ['email@exmple.com'],
            'unparseable' => [
                'weird@extension.beepbep',
                'no@tld',
                'new@tlds.shop',
                'invalid_email.com'
            ]
        ];

        $this->mailgunApi->shouldReceive('get')->once()->withArgs($args)->andReturn(
            json_decode(json_encode([
                'http_response_body' => $response
            ])));

        $result = Mailgun::validator()->parse([
            'email@exmple.com',
            'invalid_email.com',
            'weird@extension.beepbep',
            'no@tld',
            'new@tlds.shop'
        ], false);

        $this->assertCount(1, $result->parsed);
    }

    /** @test */
    public function is_provides_the_mailgun_opt_in_handler()
    {
        $this->mailgunApi->shouldReceive('OptInHandler')->once();

        $handler = Mailgun::optInHandler();

        $this->assertTrue(true);
    }

}
