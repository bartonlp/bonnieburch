<?php
// This is an example of sending an attachment.

use SendGrid\Mail\Mail;

require_once getenv("SITELOADNAME");

$email = new Mail();
$email->setFrom("MitchellFamily@bonnieburch.com", "Server");
$email->setSubject("Send an attachment");
$email->addTo("bartonphillips@gmail.com", "Barton");
$email->addContent("text/plain", "This is a test with an attachment");
$email->addContent(
    "text/html", "<strong>This is a test with an attachment</strong><div id=''>Test</div>"
);

$file_encoded = base64_encode(file_get_contents('https://bartonphillips.net/images/1997_dennis_ritchie.jpg'));
$email->addAttachment(
    $file_encoded,
    "image/jpeg",
    "1997_dennis_ritchie.jpg",
    "attachment",
);

$email->addAttachment(
    $file_encoded,
    "image/jpeg",
    "Dennis Richie",
    "inline",
    "dennis",
);

$apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
$sendgrid = new \SendGrid($apiKey);
try {
    $response = $sendgrid->send($email);
    print $response->statusCode() . "\n";
    print_r($response->headers());
    print $response->body() . "\n";
} catch (Exception $e) {
    echo 'Caught exception: '.  $e->getMessage(). "\n";
}