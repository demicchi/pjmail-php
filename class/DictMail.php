<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\type\ErrorLevel;

$driver = Config::getConfig("database/driver");
Logging::debug("selected db driver for DictMail is " . $driver);

switch($driver) {
    case "pgsql":
        class_alias("StudioDemmys\\pjmail\\DictMailPgSql", "StudioDemmys\\pjmail\\DictMail");
        break;
    default:
        throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_UNSUPPORTED", "driver = {$driver}");
        break;
}
