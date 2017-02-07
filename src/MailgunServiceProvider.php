<?php

namespace Bogardo\Mailgun;

use Bogardo\Mailgun\Contracts\Mailgun as MailgunContract;
use Illuminate\Support\ServiceProvider;
use Mailgun\Mailgun as MailgunApi;

class MailgunServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath('mailgun.php') => config_path('mailgun.php'),
        ], 'config');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');

        /**
         * Register main Mailgun service
         */
        $this->app->bind('mailgun', function () use ($config) {
            $clientAdapter = $this->app->make('mailgun.client');

            $mg = new MailgunApi(
                $config->get('mailgun.api_key'),
                $clientAdapter,
                $config->get('mailgun.api.endpoint')
            );
            $mg->setApiVersion($config->get('mailgun.api.version'));
            $mg->setSslEnabled($config->get('mailgun.api.ssl', true));

            return new Service($mg, $this->app->make('view'), $config);
        });

        /**
         * Register the public Mailgun service
         */
        $this->app->bind('mailgun.public', function () use ($config) {
            $clientAdapter = $this->app->make('mailgun.client');

            $mg = new MailgunApi(
                $config->get('mailgun.public_api_key'),
                $clientAdapter,
                $config->get('mailgun.api.endpoint')
            );
            $mg->setApiVersion($config->get('mailgun.api.version'));
            $mg->setSslEnabled($config->get('mailgun.api.ssl', true));

            return $mg;
        });

        $this->app->bind(MailgunContract::class, 'mailgun');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mailgun', 'mailgun.public', MailgunContract::class];
    }

    /**
     * Get the path to the config directory.
     *
     * @param string $file
     *
     * @return string
     */
    protected function configPath($file = '')
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $file;
    }
}
