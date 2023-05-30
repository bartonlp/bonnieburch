<?php
require("vendor/autoload.php");

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$httpClient = new GuzzleAdapter(new Client());

// In these examples, fetch API key from environment variable
$sparky = new SparkPost($httpClient, ["key" => getenv('SPARKPOST_API_KEY')]);
echo "<pre>"; print_r($sparky);echo "</pre>";

// put your own sending domain and test recipient address here
$sending_domain = "mail@mail.bartonphillips.com";
$your_email = "bartonphillips@gmail.com";
$your_cc = "bartonlp@outlook.com";
$your_bcc = "bonnieburch2015@gmail.com";

$promise = $sparky->transmissions->post([
    'content' => [
        'from' => [
            'name' => 'SparkPost Team',
            'email' => "from@$sending_domain",
        ],
        'subject' => 'Mailing With CC and BCC From PHP',
        'html' => '<html><body><h1>Congratulations, {{name}}!</h1><p>You just sent your very first mailing with CC and BCC recipients!</p></body></html>',
        'text' => 'Congratulations, {{name}}! You just sent your very first mailing with CC and BCC recipients!'
    ],
    'substitution_data' => ['name' => 'Barton'],
    'recipients' => [
        [
            'address' => [
                'name' => 'Barton',
                'email' => $your_email,
            ],
        ],
    ],
    'cc' => [
        [
            'address' => [
                'name' => 'ANOTHER_NAME',
                'email' => $your_cc,
            ],
        ],
    ],
    'bcc' => [
        [
            'address' => [
                'name' => 'AND_ANOTHER_NAME',
                'email' => $your_bcc,
            ],
        ],
    ],
]);

try {
    $response = $promise->wait();
    echo $response->getStatusCode()."\n";
    print_r($response->getBody())."\n";
} catch (\Exception $e) {
    echo $e->getCode()."\n";
    echo $e->getMessage()."\n";
}
