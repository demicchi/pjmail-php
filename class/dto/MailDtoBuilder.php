<?php

namespace StudioDemmys\pjmail\dto;

use StudioDemmys\pjmail\Config;
use StudioDemmys\pjmail\PJMailException;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\Mail;
use StudioDemmys\pjmail\type\TicketLocator;

use Ramsey\Uuid\Uuid;

class MailDtoBuilder
{
    public function __construct(
        public ?string $id = null,
        public ?TicketLocator $redmine_ticket_locator = null,
        public ?TicketLocator $mail_ticket_locator = null,
        public ?string $mail_received_date = null,
        public ?Mail $mail = null,
    ){}
    
    public function __clone()
    {
        if (!is_null($this->mail))
            $this->mail = clone $this->mail;
        if (!is_null($this->redmine_ticket_locator))
            $this->redmine_ticket_locator = clone $this->redmine_ticket_locator;
        if (!is_null($this->mail_ticket_locator))
            $this->mail_ticket_locator = clone $this->mail_ticket_locator;
    }
    
    public function build(\DateTime $default_datetime = null): MailDto
    {
        if (is_null($this->mail_ticket_locator))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_DTO_PARAMETER_MISSING", "mail_ticket_locator");
        if ($this->mail_ticket_locator->project == "")
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_DTO_PARAMETER_MISSING", "mail_project");
        if ($this->mail_ticket_locator->ticket == "")
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_DTO_PARAMETER_MISSING", "mail_ticket");
        if ($this->mail_received_date == "")
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_DTO_PARAMETER_MISSING", "mail_received_date");
        if (is_null($this->mail))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_DTO_PARAMETER_MISSING", "mail");
        
        try {
            $received_date = new \DateTime($this->mail_received_date);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_RECEIVED_DATE", $this->mail_received_date);
        }
        
        if ($this->mail->mail_header_date == "") {
            if (is_null($default_datetime))
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_HEADER_DATE", "No Date header.");
            $header_date = $default_datetime;
        } else {
            try {
                $header_date = new \DateTime($this->mail->mail_header_date);
            } catch (\Exception $e) {
                if (is_null($default_datetime))
                    throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_HEADER_DATE", $this->mail->mail_header_date);
                $header_date = $default_datetime;
            }
        }
        
        if ($this->id == "")
            $this->id = Uuid::uuid7();
        if (is_null($this->redmine_ticket_locator))
            $this->redmine_ticket_locator = new TicketLocator();
        if ($this->redmine_ticket_locator->project == "")
            $this->redmine_ticket_locator->project = $this->mail_ticket_locator->project;
        if ($this->redmine_ticket_locator->ticket == "")
            $this->redmine_ticket_locator->ticket = $this->mail_ticket_locator->ticket;
        
        return new MailDto(
            $this->id,
            $this->redmine_ticket_locator->project,
            $this->redmine_ticket_locator->ticket,
            $this->mail_ticket_locator->project,
            $this->mail_ticket_locator->ticket,
            $received_date->format(DATE_RFC2822),
            $this->mail->mail_envelope_from,
            $this->mail->mail_envelope_to,
            $header_date->format(DATE_RFC2822),
            $this->mail->mail_header_from,
            $this->mail->mail_header_to,
            $this->mail->mail_header_cc,
            $this->mail->mail_header_subject,
            $this->mail->mail_body_text,
            $this->mail->mail_body_html,
            $this->mail->mail_raw,
        );
    }
    
    /**
     * @return TicketLocator[]
     */
    public static function getTicketLocatorFromAddress(string $address, string $target_domain): array
    {
        $result = [];
        $separator = preg_quote(Config::getConfig("project_ticket_separator"), "/");
        $address_array = mailparse_rfc822_parse_addresses($address);
        foreach ($address_array as $address_part) {
            if (!preg_match('/^(.+)'. $separator . '(\d+)@(.+?)$/', $address_part["address"], $match))
                continue;
            if (strtolower($match[3]) != strtolower($target_domain))
                continue;
            $result[] = new TicketLocator(strtolower($match[1]), strtolower($match[2]));
        }
        return $result;
    }
}