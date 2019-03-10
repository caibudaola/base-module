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
	}

    public function sendEmail(Exception $exception)
    {
        try {
            $e = FlattenException::create($exception);

            $handler = new SymfonyExceptionHandler();

            $html = $handler->getHtml($e);

            Mail::to(env('MAIL_EXCEPTION_RECEIVER', 'lzw122333@gmail.com'))->send(new ExceptionOccured($html));
        } catch (Exception $ex) {
            dd($ex);
        }
    }
}
