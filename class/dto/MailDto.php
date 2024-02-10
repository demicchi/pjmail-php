<?php

namespace StudioDemmys\pjmail\dto;

class MailDto
{
    /**
     * @param string $id UUID
     * @param string $redmine_project Redmine project name that this mail is assigned to
     * @param string $redmine_ticket Redmine ticket number of the project that this mail is assigned to
     * @param string $mail_project Project name parsed from the envelope-to address of this mail
     * @param string $mail_ticket Ticket number parsed from the envelope-to address of this mail
     * @param string $mail_received_date DATE_RFC2822
     * @param string $mail_envelope_from
     * @param string $mail_envelope_to
     * @param string $mail_header_date DATE_RFC2822
     * @param string $mail_header_from
     * @param string $mail_header_to
     * @param string $mail_header_cc
     * @param string $mail_header_subject
     * @param string $mail_body_text
     * @param string $mail_body_html
     * @param string $mail_raw
     */
    public function __construct(
        public string $id = "",
        public string $redmine_project = "",
        public string $redmine_ticket = "",
        public string $mail_project = "",
        public string $mail_ticket = "",
        public string $mail_received_date = "",
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
}