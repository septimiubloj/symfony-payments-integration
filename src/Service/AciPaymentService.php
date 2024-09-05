<?php

namespace App\Service;

use GuzzleHttp\Client;

class AciPaymentService
{
    private Client $client;

    public function __construct(string $aciApiKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://test.oppwa.com/v1/payments',
            'headers' => [
                'Authorization' => 'Bearer ' . $aciApiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function processPayment(array $paymentDetails)
    {
        $response = $this->client->post('/', [
            'json' => $paymentDetails,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
