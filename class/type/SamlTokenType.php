<?php

namespace StudioDemmys\pjmail\type;

enum SamlTokenType :string
{
    case Request = "request";
    case Message = "message";
    case Assertion = "assertion";
    
    public static function getSamlTokenType(string $token_type): ?SamlTokenType
    {
        foreach (self::cases() as $case) {
            if (strtolower($token_type) == strtolower($case->name))
                return $case;
        }
        return null;
    }
}