<?php
namespace Module\Base\Mail;

use Illuminate\Support\ServiceProvider;

class CustomMailServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/config.php' => config_path('module-mail.php')
		], 'config');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/config/config.php', 'module-mail');

		$this->app->singleton('custom-mail', function ($app) {
			$config = $app->config->get('module-mail');
			return new CustomMail($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'custom-mail'
		];
	}
}
