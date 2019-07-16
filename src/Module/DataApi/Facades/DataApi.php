<?php
namespace Module\DataApi\Facades;

use Illuminate\Support\Facades\Facade;

class DataApi extends Facade
{

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'data-api';
	}
}