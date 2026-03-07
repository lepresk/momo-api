# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-03-07

### Added
- **Airtel Money support**: `AirtelApi`, `AirtelCollectionApi`, `AirtelDisbursementApi`
  - `AirtelCollectionApi::requestToPay()`, `getPaymentStatus()`, `getBalance()`
  - `AirtelDisbursementApi::transfer()`, `getTransferStatus()`, `getBalance()`
  - `AirtelConfig` with static `collection()` and `disbursement()` factories
  - `AirtelTransaction` with `isSuccessful()`, `isPending()`, `isFailed()` helpers
- **Token caching**: `CollectionApi` and `DisbursementApi` now cache access tokens for their TTL, avoiding redundant auth requests
- `CollectionApi::checkAccountHolder()` — verify an MSISDN is active before initiating a payment
- `DisbursementApi::checkAccountHolder()` — verify an MSISDN is active before initiating a transfer
- `TokenCache` support class for in-memory token TTL management

## [1.1.0] - 2025-02-27

### Added
- `Disbursement::deposit()` — deposit funds to a customer account
- `Disbursement::getDepositStatus()` — check deposit transaction status
- `Disbursement::refund()` — refund a previous collection payment
- `Disbursement::getRefundStatus()` — check refund transaction status
- `RefundRequest` model with static `make()` factory method
- Typed exception hierarchy: `ResourceNotFoundException`, `InternalServerErrorException`, `ConflictException`, `InvalidSubscriptionKeyException`
- `ErrorReason` helpers: `isNotEnoughFunds()`, `isPayerLimitReached()`
- `Collection::quickPay()` shorthand for simple payment requests

### Changed
- `MomoApi::collection()` and `MomoApi::disbursement()` now accept a flat config array for simpler initialization

## [1.0.0] - 2025-01-15

### Added
- Initial release
- `Collection` product: `requestToPay()`, `getPaymentStatus()`, `getBalance()`, `getAccessToken()`
- `Disbursement` product: `transfer()`, `getTransferStatus()`, `getBalance()`, `getAccessToken()`
- `Sandbox` product: `createApiUser()`, `getApiUser()`, `createApiKey()`
- Support for 12 MTN environments (sandbox + 11 production markets)
- `PaymentRequest` and `TransferRequest` models
- `Transaction` model with `isSuccessful()`, `isPending()`, `isFailed()` helpers
- `Balance` model
