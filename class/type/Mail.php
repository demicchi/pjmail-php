<?php

namespace StudioDemmys\pjmail\type;

use StudioDemmys\pjmail\PJMailException;

class Mail
{
    public function __construct(
        public string $mail_envelope_from = "",
        public string $mail_envelope_to = "",
        public string $mail_header_date = "",
        public string $mail_header_from = "",
        public string $mail_header_to = "",
        public string $mail_header_cc = "",
        public string $mail_header_subject = "",
        public string $mail_body_text = "",
        public string $mail_body_html = "",
        public string $mail_raw = "",
    ){}
    
    public function setParameters(?string $mail_raw = null, ?string $mail_envelope_from = null,
                                  ?string $mail_envelope_to = null, ?\DateTime $default_datetime = null): void
    {
        if ($mail_raw != "") {
            $this->mail_raw = $mail_raw;
            $parser = new \PhpMimeMailParser\Parser();
            $parser->setText($mail_raw);
            
            $mail_header_date = ($parser->getHeader("date") === false) ? "" : $parser->getHeader("date");
            if ($mail_header_date == "") {
                if (is_null($default_datetime))
                    throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_HEADER_DATE", "No Date header.");
                $header_datetime = $default_datetime;
            } else {
                try {
                    $header_datetime = new \DateTime($mail_header_date);
                } catch (\Exception $e) {
                    if (is_null($default_datetime))
                        throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_HEADER_DATE", $mail_header_date);
                    $header_datetime = $default_datetime;
                }
            }
            
            $this->mail_header_date = $header_datetime->format(DATE_RFC2822);
            $this->mail_header_from = ($parser->getHeader("from") === false) ? "" : $parser->getHeader("from");
            $this->mail_header_to = ($parser->getHeader("to") === false) ? "" : $parser->getHeader("to");
            $this->mail_header_cc = ($parser->getHeader("cc") === false) ? "" : $parser->getHeader("cc");
            $this->mail_header_subject = ($parser->getHeader("subject") === false) ? "" : $parser->getHeader("subject");
            $this->mail_body_text = ($parser->getMessageBody("text") === false) ? "" : $parser->getMessageBody("text");
            $this->mail_body_html = ($parser->getMessageBody("html") === false) ? "" : $parser->getMessageBody("html");
        }
        if (!is_null($mail_envelope_from))
            $this->mail_envelope_from = $mail_envelope_from;
        if (!is_null($mail_envelope_to))
            $this->mail_envelope_to = $mail_envelope_to;
    }
}