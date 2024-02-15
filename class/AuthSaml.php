<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\dto\SamlSessionDtoBuilder;
use StudioDemmys\pjmail\type\ErrorLevel;
use StudioDemmys\pjmail\type\SamlTokenType;

class AuthSaml
{
    const SAML_ENDPOINT = "saml_endpoint.php";
    const SHOW_MAIL_SCRIPT = "show_mail.php";
    const SP_NAME_ID_FORMAT_DEFAULT = "email";
    const SESSION_LIFETIME_DEFAULT = 0;
    
    protected \OneLogin\Saml2\Auth $saml;
    protected string $baseurl;
    
    public function __construct()
    {
        $this->baseurl = static::getBaseurl();
        
        $session_cookie_params = session_get_cookie_params();
        $session_cookie_params["samesite"] = "None";
        $session_cookie_params["secure"] = True;
        session_set_cookie_params($session_cookie_params);
        
        if (!session_start(['cookie_lifetime' => Config::getConfigOrSetIfUndefined("auth/saml/session_lifetime", static::SESSION_LIFETIME_DEFAULT)]))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_SESSION_FAILURE");
        Logging::debug("session id is " . session_id() . ", session: " . print_r($_SESSION, true));
        
        try {
            $this->saml = new \OneLogin\Saml2\Auth(static::getSamlSetting());
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_BAD_CONFIG", $e->getMessage());
        }
    }
    
    public function login(string $relay_project, string $relay_ticket): never
    {
        try {
            $location_url = $this->saml->login(null, array(), false, false, true);
            Logging::debug("location_url = " . $location_url);
            
            $_SESSION['saml_request_id'] = $this->saml->getLastRequestID();
            $_SESSION['saml_relay_project'] = $relay_project;
            $_SESSION['saml_relay_ticket'] = $relay_ticket;
            Logging::debug("session id is " . session_id() . ", session: " . print_r($_SESSION, true));
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_LOGIN_FAILURE", $e->getMessage());
        }
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');
        header('Location: ' . $location_url);
        exit();
    }
    
    protected function validateToken(SamlTokenType $token_type, string $token): void
    {
        Logging::debug("validate token. type = " . $token_type->value . ", token = " . $token);
        $dict_saml_session = new DictSamlSession();
        
        $token_valid = $dict_saml_session->checkTokenValidity($token_type, $token);
        if (!$token_valid)
            throw new PJMailException(ErrorLevel::Error,
                "PJMAIL_E_SAML_ACS_INVALID_TOKEN", $token_type->value . " = " . $token ?? "null");
        $dict_saml_session->saveToken(
            (new SamlSessionDtoBuilder(null, $token_type, $token))->build()
        );
    }
    
