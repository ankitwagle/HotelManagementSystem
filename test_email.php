<?php

require_once __DIR__ . '/services/MailService.php';

$mailService = new MailService();

$result = $mailService->send(

    'ankitwagle5@gmail.com',

    'Test User',

    'LuxeStay Email Test',

    '
    <h2>Email is working!</h2>

    <p>
        Congratulations! LuxeStay can now
        send emails successfully.
    </p>
    '

);

if ($result) {

    echo "Email sent successfully!";

} else {

    echo "Email failed to send.";

}