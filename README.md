# Librairie Momo API

[![Static Badge](https://img.shields.io/badge/Stable-v1.2.0-blue)](https://packagist.org/packages/lepresk/momo-api)
[![CI](https://github.com/lepresk/momo-api/actions/workflows/phpunit.yml/badge.svg)](https://github.com/lepresk/momo-api/actions/workflows/phpunit.yml)
![GitHub](https://img.shields.io/github/license/lepresk/momo-api)

A powerful and professional PHP wrapper for integrating **MTN Mobile Money** and **Airtel Money** APIs. Supports **Collection** (receive payments) and **Disbursement** (send money) operations.

## Features

| Provider | Product | Supported Operations |
|----------|---------|---------------------|
| **MTN MoMo** | Collection | Request payments, Check payment status, Check account holder, Get balance |
| **MTN MoMo** | Disbursement | Transfer money, Deposit funds, Process refunds, Check account holder, Get balance |
| **MTN MoMo** | Sandbox | Create API users, Generate API keys, Test environment support |
| **Airtel Money** | Collection | Request payments, Check payment status, Get balance |
| **Airtel Money** | Disbursement | Transfer money, Check transfer status, Get balance |

## Requirements

- PHP 8.2 or higher
- MTN MoMo Developer Account ([Sign up](https://momodeveloper.mtn.com/)) and/or Airtel Money API credentials

## Installation

```bash
composer require lepresk/momo-api
```

## Quick Start

### Collection API (Receive Payments)

```php
<?php
use Lepresk\MomoApi\MomoApi;

// Simple fluent configuration
$collection = MomoApi::collection([
    'environment' => 'sandbox', // or 'mtncongo', 'mtnuganda', etc.
    'subscription_key' => 'YOUR_SUBSCRIPTION_KEY',
    'api_user' => 'YOUR_API_USER',
    'api_key' => 'YOUR_API_KEY',
    'callback_url' => 'https://yourdomain.com/callback'
]);

// Quick payment - 3 parameters
$paymentId = $collection->quickPay('1000', '242068511358', 'ORDER-123');

// Check payment status
$transaction = $collection->getPaymentStatus($paymentId);

if ($transaction->isSuccessful()) {
    echo "Payment of {$transaction->getAmount()} received!";
}
```

### Disbursement API (Send Money)

```php
<?php
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Models\TransferRequest;

$disbursement = MomoApi::disbursement([
    'environment' => 'sandbox',
    'subscription_key' => 'YOUR_SUBSCRIPTION_KEY',
    'api_user' => 'YOUR_API_USER',
    'api_key' => 'YOUR_API_KEY',
    'callback_url' => 'https://yourdomain.com/callback'
]);

// Transfer money to a beneficiary
$transfer = TransferRequest::make('5000', '242068511358', 'SALARY-001');
$transferId = $disbursement->transfer($transfer);

// Check transfer status
$result = $disbursement->getTransferStatus($transferId);
```

## Sandbox Setup

```php
<?php
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Utilities;

$momo = MomoApi::create(MomoApi::ENVIRONMENT_SANDBOX);
$subscriptionKey = 'YOUR_SANDBOX_SUBSCRIPTION_KEY';

// 1. Create API User
$uuid = Utilities::guidv4();
$callbackHost = 'https://yourdomain.com/callback';
$apiUser = $momo->sandbox($subscriptionKey)->createApiUser($uuid, $callbackHost);

// 2. Create API Key
$apiKey = $momo->sandbox($subscriptionKey)->createApiKey($apiUser);

// Now use these credentials for Collection/Disbursement
```

## Advanced Usage

### Collection API - Full Example

```php
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Models\PaymentRequest;

$collection = MomoApi::collection([
    'environment' => 'mtncongo',
    'subscription_key' => env('MOMO_SUBSCRIPTION_KEY'),
    'api_user' => env('MOMO_API_USER'),
    'api_key' => env('MOMO_API_KEY'),
    'callback_url' => 'https://yourdomain.com/webhook/momo'
]);

// Custom payment request
$request = new PaymentRequest(
    amount: '2500',
    currency: 'XAF',
    externalId: 'ORDER-456',
    payer: '242068511358',
    payerMessage: 'Payment for order #456',
    payeeNote: 'Thank you for your purchase'
);

$paymentId = $collection->requestToPay($request);

// Get account balance
$balance = $collection->getBalance();
echo "Available: {$balance->getAvailableBalance()} {$balance->getCurrency()}";
```

### Disbursement API - Full Example

```php
use Lepresk\MomoApi\MomoApi;
use Lepresk\MomoApi\Models\TransferRequest;
use Lepresk\MomoApi\Models\RefundRequest;

$disbursement = MomoApi::disbursement([...config...]);

// Transfer
$transfer = new TransferRequest(
    amount: '10000',
    currency: 'XAF',
    externalId: 'PAYOUT-789',
    payee: '242068511358',
    payerMessage: 'Monthly salary',
    payeeNote: 'Salary payment for June'
);
$transferId = $disbursement->transfer($transfer);

// Refund
$refund = RefundRequest::make('1000', $originalTransactionId, 'REFUND-123');
$refundId = $disbursement->refund($refund);

// Check balance
$balance = $disbursement->getBalance();
```

## Airtel Money

### Airtel Collection API (Receive Payments)

```php
<?php
use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Models\AirtelConfig;

$collection = AirtelApi::collection('staging', AirtelConfig::collection(
    clientId: 'YOUR_CLIENT_ID',
    clientSecret: 'YOUR_CLIENT_SECRET',
));

// Request a payment
$externalId = $collection->requestToPay('5000', '068511358', 'ORDER-001');

// Check payment status
$transaction = $collection->getPaymentStatus($externalId);

if ($transaction->isSuccessful()) {
    echo "Payment received! Airtel Money ID: " . $transaction->getAirtelMoneyId();
} elseif ($transaction->isPending()) {
    echo "Payment pending...";
}

// Check balance
$balance = $collection->getBalance();
echo "Available: {$balance->getAvailableBalance()} {$balance->getCurrency()}";
```

### Airtel Disbursement API (Send Money)

```php
<?php
use Lepresk\MomoApi\AirtelApi;
use Lepresk\MomoApi\Models\AirtelConfig;

$disbursement = AirtelApi::disbursement('production', AirtelConfig::disbursement(
    clientId: 'YOUR_CLIENT_ID',
    clientSecret: 'YOUR_CLIENT_SECRET',
    encryptedPin: 'YOUR_ENCRYPTED_PIN',
));

// Transfer money
$externalId = $disbursement->transfer('10000', '068511358', 'PAY-001');

// Check transfer status
$transaction = $disbursement->getTransferStatus($externalId);

if ($transaction->isSuccessful()) {
    echo "Transfer completed!";
}
```

### Airtel Environments

| Mode | URL | Use Case |
|------|-----|----------|
| `staging` | `https://openapiuat.airtel.cg` | Testing |
| `production` | `https://openapi.airtel.cg` | Production - Congo |

---

### Handling Callbacks

```php
<?php
use Lepresk\MomoApi\Models\Transaction;

// Parse callback data
$transaction = Transaction::parse($_GET);

if ($transaction->isSuccessful()) {
    // Update your database
    $orderId = $transaction->getExternalId();
    $amount = $transaction->getAmount();

    // Process order...
} elseif ($transaction->isFailed()) {
    $reason = $transaction->getReason();
    echo "Failed: {$reason->getCode()} - {$reason->getMessage()}";
}
```

### Error Handling

```php
use Lepresk\MomoApi\Exceptions\ResourceNotFoundException;
use Lepresk\MomoApi\Exceptions\InternalServerErrorException;
use Lepresk\MomoApi\Models\ErrorReason;

try {
    $paymentId = $collection->quickPay('1000', '242068511358', 'ORDER-999');
} catch (ResourceNotFoundException $e) {
    // Payment not found
    echo "Error: " . $e->getMessage();
} catch (InternalServerErrorException $e) {
    // Server error
    echo "Server error, please retry";
}

// Check error reason from transaction
$transaction = $collection->getPaymentStatus($paymentId);
if ($transaction->isFailed()) {
    $reason = $transaction->getReason();

    if ($reason->isNotEnoughFunds()) {
        echo "Insufficient funds";
    } elseif ($reason->isPayerLimitReached()) {
        echo "Transaction limit exceeded";
    }
}
```

## Available Environments

| Constant | Value | Use Case |
|----------|-------|----------|
| `ENVIRONMENT_SANDBOX` | sandbox | Testing |
| `ENVIRONMENT_MTN_CONGO` | mtncongo | Production - Congo |
| `ENVIRONMENT_MTN_UGANDA` | mtnuganda | Production - Uganda |
| `ENVIRONMENT_MTN_GHANA` | mtnghana | Production - Ghana |
| `ENVIRONMENT_IVORY_COAST` | mtnivorycoast | Production - Ivory Coast |
| `ENVIRONMENT_ZAMBIA` | mtnzambia | Production - Zambia |
| `ENVIRONMENT_CAMEROON` | mtncameroon | Production - Cameroon |
| `ENVIRONMENT_BENIN` | mtnbenin | Production - Benin |
| `ENVIRONMENT_SWAZILAND` | mtnswaziland | Production - Swaziland |
| `ENVIRONMENT_GUINEACONAKRY` | mtnguineaconakry | Production - Guinea Conakry |
| `ENVIRONMENT_SOUTHAFRICA` | mtnsouthafrica | Production - South Africa |
| `ENVIRONMENT_LIBERIA` | mtnliberia | Production - Liberia |

## API Reference

### Collection API

| Method | Description |
|--------|-------------|
| `requestToPay(PaymentRequest $request)` | Request payment from customer |
| `quickPay(string $amount, string $phone, string $ref)` | Quick payment helper |
| `getPaymentStatus(string $paymentId)` | Check payment status |
| `checkAccountHolder(string $phone)` | Check if MSISDN is active |
| `getBalance()` | Get account balance |
| `getAccessToken()` | Get OAuth token (auto-managed) |

### Disbursement API

| Method | Description |
|--------|-------------|
| `transfer(TransferRequest $request)` | Transfer money to beneficiary |
| `getTransferStatus(string $transferId)` | Check transfer status |
| `deposit(PaymentRequest $request)` | Deposit funds |
| `getDepositStatus(string $depositId)` | Check deposit status |
| `refund(RefundRequest $request)` | Refund a transaction |
| `getRefundStatus(string $refundId)` | Check refund status |
| `checkAccountHolder(string $phone)` | Check if MSISDN is active |
| `getBalance()` | Get account balance |
| `getAccessToken()` | Get OAuth token (auto-managed) |

### Airtel Collection API

| Method | Description |
|--------|-------------|
| `requestToPay(string $amount, string $phone, string $reference)` | Request payment from customer |
| `getPaymentStatus(string $externalId)` | Check payment status (returns `AirtelTransaction`) |
| `getBalance()` | Get account balance |
| `getAccessToken()` | Get OAuth token (cached automatically) |

### Airtel Disbursement API

| Method | Description |
|--------|-------------|
| `transfer(string $amount, string $phone, string $reference)` | Transfer money (requires `encryptedPin`) |
| `getTransferStatus(string $externalId)` | Check transfer status (returns `AirtelTransaction`) |
| `getBalance()` | Get account balance |
| `getAccessToken()` | Get OAuth token (cached automatically) |

### Sandbox API

| Method | Description |
|--------|-------------|
| `createApiUser(string $uuid, string $callback)` | Create sandbox API user |
| `getApiUser(string $uuid)` | Get API user details |
| `createApiKey(string $uuid)` | Generate API key |

## Testing

The package includes two types of tests:

**Unit Tests** - Fast tests with mocked HTTP responses:
```bash
composer test
# or run specific suite
vendor/bin/phpunit --testsuite Unit
```

**Fixture Tests** - Validate parsing of real MTN API responses:
```bash
vendor/bin/phpunit --testsuite Fixtures
```

Run PHPStan analysis:
```bash
composer phpstan
```

## Production Notes

- **Never hardcode credentials** - Use environment variables
- **Validate callbacks** - Check transaction status via API, not just callback data
- **Handle webhooks asynchronously** - Process in background queue
- **Log all transactions** - Keep audit trail
- **Test thoroughly in sandbox** before going live

## Ecosystem

The same client is available for multiple languages:

| Language | Package | Install |
|----------|---------|---------|
| **PHP** | [`lepresk/momo-api`](https://github.com/lepresk/momo-api) | `composer require lepresk/momo-api` |
| **Node.js / TypeScript** | [`@lepresk/momo-api`](https://github.com/lepresk/momo-api-node) | `npm install @lepresk/momo-api` |
| **Python** | [`mtn-momo-client`](https://github.com/lepresk/momo-api-python) | `pip install mtn-momo-client` |

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full history of changes.

## License

MIT License - see [LICENSE](LICENCE) file for details.

## Support

- Documentation: [MTN MoMo Developer Portal](https://momodeveloper.mtn.com/)
- Issues: [GitHub Issues](https://github.com/lepresk/momo-api/issues)
