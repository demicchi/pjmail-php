<?php
namespace StudioDemmys\pjmail;

use StudioDemmys\pjmail\type\TicketLocator;

const HTML_PURIFIER_CACHE_DIR = "./htmlpurifier_cache";

define("PJMAIL_RUNNING_MODE", "ui");
require_once dirname(__FILE__)."initialize.php";

Logging::debug("running in " . PJMAIL_RUNNING_MODE . " mode.");

$project = Common::sanitizeUserInput($_GET["pj"] ?? "");
$ticket = Common::sanitizeUserInput($_GET["num"] ?? "");
Logging::debug("project={$project}, ticket={$ticket}");

Logging::debug("check if the user is logged in.");
$auth = new AuthSaml();
if (!$auth->isLoggedIn()) {
    Logging::debug("the user is not logged in. redirect to login URL.");
    $auth->login($project, $ticket);
    exit();
}

Logging::debug("the user is logged in.");

$user_id = $auth->getUserId();
$user_display_name = $auth->getUserDisplayName();
Logging::debug("user_id={$user_id}, user_display_name={$user_display_name}");

$html_renderer = new HtmlRenderer();
$html_renderer->assign('project', $project);
$html_renderer->assign('ticket', $ticket);
$html_renderer->assign('user_id', $user_id);
$html_renderer->assign('user_display_name', $user_display_name);

if ($project == "" || $ticket == "") {
    Logging::debug("ticket locator is invalid");
    $html_renderer->assign('invalid', true);
} else {
    $ticket_locator = new TicketLocator($project, $ticket);
    $dict_mail = new DictMail();
    $mail_array = $dict_mail->getMailByRedmineTicket($ticket_locator);
    if (count($mail_array) == 0) {
        Logging::debug("ticket locator has no associated mails");
        $html_renderer->assign('nodata', true);
    } else {
        foreach ($mail_array as $mail) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', Common::getAbsolutePath(HTML_PURIFIER_CACHE_DIR));
            $config->set('URI.DisableExternal', true);
            $config->set('URI.DisableExternalResources', true);
            $config->set('URI.DisableResources', true);
            $purifier = new \HTMLPurifier($config);
            $mail->mail_body_html = $purifier->purify($mail->mail_body_html);
        }
        Logging::debug("associated mails are: " . print_r($mail_array, true));
        $html_renderer->assign('mail_array', $mail_array);
    }
}
$html_renderer->display('show_mail.tpl');

