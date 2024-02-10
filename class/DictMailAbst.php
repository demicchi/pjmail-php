<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\dto\MailDto;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\TicketLocator;

abstract class DictMailAbst
{
    protected ?\PDO $conn = null;
    
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        try {
            $this->conn = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_CONNECT_FAILURE", $e->getMessage());
        }
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    
    public function addMail(MailDto $mail_dto): void
    {
        try {
            $received_date = new \DateTime($mail_dto->mail_received_date);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_RECEIVED_DATE", $mail_dto->mail_received_date);
        }
        
        try {
            $header_date = new \DateTime($mail_dto->mail_header_date);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_MAIL_INVALID_HEADER_DATE", $mail_dto->mail_header_date);
        }
        
        $sql = 'INSERT INTO mail (id, redmine_project, redmine_ticket, mail_project, mail_ticket, mail_received_date,
                  mail_envelope_from, mail_envelope_to, mail_header_date, mail_header_from, mail_header_to,
                  mail_header_cc, mail_header_subject, mail_body_text, mail_body_html, mail_raw)
                VALUES (:id, :redmine_project, :redmine_ticket, :mail_project, :mail_ticket, :mail_received_date,
                  :mail_envelope_from, :mail_envelope_to, :mail_header_date, :mail_header_from, :mail_header_to,
                  :mail_header_cc, :mail_header_subject, :mail_body_text, :mail_body_html, :mail_raw);';
        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                "id" => $mail_dto->id,
                "redmine_project" => $mail_dto->redmine_project,
                "redmine_ticket" => $mail_dto->redmine_ticket,
                "mail_project" => $mail_dto->mail_project,
                "mail_ticket" => $mail_dto->mail_ticket,
                "mail_received_date" => static::getTimestampText($received_date),
                "mail_envelope_from" => $mail_dto->mail_envelope_from,
                "mail_envelope_to" => $mail_dto->mail_envelope_to,
                "mail_header_date" => static::getTimestampText($header_date),
                "mail_header_from" => $mail_dto->mail_header_from,
                "mail_header_to" => $mail_dto->mail_header_to,
                "mail_header_cc" => $mail_dto->mail_header_cc,
                "mail_header_subject" => $mail_dto->mail_header_subject,
                "mail_body_text" => $mail_dto->mail_body_text,
                "mail_body_html" => $mail_dto->mail_body_html,
                "mail_raw" => $mail_dto->mail_raw,
            ]);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INSERT_MAIL_FAILURE", $e->getMessage());
        }
        if (!$result)
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INSERT_MAIL_FAILURE");
    }
    
    /**
     * @return MailDto[]
     */
    public function getMailByRedmineTicket(TicketLocator $ticket_locator) :array
    {
        return $this->getMailByTicketLocator($ticket_locator, "redmine");
    }
    
    /**
     * @return MailDto[]
     */
    public function getMailByMailTicket(TicketLocator $ticket_locator) :array
    {
        return $this->getMailByTicketLocator($ticket_locator, "mail");
    }
    
    /**
     * @return MailDto[]
     */
    public function getMailByTicketLocator(TicketLocator $ticket_locator, string $origin) :array
    {
        if (is_null($ticket_locator->project))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_INVALID_TICKET_LOCATOR", "project is null");
        if (is_null($ticket_locator->ticket))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_INVALID_TICKET_LOCATOR", "ticket is null");
        
        $sql = 'SELECT id, redmine_project, redmine_ticket, mail_project, mail_ticket, mail_received_date,
                mail_envelope_from, mail_envelope_to, mail_header_date, mail_header_from, mail_header_to,
                mail_header_cc, mail_header_subject, mail_body_text, mail_body_html, mail_raw
                FROM mail ';
        $sql .= match ($origin) {
            "redmine" => ' WHERE redmine_project = :project AND redmine_ticket = :ticket ',
            "mail" => ' WHERE mail_project = :project AND mail_ticket = :ticket ',
            default => throw new PJMailException(ErrorLevel::Error,
                "PJMAIL_E_INVALID_TICKET_LOCATOR_ORIGIN", "origin is " . $origin),
        };
        $sql .= ' ORDER BY mail_received_date DESC;';
        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                "project" => $ticket_locator->project,
                "ticket" => $ticket_locator->ticket,
            ]);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_SELECT_MAIL_FAILURE", $e->getMessage());
        }
        if (!$result)
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_SELECT_MAIL_FAILURE");
        
        $mail_dto_array = [];
        try {
            if (!$stmt->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, "StudioDemmys\\pjmail\\dto\\MailDto"))
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_FETCH_MAIL_FAILURE");
            while (($mail_dto = $stmt->fetch()) !== false) {
                static::formatDateFromTimestampToRFC2822($mail_dto);
                $mail_dto_array[] = $mail_dto;
            }
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_FETCH_MAIL_FAILURE", $e->getMessage());
        }
        
        return $mail_dto_array;
    }
    
    public static function formatDateFromTimestampToRFC2822(MailDto &$mail_dto): void
    {
        try {
            $received_date = static::getDateTimeFromTimestampText($mail_dto->mail_received_date);
            $mail_dto->mail_received_date = $received_date->format(DATE_RFC2822);
        } catch (PJMailException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INVALID_TIMESTAMP",
                "mail_received_date is " . $mail_dto->mail_received_date . ". the original message: " . $e->getMessage());
        }
        try {
            $header_date = static::getDateTimeFromTimestampText($mail_dto->mail_header_date);
            $mail_dto->mail_header_date = $header_date->format(DATE_RFC2822);
        } catch (PJMailException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INVALID_TIMESTAMP",
                "mail_header_date is " . $mail_dto->mail_header_date . ". the original message: " . $e->getMessage());
        }
    }
    
    public static function getTimestampText(\DateTime $datetime): string
    {
        return $datetime->format("Y-m-d H:i:sP");
    }
    
    public static function getDateTimeFromTimestampText(string $timestamp): \DateTime
    {
        try {
            $datetime = new \DateTime($timestamp);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INVALID_TIMESTAMP",
                "timestamp is " . $timestamp);
        }
        return $datetime;
    }
}