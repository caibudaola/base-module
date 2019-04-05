<?php
namespace Module\Base\Mail;

use Exception;
use Symfony\Component\Debug\Exception\FlattenException;
use Mail;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Module\Base\Mail\Mail\ExceptionOccured;

class CustomMail {

	public function __construct($config)
	{
        $this->debug = isset($config['debug']) ? $config['debug'] : 'false';
        $this->receiver = isset($config['receiver']) ? $config['receiver'] : 'lzw122333@gmail.com';
	}

    public function sendEmail(Exception $exception)
    {
        try {
            $e = FlattenException::create($exception);

            $handler = new SymfonyExceptionHandler();

            $html = $handler->getHtml($e);

            Mail::to($this->receiver)->send(new ExceptionOccured($html));
        } catch (Exception $ex) {
            dd($ex);
        }
    }
}
