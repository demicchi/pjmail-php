<?php

namespace StudioDemmys\pjmail\dto;

class SamlSessionDto
{
    /**
     * @param string $id UUID
     * @param string $token_type SAML token type
     * @param string $token SAML token
     * @param string $created "Y-m-d H:i:s" (without timezone)
     */
    public function __construct(
        public string $id = "",
        public string $token_type = "",
        public string $token = "",
        public string $created = "",
    ){}
}