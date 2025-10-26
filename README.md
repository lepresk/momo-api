# Librairie Momo API

[![Static Badge](https://img.shields.io/badge/Stable-v1.0.1-blue)](https://packagist.org/packages/lepresk/momo-api)
![GitHub](https://img.shields.io/github/license/lepresk/momo-api)

A powerful and professional PHP wrapper for integrating MTN Mobile Money API. Supports **Collection** (receive payments) and **Disbursement** (send money) operations.

## Features

| Product | Supported Operations |
|---------|---------------------|
| **Collection** | Request payments from customers, Check payment status, Get account balance |
| **Disbursement** | Transfer money, Deposit funds, Process refunds, Get account balance |
| **Sandbox** | Create API users, Generate API keys, Test environment support |

## Requirements

- PHP 7.4 or higher
- MTN MoMo Developer Account ([Sign up](https://momodeveloper.mtn.com/))
- Subscription Key (sandbox or production)

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
| `getBalance()` | Get account balance |
| `getAccessToken()` | Get OAuth token (auto-managed) |

### Sandbox API

| Method | Description |
|--------|-------------|
| `createApiUser(string $uuid, string $callback)` | Create sandbox API user |
| `getApiUser(string $uuid)` | Get API user details |
| `createApiKey(string $uuid)` | Generate API key |

## Testing

```bash
composer test
```

## Production Notes

- **Never hardcode credentials** - Use environment variables
- **Validate callbacks** - Check transaction status via API, not just callback data
- **Handle webhooks asynchronously** - Process in background queue
- **Log all transactions** - Keep audit trail
- **Test thoroughly in sandbox** before going live

## Contributing

Contributions are welcome! Please create an issue or pull request on [GitHub](https://github.com/lepresk/momo-api).

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- Documentation: [MTN MoMo Developer Portal](https://momodeveloper.mtn.com/)
- Issues: [GitHub Issues](https://github.com/lepresk/momo-api/issues)
