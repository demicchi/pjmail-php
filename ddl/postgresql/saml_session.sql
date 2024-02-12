create table saml_session
(
    id         uuid      not null
        constraint saml_session_pk
            primary key,
    token_type text      not null,
    token      text      not null,
    created    timestamp not null
);


grant delete, insert, select on saml_session to "pjmail-ui";

