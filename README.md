# symfony-payments-integration

### Project Setup
1. cp .env.example .env

2. docker-compose up --build

3. composer install

### Usage

#### API Endpoint
URL: `/api/payment/{provider}`
Method: `POST`
Providers: `shift4`, `aci`

#### Request Parameters:

`email`: The customer's email address.
`amount`: The amount to charge.
`currency`: The currency of the amount.
`card_number`: The credit card number.
`card_exp_year`: The expiry year of the card.
`card_exp_month`: The expiry month of the card.
`card_cvc`: The card verification code.

#### Example Request
```bash
curl -X POST http://localhost:8000/api/payment/shift4 \
     -d "email=test@example.com" \
     -d "amount=100.00" \
     -d "currency=EUR" \
     -d "card_number=4242424242424242" \
     -d "card_exp_year=2025" \
     -d "card_exp_month=06" \
     -d "card_cvc=123"
```

##### Response
Response:

Returns a JSON object with the transaction details, such as `transaction_id`, `date`, `amount`, `currency`, and `card_bin`.

#### CLI Command

- Command: `app.payment`

##### Example Command:

```bash
bin/console app:payment shift4
```

The command will ask for each parameter input.