    public function assertionConsumerService(): never
    {
        Logging::debug("ACS invoked.");
        
        try {
            if (!isset($_POST['SAMLResponse']))
                throw new PJMailException(ErrorLevel::Info, "PJMAIL_E_SAML_ACS_FAILED",
                    "SAMLResponse is not found. reply 403 error.");
        } catch (PJMailException $e) {
            http_response_code(403);
            exit();
        }
        
        try {
            if (!isset($_SESSION['saml_request_id']))
                throw new PJMailException(ErrorLevel::Info, "PJMAIL_E_SAML_ACS_FAILURE",
                    "saml_request_id is not set. Assume as an IdP-initiated request and continue.");
            $request_id = $_SESSION['saml_request_id'];
        } catch (PJMailException $e) {
            $request_id = null;
        }
        Logging::debug("request_id: " . $request_id);
        
        $relay_project = "";
        $relay_ticket = "";
        if (!is_null($request_id)) {
            $this->validateToken(SamlTokenType::Request, $request_id);
            
            if (!isset($_SESSION['saml_relay_project']))
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_ACS_FAILURE", "saml_relay_project is not set.");
            if (!isset($_SESSION['saml_relay_ticket']))
                throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_ACS_FAILURE", "saml_relay_ticket is not set.");
            
            $relay_project = $_SESSION['saml_relay_project'];
            $relay_ticket = $_SESSION['saml_relay_ticket'];
        }
        
        try {
            $this->saml->processResponse($request_id);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_ACS_FAILED", $e->getMessage());
        }
        
        $this->validateToken(SamlTokenType::Message, $this->saml->getLastMessageId());
        $this->validateToken(SamlTokenType::Assertion, $this->saml->getLastAssertionId());
        
        $errors = $this->saml->getErrors();
        if (!empty($errors))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_ACS_FAILED", implode(", ", $errors));
        
        if (!$this->saml->isAuthenticated())
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_ACS_NOT_AUTHED");
        
        Logging::debug("changing the session id from " . session_id());
        session_regenerate_id();
        Logging::debug("to " . session_id());
        
        unset($_SESSION['saml_request_id']);
        $_SESSION['saml_user_data'] = $this->saml->getAttributes();
        
        $this->saml->redirectTo($this->baseurl . static::SHOW_MAIL_SCRIPT . "?pj=" . $relay_project . "&num=" . $relay_ticket);
        exit();
    }
    
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['saml_user_data']);
    }
    
    public function getUserId(): ?string
    {
        if (!$this->isLoggedIn())
            return null;
        return $_SESSION['saml_user_data'][Config::getConfig("auth/saml/user_id")][0] ?? null;
    }
    
    public function getUserDisplayName(): ?string
    {
        if (!$this->isLoggedIn())
            return null;
        return $_SESSION['saml_user_data'][Config::getConfig("auth/saml/user_display_name")][0] ?? null;
    }
    
    public static function getBaseurl(): string
    {
        $baseurl = Config::getConfigOrSetIfUndefined("auth/saml/baseurl", "");
        if (!empty($baseurl) && is_string($baseurl) && !str_ends_with($baseurl, "/"))
            $baseurl .= "/";
        Logging::debug("baseurl = " . $baseurl);
        return $baseurl;
    }
    
    public static function getNameIdFormat(): string
    {
        $name_id_format_config = Config::getConfigOrSetIfUndefined("auth/saml/sp/name_id_format", static::SP_NAME_ID_FORMAT_DEFAULT);
        return match($name_id_format_config) {
            "email" => \OneLogin\Saml2\Constants::NAMEID_EMAIL_ADDRESS,
            "unspecified" => \OneLogin\Saml2\Constants::NAMEID_UNSPECIFIED,
            default => throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_BAD_CONFIG",
                "name_id_format = " . $name_id_format_config),
        };
    }
    
    public static function getSamlSetting(): array
    {
        $baseurl = static::getBaseurl();
        try {
            return [
                "strict" => Config::getConfigOrSetIfUndefined("auth/saml/strict", true),
                "baseurl" => $baseurl,
                "sp" => [
                    "entityId" => $baseurl . static::SAML_ENDPOINT . "?metadata",
                    "assertionConsumerService" => [
                        "url" => $baseurl . static::SAML_ENDPOINT . "?acs",
                    ],
                    "NameIDFormat" => static::getNameIdFormat(),
                    "x509cert" => Config::getConfig("auth/saml/sp/x509cert"),
                    "privateKey" => Config::getConfig("auth/saml/sp/private_key"),
                ],
                "idp" => [
                    "entityId" => Config::getConfig("auth/saml/idp/entity_id"),
                    "singleSignOnService" => [
                        "url" => Config::getConfig("auth/saml/idp/single_sign_on_service/url"),
                    ],
                    "x509cert" => Config::getConfig("auth/saml/idp/x509cert"),
                ],
            ];
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_BAD_CONFIG", $e->getMessage());
        }
    }
    
    public static function publishMetadata(): never
    {
        try {
            $saml_setting = new \OneLogin\Saml2\Settings(AuthSaml::getSamlSetting(), true);
            $saml_metadata = $saml_setting->getSPMetadata();
            $saml_error = $saml_setting->validateMetadata($saml_metadata);
        } catch (\Exception $e) {
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_METADATA_FAILURE", $e->getMessage());
        }
        
        if (!empty($saml_error))
            throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_SAML_METADATA_FAILURE", implode(", ", $saml_error));
        
        header('Content-Type: text/xml');
        echo $saml_metadata;
        exit();
    }
}