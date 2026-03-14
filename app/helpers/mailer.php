<?php

function send_demo_mail(string $to, string $subject, string $body): void
{
    $mode = env('MAIL_MODE', 'log');

    if ($mode === 'log') {
        app_log("MAIL TO: {$to} | SUBJECT: {$subject} | BODY: {$body}");
        return;
    }

    app_log("MAIL MODE não implementado totalmente. TO: {$to} | SUBJECT: {$subject}");
}