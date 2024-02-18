<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="./vendor/twbs/bootstrap/dist/css/bootstrap.css" rel="stylesheet">
        {nocache}<title>PJMail viewer ({$project|escape}-{$ticket|escape})</title>{/nocache}
    </head>
    <body>
        {nocache}
        <div class="container">
            <div class="container-fluid">
                <div class="d-flex justify-content-end">User: {$user_display_name|default|escape} ({$user_id|default|escape})</div>
            </div>
            <h1>{$project|escape}-{$ticket|escape}</h1>
            <div>mail address: <a href="mailto:{$project|escape}{$separator|escape}{$ticket|escape}@{$domain|escape}">{$project|escape}{$separator|escape}{$ticket|escape}@{$domain|escape}</div>>
            <hr class="border border-primary border-3 opacity-75">
                {if $invalid|default}
                    <div class="alert alert-danger" role="alert">
                        <h3 class="alert-heading">
                            <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" fill="currentColor"
                                 class="bi bi-x-circle-fill" viewBox="0 0 16 16" role="img"
                                 aria-label="Error:">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293
                                8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707
                                8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/>
                            </svg>
                            Invalid Ticket Locator!
                        </h3>
                        <p>The ticket locator you specified is not valid.</p>
                        <hr>
                        <p class="fs-6">project: {$project|escape} / ticket: {$ticket|escape}</p>
                    </div>
                {elseif $nodata|default}
                    <div class="alert alert-warning" role="alert">
                        <h3 class="alert-heading">
                            <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" fill="currentColor"
                                 class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16" role="img"
                                 aria-label="Warning:">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98
                                1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552
                                0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                            </svg>
                            No Data!
                        </h3>
                        <p>The ticket locator you specified has not yet been associated to any mails.</p>
                        <hr>
                        <p class="fs-6">project: {$project|escape} / ticket: {$ticket|escape}</p>
                    </div>
                {else}
                    {foreach $mail_array as $i => $mail}
                        <div class="grid gap-3 border border-3 rounded-3 mb-3 p-3">
                            <div class="container"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#mailbody-body-{$mail->id|escape}"
                                 aria-controls="mailbody-body-{$mail->id|escape}"
                                 role="button">
                                <div class="row">
                                    <div class="col">
                                        Date: {$mail->mail_header_date|escape}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        From: {$mail->mail_header_from|escape}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        Subject: {$mail->mail_header_subject|escape}
                                    </div>
                                </div>
                            </div>
                            <div class="collapse{if $i == 0} show{/if}"
                                 id="mailbody-body-{$mail->id|escape}">
                                <hr>
                                <nav>
                                    <div class="nav nav-tabs" id="mailbody-tab-{$mail->id|escape}" role="tablist">
                                        <button class="nav-link active"
                                                id="mailbody-tab-{$mail->id|escape}-text"
                                                data-bs-toggle="tab"
                                                data-bs-target="#mailbody-content-{$mail->id|escape}-text"
                                                type="button"
                                                role="tab"
                                                aria-controls="mailbody-content-{$mail->id|escape}-text"
                                                aria-selected="true"
                                                {if $mail->mail_body_text == ""}disabled{/if}>
                                            text
                                        </button>
                                        <button class="nav-link"
                                                id="mailbody-tab-{$mail->id|escape}-html"
                                                data-bs-toggle="tab"
                                                data-bs-target="#mailbody-content-{$mail->id|escape}-html"
                                                type="button"
                                                role="tab"
                                                aria-controls="mailbody-content-{$mail->id|escape}-html"
                                                aria-selected="false"
                                                {if $mail->mail_body_html == ""}disabled{/if}>
                                            html
                                        </button>
                                        <button class="nav-link"
                                                id="mailbody-tab-{$mail->id|escape}-raw"
                                                data-bs-toggle="tab"
                                                data-bs-target="#mailbody-content-{$mail->id|escape}-raw"
                                                type="button"
                                                role="tab"
                                                aria-controls="mailbody-content-{$mail->id|escape}-raw"
                                                aria-selected="false"
                                                {if $mail->mail_raw == ""}disabled{/if}>
                                            raw
                                        </button>
                                    </div>
                                </nav>
                                <div class="tab-content border-start border-end border-bottom rounded-bottom"
                                     id="mailbody-content-{$mail->id|escape}">
                                    <div class="tab-pane fade show active"
                                         id="mailbody-content-{$mail->id|escape}-text"
                                         role="tabpanel"
                                         aria-labelledby="mailbody-tab-{$mail->id|escape}-text"
                                         tabindex="0">
                                        <div class="container">
                                            <div class="row overflow-auto">
                                                <div class="col">
                                                    <pre>{$mail->mail_body_text|escape}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade"
                                         id="mailbody-content-{$mail->id|escape}-html"
                                         role="tabpanel"
                                         aria-labelledby="mailbody-tab-{$mail->id|escape}-html"
                                         tabindex="0">
                                        <div class="container">
                                            <div class="row overflow-auto">
                                                <div class="col">
                                                    {$mail->mail_body_html}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade"
                                         id="mailbody-content-{$mail->id|escape}-raw"
                                         role="tabpanel"
                                         aria-labelledby="mailbody-tab-{$mail->id|escape}-raw"
                                         tabindex="0">
                                        <div class="container">
                                            <div class="row overflow-auto">
                                                <div class="col">
                                                    <pre>{$mail->mail_raw|escape}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                {/if}
        </div>
        {/nocache}
        <script src="./vendor/twbs/bootstrap/dist/js/bootstrap.bundle.js"></script>
    </body>
</html>