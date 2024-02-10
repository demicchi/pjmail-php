<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\dto\SamlSessionDto;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\SamlTokenType;

abstract class DictSamlSessionAbst
{
    const MAX_TOKEN_LIFETIME_DEFAULT = 86400;
    
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
    
    protected function deleteOldToken(\DateTime $expired): void
    {
        $sql = 'DELETE FROM saml_session WHERE created < :created;';
        try {
            Logging::debug("delete token. expire = " . static::getTimestampText($expired));
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                "created" => static::getTimestampText($expired),
            ]);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_DELETE_SAML_SESSION_FAILURE", $e->getMessage());
        }
        if (!$result)
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_DELETE_SAML_SESSION_FAILURE");
        Logging::debug("tokens deleted.");
    }
    
    public function saveToken(SamlSessionDto $saml_session_dto): void
    {
        try {
            $created = new \DateTime($saml_session_dto->created);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_INVALID_CREATED_DATE", $saml_session_dto->created);
        }
        
        $sql = 'INSERT INTO saml_session (id, token_type, token, created)
                VALUES (:id, :token_type, :token, :created);';
        try {
            Logging::debug("save token. type = " . $saml_session_dto->token_type . ", token = " . $saml_session_dto->token);
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                "id" => $saml_session_dto->id,
                "token_type" => $saml_session_dto->token_type,
                "token" => $saml_session_dto->token,
                "created" => static::getTimestampText($created),
            ]);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INSERT_SAML_SESSION_FAILURE", $e->getMessage());
        }
        if (!$result)
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_INSERT_SAML_SESSION_FAILURE");
        Logging::debug("the token saved.");
    }
    
    public function checkTokenValidity(SamlTokenType $token_type, string $token, \DateTime $now = null): bool
    {
        if (empty($token))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_EMPTY_TOKEN");
        
        try {
            if (is_null($now))
                $now = new \DateTime();
            $expired = $now->sub(
                new \DateInterval("PT" .
                    Config::getConfigOrSetIfUndefined("auth/saml/expected_maximum_token_lifetime", self::MAX_TOKEN_LIFETIME_DEFAULT) .
                    "S")
            );
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_DATE_FAILURE");
        }
        
        static::deleteOldToken($expired);
        
        $sql = 'SELECT COUNT(id) FROM saml_session WHERE token_type = :token_type AND token = :token;';
        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                "token_type" => $token_type->value,
                "token" => $token,
            ]);
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_SELECT_SAML_SESSION_FAILURE", $e->getMessage());
        }
        if (!$result)
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_SELECT_SAML_SESSION_FAILURE");
        
        try {
            if ($stmt->fetchColumn() > 0)
                return false;
        } catch (\PDOException $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_FETCH_SAML_SESSION_FAILURE", $e->getMessage());
        }
        Logging::debug("the token is valid!");
        return true;
    }
    
    public static function getTimestampText(\DateTime $datetime): string
    {
        return $datetime->format("Y-m-d H:i:s");
    }
    
}