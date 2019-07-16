<?php
namespace Module\DataApi;

use Illuminate\Support\ServiceProvider;

class DataApiProvider extends ServiceProvider
{

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/config.php' => config_path('module-data-api.php')
		], 'config');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/config/config.php', 'module-data-api');

		$this->app->singleton('data-api', function ($app) {
			$config = $app->config->get('module-data-api');
			return new DataApi($config);
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
			'data-api'
		];
	}
}
