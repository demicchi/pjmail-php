create table mail
(
    id                  uuid                     not null
        constraint mail_pk
            primary key,
    redmine_project     text                     not null,
    redmine_ticket      text                     not null,
    mail_project        text                     not null,
    mail_ticket         text                     not null,
    mail_received_date  timestamp with time zone not null,
    mail_envelope_from  text,
    mail_envelope_to    text,
    mail_header_date    timestamp with time zone,
    mail_header_from    text,
    mail_header_to      text,
    mail_header_cc      text,
    mail_header_subject text,
    mail_body_text      text,
    mail_body_html      text,
    mail_raw            text
);

grant insert, select, update on mail to "pjmail-mail";

grant select on mail to "pjmail-ui";

