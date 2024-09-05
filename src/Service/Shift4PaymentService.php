<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class Shift4PaymentService
{
    private Client $shift4Client;
    private string $apiKey;

    public function __construct(string $shift4ApiKey)
    {
        $this->apiKey = $shift4ApiKey . ':';
        $this->shift4Client = new Client([
            'base_uri' => 'https://api.shift4.com',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function createCustomer(string $email): array
    {
        $config = $this->getConfig(['email' => $email]);
        $response = $this->shift4Client->request('POST', '/customers', $config);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createCard(string $customerId, string $cardNumber, int $cardExpMonth, int $cardExpYear, string $cvc): array
    {
        $config = $this->getConfig([
            'number' => $cardNumber,
            'expMonth' => $cardExpMonth,
            'expYear' => $cardExpYear,
            'cvc' => $cvc,
        ]);

        $response = $this->shift4Client->request('POST', "/customers/{$customerId}/cards", $config);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function chargeCustomer(array $customer, array $card, float $amount, string $currency): JsonResponse
    {
        try {
            $response = $this->shift4Client->post('/charges', [
                'form_params' => [
                    'amount' => ceil(number_format($amount, 2, '.', '') * pow(10, 2)),
                    'currency' => $currency,
                    'customerId' => $customer['id'],
                    'card' => $card['id'],
                    'description' => 'Example charge',
                ],
            ]);

            return new JsonResponse(json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getResponse()->getBody()->getContents()], $e->getCode());
        }
    }

    private function getConfig(array $data): array
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'auth_basic' => [$this->apiKey],
            'json' => $data,
        ];
    }
}
