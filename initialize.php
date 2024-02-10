<?php

namespace StudioDemmys\pjmail;

if (!defined('PJMAIL_RUNNING_MODE'))
    throw new \Exception("[FATAL] PJMAIL_RUNNING_MODE is not defined.");

if (!defined('PJMAIL_UNIQUE_ID'))
    define("PJMAIL_UNIQUE_ID", bin2hex(random_bytes(4)));

// fundamental classes and default configurations
// The loading order is important! DO NOT modify!
require_once dirname(__FILE__)."/class/Common.php";
require_once dirname(__FILE__)."/class/Config.php";

Config::loadConfig();
Config::getConfigOrSetIfUndefined("language", "en");
Config::getConfigOrSetIfUndefined("logging/level", "debug");
Config::getConfigOrSetIfUndefined("logging/file/ui", "./pjmail-ui.log");
Config::getConfigOrSetIfUndefined("logging/file/mail", "./pjmail-mail.log");

require_once dirname(__FILE__)."/class/type/ErrorLevel.php";
require_once dirname(__FILE__)."/class/Logging.php";
require_once dirname(__FILE__)."/class/PJMailException.php";


// composer libraries
require_once dirname(__FILE__)."/vendor/autoload.php";
require_once dirname(__FILE__)."/class/DictMail.php";
require_once dirname(__FILE__)."/class/DictSamlSession.php";
