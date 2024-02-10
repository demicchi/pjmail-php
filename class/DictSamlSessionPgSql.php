<?php

namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\type\ErrorLevel;

class DictSamlSessionPgSql extends DictSamlSessionAbst
{
    public function __construct()
    {
        $connection_string = match (constant('PJMAIL_RUNNING_MODE')) {
            "ui" => Config::getConfig("database/ui/pgsql/connection_string"),
            default => throw new PJMailException(ErrorLevel::Error, "PJMAIL_E_INVALID_RUNNING_MODE",
                "PJMAIL_RUNNING_MODE = " . constant("PJMAIL_RUNNING_MODE")),
        };
        parent::__construct("pgsql:" . $connection_string);
    }
}