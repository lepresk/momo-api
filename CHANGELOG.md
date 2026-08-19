# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-08-19

Airtel Money corrections. Anyone using the Airtel products should upgrade: the
previous release could report a refused payment as accepted.

### Fixed
- `TI` ("transaction initiated") was not recognised as a pending status. A `TI`
  transaction answered `false` to `isPending()`, `isSuccessful()` **and**
  `isFailed()`, leaving the caller with a transaction in no state at all
- Airtel reports business failures — insufficient funds, invalid PIN, unknown
  transaction — with HTTP 200 and `status.success: false` in the body. That
  envelope was ignored, so `requestToPay()` and `transfer()` returned an
  externalId for a request Airtel had refused. Both now throw a `MomoException`
  carrying Airtel's own message
- The MSISDN was sent verbatim. Airtel expects a national number, so a
  country-prefixed one (`242068511358`) was rejected. It is now stripped before
  the request; an already-national number is untouched
- `AirtelTransaction` exposed no `reference_id`, the identifier Airtel returns

### Added
- `AirtelPin::encrypt($pin, $publicKey)` — RSA/PKCS#1 v1.5 encryption of a
  disbursement PIN with Airtel's public key, accepted base64-encoded or as PEM.
  The transfer endpoint requires an encrypted PIN and there was previously no
  way to produce one
- `AirtelResponseStatus` — the `status` envelope Airtel returns alongside `data`
- `Phone::clean()` and `Phone::AIRTEL_COUNTRY_CODES`
- `AirtelTransaction::getReferenceId()` and the `STATUS_IN_PROGRESS` constant

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
