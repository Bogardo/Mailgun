<?php

class MailgunTest extends MailgunTestCase
{

    /** @test */
    public function it_provides_the_mailgun_service()
    {
        $provides = (new Bogardo\Mailgun\MailgunServiceProvider($this->app))->provides();
        $this->assertEquals([
            'mailgun',
            'mailgun.public',
            Bogardo\Mailgun\Contracts\Mailgun::class
        ], $provides);
    }

    /** @test */
    public function it_registers_the_mailgun_service()
    {
        $service = $this->app->make('mailgun');
        $this->assertInstanceOf(Bogardo\Mailgun\Service::class, $service);
    }

    /** @test */
    public function it_registers_the_public_mailgun_service()
    {
        $service = $this->app->make('mailgun.public');
        $this->assertInstanceOf(Mailgun\Mailgun::class, $service);
    }

    /** @test */
    public function it_registers_the_mailgun_contract()
    {
        $service = $this->app->make(Bogardo\Mailgun\Contracts\Mailgun::class);
        $this->assertInstanceOf(Bogardo\Mailgun\Service::class, $service);
    }

    /** @test */
    public function it_registers_the_facade()
    {
        $this->assertInstanceOf(Bogardo\Mailgun\Service::class, Mailgun::getFacadeRoot());
    }

    /** @test */
    public function it_raises_an_exception_when_a_custom_client_is_not_registered_in_the_container()
    {
        $this->expectException('ReflectionException');
        $this->expectExceptionMessage('Class mailgun.client does not exist');

        unset($this->app['mailgun.client']);

        Mailgun::send(null, null, null);
    }
}
