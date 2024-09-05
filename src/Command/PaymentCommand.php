<?php

namespace App\Command;

use App\Service\Shift4Service;
use App\Service\AciService;
use App\Service\Shift4PaymentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentCommand extends Command
{
    private Shift4PaymentService $shift4Service;
    // private AciService $aciService;

    public function __construct(Shift4PaymentService $shift4Service)
    {
        parent::__construct();
        $this->shift4Service = $shift4Service;
        // $this->aciService = $aciService;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:payment')
            ->setDescription('Create payment')
            ->addArgument('provider', InputArgument::REQUIRED, 'Shift4 or ACI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = $input->getArgument('provider');

        if (!in_array($provider, ['shift4', 'aci'], true)) {
            $output->writeln('<error>Invalid provider. Use "shift4" or "aci".</error>');
            return Command::FAILURE;
        }

        $questionHelper = $this->getHelper('question');

        // Collect payment details interactively
        $email = $this->askQuestion($input, $output, $questionHelper, 'Email: ');
        $amount = $this->askQuestion($input, $output, $questionHelper, 'Amount: ');
        $currency = $this->askQuestion($input, $output, $questionHelper, 'Currency: ');
        $cardNumber = $this->askQuestion($input, $output, $questionHelper, 'Card Number: ');
        $cardExpYear = $this->askQuestion($input, $output, $questionHelper, 'Card Expiration Year: ');
        $cardExpMonth = $this->askQuestion($input, $output, $questionHelper, 'Card Expiration Month: ');
        $cardCvc = $this->askQuestion($input, $output, $questionHelper, 'Card CVC: ');

        $params = [
            'email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'card_number' => $cardNumber,
            'card_exp_year' => $cardExpYear,
            'card_exp_month' => $cardExpMonth,
            'card_cvc' => $cardCvc,
        ];

        try {
            if ($provider === 'shift4') {
                $customer = $this->shift4Service->createCustomer($email);
                $card = $this->shift4Service->createCard($customer['id'], $cardNumber, $cardExpMonth, $cardExpYear, $cardCvc);
                $response = $this->shift4Service->chargeCustomer($customer, $card, $amount, $currency);
            } elseif ($provider === 'aci') {
                // $response = $this->aciService->makePayment($params);
            } else {
                $output->writeln('<error>Invalid provider.</error>');
                return Command::FAILURE;
            }


            $data = json_decode($response->getContent(), true);
            $formattedResponse = [
                'transaction_id' => $data['id'] ?? $data['transaction_id'],
                'date' => date('Y-m-d H:i:s', $data['created']) ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'card_bin' => $data['card']['first6'] ?? substr($data['card']['number'], 0, 6),
            ];
            $output->writeln(json_encode($formattedResponse, JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function askQuestion(InputInterface $input, OutputInterface $output, QuestionHelper $helper, string $questionText): string
    {
        $question = new Question($questionText);
        $question->setValidator(function ($value) {
            if (empty($value)) {
                throw new InvalidArgumentException('Value cannot be empty');
            }
            return $value;
        });
        return $helper->ask($input, $output, $question);
    }
}
