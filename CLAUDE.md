# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Professional PHP library providing a modern, fluent wrapper for MTN Mobile Money (MoMo) API. Supports **Collection** (receive payments from customers) and **Disbursement** (send money to beneficiaries) across multiple African countries.

## Development Commands

### Testing
```bash
composer test
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html coverage
```

### Dependencies
```bash
composer install
composer update
```

## Architecture

### Core Components

**MomoApi (src/MomoApi.php)** - Main entry point with fluent API
- Fluent factories: `MomoApi::collection([...config])`, `MomoApi::disbursement([...config])`
- Legacy factory: `MomoApi::create($environment)` (backward compatibility)
- Manages Symfony HttpClient instance (singleton pattern)
- Environment-aware URL routing

**Config (src/Config.php)** - Immutable configuration
- Factory methods: `Config::sandbox()`, `Config::collection()`, `Config::disbursement()`
- Properties: subscriptionKey, apiUser, apiKey, callbackUri

**ApiProduct (src/ApiProduct.php)** - Abstract base for product APIs
- Base class for SandboxApi, CollectionApi, DisbursementApi
- Provides HttpClient, environment, config access

### Product APIs (src/Products/)

**CollectionApi** - Receive payments from customers
- `requestToPay(PaymentRequest)` - Request payment (uses "payer")
- `quickPay(amount, phone, ref)` - Convenience helper
- `getPaymentStatus(paymentId)` - Check status (accepts 200/202)
- `getBalance()` - Account balance
- Sends X-Callback-Url header when configured

**DisbursementApi** - Send money to beneficiaries
- `transfer(TransferRequest)` - Send money (uses "payee")
- `getTransferStatus(transferId)` - Check transfer
- `deposit(PaymentRequest)` - Deposit operation
- `getDepositStatus(depositId)` - Check deposit
- `refund(RefundRequest)` - Process refund
- `getRefundStatus(refundId)` - Check refund
- `getBalance()` - Account balance
- All operations send X-Callback-Url when configured

**SandboxApi** - Sandbox provisioning
- `createApiUser(uuid, callback)` - Create test user
- `getApiUser(uuid)` - Get user info
- `createApiKey(uuid)` - Generate API key

### Models (src/Models/)

**PaymentRequest** - Collection payment model
- Uses "payer" field (customer who pays)
- Properties: amount (string), currency, externalId, payer, payerMessage, payeeNote
- Helper: `PaymentRequest::make(amount, payer, externalId, currency='XAF')`

**TransferRequest** - Disbursement transfer model
- Uses "payee" field (beneficiary who receives)
- Properties: amount (string), currency, externalId, payee, payerMessage, payeeNote
- Helper: `TransferRequest::make(amount, payee, externalId, currency='XAF')`

**RefundRequest** - Refund model
- Additional: referenceIdToRefund (UUID of original transaction)
- Helper: `RefundRequest::make(amount, refId, externalId, currency='XAF')`

**Transaction** - Response model
- Parses both Collection (payer) and Disbursement (payee) responses
- Status helpers: `isSuccessful()`, `isPending()`, `isFailed()`
- Getters: `getPayer()`, `getPayee()` (alias), `getReason()` (returns ErrorReason)

**ErrorReason** - Structured error information
- Constants for all error codes (PAYEE_NOT_FOUND, NOT_ENOUGH_FUNDS, etc.)
- Helpers: `isNotEnoughFunds()`, `isPayerLimitReached()`, etc.
- String representation: `[CODE] message`

**AccountBalance** - Balance response
- Properties: availableBalance, currency

### Exception Handling (src/Exceptions/)

**ExceptionFactory** - Maps HTTP codes to exceptions
- 400 → BadRequestExeption
- 401 → InvalidSubscriptionKeyException
- 404 → ResourceNotFoundException
- 409 → ConflictException
- 500 → InternalServerErrorException

All exceptions extend `MomoException`

## Key Patterns

### Fluent Configuration
```php
$collection = MomoApi::collection([
    'environment' => 'sandbox',
    'subscription_key' => '...',
    'api_user' => '...',
    'api_key' => '...',
    'callback_url' => 'https://...'
]);
```

### Collection vs Disbursement Semantics
- **Collection**: Customer → Merchant (uses "payer")
- **Disbursement**: Business → Beneficiary (uses "payee")

### Callback Flow
1. Configure callback_url in config
2. Library sends X-Callback-Url header automatically
3. MTN sends GET request to callback URL on status change
4. Parse with `Transaction::parse($_GET)`

### Status Code Handling
- Success: 200 or 202 (both accepted)
- 202 = Accepted/Pending (valid success response)

### Error Handling
```php
try {
    $payment = $collection->quickPay(...);
} catch (ResourceNotFoundException $e) {
    // 404
} catch (InternalServerErrorException $e) {
    // 500
}

// Or check transaction reason
if ($transaction->isFailed()) {
    $reason = $transaction->getReason();
    if ($reason->isNotEnoughFunds()) { ... }
}
```

## API Endpoints

### Collection
- POST `/collection/v1_0/requesttopay` - Request payment
- GET `/collection/v1_0/requesttopay/{id}` - Get status
- GET `/collection/v1_0/account/balance` - Get balance
- POST `/collection/token/` - Get OAuth token (auto-handled)

### Disbursement
- POST `/disbursement/v1_0/transfer` - Transfer money
- GET `/disbursement/v1_0/transfer/{id}` - Get transfer status
- POST `/disbursement/v1_0/deposit` - Deposit funds
- GET `/disbursement/v1_0/deposit/{id}` - Get deposit status
- POST `/disbursement/v1_0/refund` - Process refund
- GET `/disbursement/v1_0/refund/{id}` - Get refund status
- GET `/disbursement/v1_0/account/balance` - Get balance
- POST `/disbursement/token/` - Get OAuth token (auto-handled)

### Sandbox
- POST `/v1_0/apiuser` - Create API user
- GET `/v1_0/apiuser/{uuid}` - Get API user
- POST `/v1_0/apiuser/{uuid}/apikey` - Create API key

## Important Notes

- **Amount Type**: Always string (matches API spec)
- **Phone Format**: International format without + (e.g., "242068511358")
- **UUIDs**: Use `Utilities::guidv4()` for reference IDs
- **Tokens**: Auto-managed, no manual handling needed
- **Callbacks**: Always verify transaction via API, don't trust callback alone
- **Environment**: Use constants from MomoApi class

## Testing

- Mock responses using `MockResponse`
- Override client: `MomoApi::useClient($mockClient)`
- Test helpers: `tests/TestCase.php`
- Example: `tests/Products/SandboxApiTest.php`
