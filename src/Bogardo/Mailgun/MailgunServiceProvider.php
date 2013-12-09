<?php namespace Bogardo\Mailgun;

use Illuminate\Support\ServiceProvider;

class MailgunServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('bogardo/mailgun');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$view = $this->app['view'];

		$this->app['mailgun'] = $this->app->share(function($app) use($view){
			return new Mailgun($view);
		});

		$this->app->booting(function(){
			$loader = \Illuminate\Foundation\AliasLoader::getInstance();
			$loader->alias('Mailgun', 'Bogardo\Mailgun\Facades\Mailgun');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('mailgun');
	}

}