<?php

use Mailgun\Messages\MessageBuilder;
use Orchestra\Testbench\TestCase;

class MailgunTestCase extends TestCase
{

    /**
     * @var Mockery\MockInterface
     */
    protected $mailgunApi;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->app->bind('mailgun.client', function(){
            return new Http\Mock\Client();
        });

        $this->mailgunApi = Mockery::mock('Mailgun\Mailgun');
        $this->mailgunApi->shouldReceive('setApiVersion')->andReturnSelf();
        $this->mailgunApi->shouldReceive('setSslEnabled')->andReturnSelf();

        $this->mailgunApi->shouldReceive('MessageBuilder')->andReturn(new MessageBuilder());

        $this->app->instance('Mailgun\Mailgun', $this->mailgunApi);
    }

    public function tearDown()
    {
        parent::tearDown();
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
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
        // reset base path to point to our package's src directory
        $app['path.base'] = realpath(dirname(__DIR__) . '/src');

        $app['config']->set('mailgun.domain', 'test.domain.com');
        $app['config']->set('mailgun.from.address', 'from@example.com');
        //$app['config']->set('mailgun.from.name', 'From User');

    }

    /**
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['Bogardo\Mailgun\MailgunServiceProvider'];
    }

    /**
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return ['Mailgun' => 'Bogardo\Mailgun\Facades\Mailgun'];
    }

    protected function getSuccessResponse()
    {
        return json_decode(json_encode([
            'http_response_body' => [
                'message' => 'success',
                'id' => '123'
            ],
            'http_response_code' => 200
        ]));
    }

    protected function getErrorResponse()
    {
        return json_decode(json_encode([
            'http_response_body' => [
                'message' => 'error',
                'id' => '234'
            ],
            'http_response_code' => 400
        ]));
    }

}
