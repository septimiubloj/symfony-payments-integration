<?php

namespace App\Controller;

use App\Service\AciPaymentService;
use App\Service\Shift4PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;

class PaymentController extends AbstractController
{
    private Shift4PaymentService $shift4Service;
    private AciPaymentService $aciService;

    public function __construct(Shift4PaymentService $shift4Service, AciPaymentService $aciService)
    {
        $this->shift4Service = $shift4Service;
        $this->aciService = $aciService;
    }

    #[Route('/api/payment/{provider}', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, $provider): JsonResponse
    {
        $email = $request->request->get('email');
        $amount = $request->request->get('amount');
        $currency = $request->request->get('currency');
        $cardNumber = $request->request->get('card_number');
        $cardExpYear = $request->request->get('card_exp_year');
        $cardExpMonth = $request->request->get('card_exp_month');
        $cardCvc = $request->request->get('card_cvc');

        if ($provider === 'shift4') {
            $customer = $this->shift4Service->createCustomer($email);
            $card = $this->shift4Service->createCard($customer['id'], $cardNumber, $cardExpMonth, $cardExpYear, $cardCvc);

            $response = $this->shift4Service->chargeCustomer($customer, $card, $amount, $currency);
        } elseif ($provider === 'aci') {
            $paymentDetails = [
                'paymentBrand' => 'VISA',
                'paymentType' => 'DB',
                'card.number' => $cardNumber,
                'card.holder' => $email,
                'card.expiryMonth' => $cardExpMonth,
                'card.expiryYear' => $cardExpYear,
                'card.cvv' => $cardCvc,
                'amount' => $amount,
                'currency' => $currency,
            ];

            $response = $this->aciService->processPayment($paymentDetails);
        } else {
            return new JsonResponse(['error' => 'Invalid provider'], 400);
        }

        $data = json_decode($response->getContent(), true);

        return new JsonResponse([
            'transaction_id' => $data['id'] ?? $data['transaction_id'],
            'date' => date('Y-m-d H:i:s', $data['created']) ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'card_bin' => $data['card']['first6'] ?? substr($data['card']['number'], 0, 6),
        ]);
    }
}
