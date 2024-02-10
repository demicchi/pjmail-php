#!/usr/bin/php
<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\dto\MailDtoBuilder;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\Mail;

try {
    define("PJMAIL_RUNNING_MODE", "mail");
    require_once "initialize.php";
    
    Logging::info("adding a mail into the db.");
    Logging::debug("running in " . PJMAIL_RUNNING_MODE . " mode.");
    
    $env = getenv();
    Logging::debug("ENV: " . print_r($env, true));
    Logging::debug("argv: " . print_r($argv, true));
    
    $mail_stdin = new ReadMailFromStdin();
    $mail_stdin->setTimeout(Config::getConfig("mail_stdin/timeout"));
    $mail_raw = $mail_stdin->read(Config::getConfig("mail_stdin/max_length"));
    
    $default_datetime = new \DateTime();
    $mail = new Mail();
    try {
        $add_mail_method = Config::getConfig("add_mail_method");
        switch ($add_mail_method) {
            case "content_filter":
                $mail_envelope_from = $argv[1] ?? "";
                $mail_envelope_to = "";
                if (isset($argv[2]))
                    $mail_envelope_to = implode(",", array_slice($argv, 2));
                break;
            case "alias":
                $mail_envelope_from = $env["SENDER"] ?? "";
                $mail_envelope_to = $env["ORIGINAL_RECIPIENT"] ?? $env["RECIPIENT"] ?? "";
                break;
            default:
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_INVALID_ADD_MAIL_METHOD", $add_mail_method);
        }
        Logging::debug("add_mail_method: " . $add_mail_method);
        Logging::debug("envelope-from: " . $mail_envelope_from);
        Logging::debug("envelope-to: " . $mail_envelope_to);
        $mail->setParameters($mail_raw, $mail_envelope_from, $mail_envelope_to, $default_datetime);
    } catch (PJMailException $e) {
        exit(0);
    }
    
    try {
        if ($mail->mail_envelope_to == "")
            throw new PJMailException(ErrorLevel::Warn, "PJMAIL_W_NO_TICKET_LOCATOR", $mail->mail_envelope_to);
        
        $ticket_locator_array = MailDtoBuilder::getTicketLocatorFromAddress($mail->mail_envelope_to, Config::getConfig("mail_domain"));
        $ticket_amount = count($ticket_locator_array);
        if ($ticket_amount < 1)
            throw new PJMailException(ErrorLevel::Warn, "PJMAIL_W_NO_TICKET_LOCATOR", $mail->mail_envelope_to);
        
        Logging::info("number of assignments: " . $ticket_amount);
        
        $dict_mail = new DictMail();
        
        $i = 0;
        foreach ($ticket_locator_array as $ticket_locator) {
            Logging::info("ticket assignment " . ++$i . "/" . $ticket_amount);
            Logging::info("  assigned project: " . $ticket_locator->project);
            Logging::info("  assigned ticket: " . $ticket_locator->ticket);
            $mail_dto_builder = new MailDtoBuilder(
                null,
                $ticket_locator,
                $ticket_locator,
                date(DATE_RFC2822),
                $mail
            );
            $dict_mail->addMail($mail_dto_builder->build());
        }
    } catch (PJMailException $e) {
        exit(0);
    }
    
    Logging::info("the mail has been added.");
    
} finally {
    // Always exit with 0
    exit(0);
}
