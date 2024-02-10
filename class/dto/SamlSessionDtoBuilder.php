<?php

namespace StudioDemmys\pjmail\dto;

use Ramsey\Uuid\Uuid;
use StudioDemmys\pjmail\PJMailException;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\SamlTokenType;

class SamlSessionDtoBuilder
{
    public function __construct(
        public ?string $id = null,
        public ?SamlTokenType $token_type = null,
        public ?string $token = null,
        public ?\DateTime $created = null,
    ){}
    
    public function __clone()
    {
        if (!is_null($this->token_type))
            $this->token_type = clone $this->token_type;
        if (!is_null($this->created))
            $this->created = clone $this->created;
    }
    
    public function build()
    {
        if (is_null($this->token_type))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_DTO_PARAMETER_MISSING", "token_type");
        if (is_null($this->token))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_DTO_PARAMETER_MISSING", "token");
        
        if ($this->id == "")
            $this->id = Uuid::uuid7();
        if (is_null($this->created)) {
            try {
                $this->created = new \DateTime();
            } catch (\Exception $e) {
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_DTO_DATE_FAILURE");
            }
        }
        
        return new SamlSessionDto(
            $this->id,
            $this->token_type->value,
            $this->token,
            $this->created->format("Y-m-d H:i:s"),
        );
    }
}