<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;

class PaymentController extends AbstractController
{
    private $shift4Client;
    private $aciClient;

    public function __construct()
    {
        $this->shift4Client = new Client([
            'base_uri' => 'https://api.shift4.com',
            'headers' => [
                // 'Authorization' => 'Basic ' . base64_encode('sk_test_RTFtbTN1SmtsvCgwWuMb0MJt' . ':'),
                'Content-Type'  => 'application/json', // or 'application/json' based on Shift4 API
            ],
        ]);
        $this->aciClient = new Client(['base_uri' => 'https://api.aciworldwide.com/']);
    }

    #[Route('/api/payment/{provider}', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, $provider): JsonResponse
    {
        $amount = $request->request->get('amount');
        $currency = $request->request->get('currency');
        $cardNumber = $request->request->get('card_number');
        $cardExpYear = $request->request->get('card_exp_year');
        $cardExpMonth = $request->request->get('card_exp_month');
        $cardCvv = $request->request->get('card_cvv');

        if ($provider === 'shift4') {
            try {
                $response = $this->shift4Client->post('/charges', [
                    'form_params' => [
                        'amount' => $amount,
                        'currency' => $currency,
                        'customerId' => 'cust_EiqOaOerpVGnDI5bS8NT1nKq',
                        'card' => 'card_X4Tywc37jTrU8xRZvS5wD4BE',
                        'description' => 'Example charge',
                    ],
                ]);
            } catch (\Exception $e) {
                dump(json_decode($e->getResponse()->getBody()->getContents(), true));
                // dump($e->getCode());
                die;
                return new JsonResponse(['error' => $e->getResponse()->getBody()->getContents()], $e->getCode());
            }
        } elseif ($provider === 'aci') {
            $response = $this->aciClient->post('/payment', [
                'json' => [
                    'amount' => $amount,
                    'currency' => $currency,
                    'card_number' => $cardNumber,
                    'exp_year' => $cardExpYear,
                    'exp_month' => $cardExpMonth,
                    'cvv' => $cardCvv,
                ],
            ]);
        } else {
            return new JsonResponse(['error' => 'Invalid provider'], 400);
        }

        $data = json_decode($response->getBody(), true);

        dump($data);
        die;

        return new JsonResponse([
            'transaction_id' => $data['transaction_id'] ?? null,
            'date' => $data['date'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
            'card_bin' => substr($cardNumber, 0, 6),
        ]);
    }
}
