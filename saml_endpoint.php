<?php

namespace StudioDemmys\pjmail;

define("PJMAIL_RUNNING_MODE", "ui");
require_once dirname(__FILE__)."/initialize.php";

Logging::debug("running in " . PJMAIL_RUNNING_MODE . " mode.");

if (isset($_GET['acs'])) {
    $auth = new AuthSaml();
    $auth->assertionConsumerService();
    exit();
}

if (isset($_GET['metadata'])) {
    AuthSaml::publishMetadata();
    exit();
}

// default
AuthSaml::publishMetadata();
exit();

