<?php
namespace Module\Base\Mail\Facades;

use Illuminate\Support\Facades\Facade;

class CustomMail extends Facade
{

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'custom-mail';
	}
}