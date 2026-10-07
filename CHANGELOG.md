# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-10-07

Caller-supplied reference ids, and transfer and refund statuses that no longer
warn. Anyone who retries writes after a timeout or 5xx should upgrade: with a
random id, a lost response could not be queried and a retry could pay twice.

### Added
- Write methods take an optional caller-supplied id: `$referenceId` on MTN
  `requestToPay()`, `quickPay()`, `deposit()`, `transfer()` and `refund()`
  (sent as `X-Reference-Id`), `$transactionId` on Airtel `requestToPay()` and
  `transfer()` (sent as `transaction.id`). The id is returned and is what the
  status methods take, so after a timeout or 5xx you can query a payment whose
  response you never saw, and retry with the same id instead of risking a second
  payment ([#22](https://github.com/lepresk/momo-api/issues/22)). It must be a
  UUID; anything else throws an `InvalidArgumentException` before a request is
  sent. Omitting it keeps the random UUID, so existing code is unaffected
- `Uuid::assert()` to check a value is a UUID

### Fixed
- Reading the status of a transfer or refund raised `Undefined array key`
  warnings. MTN leaves `payerMessage` and `payeeNote` out of those bodies, and
  `Transaction::parse()` read them unguarded; under an error handler that turns
  warnings into exceptions, the status call threw. Missing `externalId`,
  `amount`, `payerMessage` and `payeeNote` now parse as `null`

## [1.4.0] - 2026-09-29

MTN Get Status failures, sandbox currency and callback guidance. Anyone reading
`Transaction::getReason()` on a failed transaction should upgrade: MTN's documented Get Status
body left it empty.

### Fixed
- A FAILED transaction from Get Status had no reason. Since January 2024 MTN
  reports the business failure in an HTTP 200 body with `reason` as a bare
  string (`"reason": "NOT_ENOUGH_FUNDS"`), but only the `{ code, message }`
  object was parsed. `Transaction::parse()` now accepts both shapes; a string becomes an
  `ErrorReason` with that code and an empty message
- An `ErrorReason` without a message rendered with a trailing space
  (`"[NOT_ENOUGH_FUNDS] "`); it now renders as `"[NOT_ENOUGH_FUNDS]"`
- Payments in the sandbox failed with the default currency. The sandbox accepts
  EUR only, but `quickPay()` and the request `make()` factories default to XAF.
  Against the sandbox, `requestToPay()`, `quickPay()`, `deposit()`, `transfer()`
  and `refund()` now send `EUR` whatever the request currency; the request
  object is left untouched, and other environments are unaffected
- The README's callback example fulfilled the order straight from the callback
  data. MTN and Airtel do not sign callbacks, so anyone who knows the callback
  URL could mark an unpaid order as paid. The example now re-queries the status
  from MTN or Airtel before fulfilling, for both providers

### Added
- `ErrorReason` constants and predicates for the Get Status failure codes MTN
  documents or returns in production: `LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED`,
  `COULD_NOT_PERFORM_TRANSACTION`, `SENDER_ACCOUNT_NOT_ACTIVE`,
  `PAYEE_LIMIT_REACHED`, `TRANSACTION_NOT_FOUND`, `VALIDATION_ERROR`
- `ErrorReason::isPayerFundingFailure()`: true for `NOT_ENOUGH_FUNDS`,
  `PAYER_LIMIT_REACHED` and `LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED`.
  MTN Congo returns the last one instead of `NOT_ENOUGH_FUNDS`, so
  `isNotEnoughFunds()` alone misses an insufficient balance there
- `MomoApi::SANDBOX_CURRENCY`: `'EUR'`, the only currency the sandbox accepts

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
