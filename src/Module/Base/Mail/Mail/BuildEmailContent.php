<?php

namespace Module\Base\Mail\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BuildEmailContent extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The body of the message.
     *
     * @var string
     */
    public $content;
    private $strTitle;

    /**
     * Create a new message instance
     *
     * BuildEmailContent constructor.
     * @param $content
     * @param $strTitle
     */
    public function __construct($content, $strTitle)
    {
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.exception')
            ->with('content', $this->content)
            ->subject(env('APP_NAME').'-' . $this->strTitle);
    }
}