<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $encryptedData;
    public $dataArray;

    /**
     * Create a new message instance.
     *
     * @param array $encryptedData
     * @param array $dataArray
     * @return void
     */
    public function __construct($encryptedData, $dataArray)
    {
        $this->encryptedData = $encryptedData;
        $this->dataArray = $dataArray;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject($this->dataArray['subject'])
                    ->view('email.send')
                    ->with([
                        'encryptedData' => $this->encryptedData,
                        'dataArray' => $this->dataArray,
                    ]);
    }
}