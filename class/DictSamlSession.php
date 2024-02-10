<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\type\ErrorLevel;

$driver = Config::getConfig("database/driver");
Logging::debug("selected db driver for DictSamlSession is " . $driver);

switch($driver) {
    case "pgsql":
        class_alias("StudioDemmys\\pjmail\\DictSamlSessionPgSql", "StudioDemmys\\pjmail\\DictSamlSession");
        break;
    default:
        throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_DB_UNSUPPORTED", "driver = {$driver}");
        break;
}