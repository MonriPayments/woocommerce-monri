# Monri Payments WooCommerce Plugin - Test Suite

This directory contains the automated test suite for the Monri Payments WooCommerce plugin. The plugin uses a **Hybrid Testing Architecture** consisting of an **Isolated Unit Test Suite** and a **WordPress Integration Test Suite**.

---

## Test Suites Overview

| Suite | Runner / Framework | Database Required | Execution Speed | Primary Purpose |
|---|---|---|---|---|
| **Blocks (JS)** | Jest (`wp-scripts test-unit-js`) | ❌ No | Fast (~0.9s) | WooCommerce checkout blocks, payment method registration, components, tokenization, and cart store listeners. |
| **Unit** | PHPUnit + Brain Monkey + Mockery | ❌ No | Fast (~0.2s) | Pure business logic, algorithms, cryptographic digests, fee calculation, webhook validation, and isolated class behaviors without external dependencies. |
| **Integration** | PHPUnit + `wp-phpunit` (`WP_UnitTestCase`) |  Yes (MySQL) | Moderate (~1s) | WordPress and WooCommerce lifecycle, database persistence, options management, custom payment tokens, and order state transitions. |

---

## Prerequisites & Installation

Ensure composer development dependencies are installed:

```bash
cd wp-content/plugins/monri
composer install
```

---

## How to Run Tests

### 1. Run All Tests
Runs both unit tests and integration tests sequentially:
```bash
composer test
# or
./vendor/bin/phpunit
```

### 2. Run Isolated Unit Tests Only
Executes mocked unit tests without requiring a database connection:
```bash
composer test:unit
# or
./vendor/bin/phpunit --testsuite unit
```

### 3. Run WordPress Integration Tests Only
Executes database-backed integration tests against the WordPress test environment:
```bash
composer test:integration
# or
./vendor/bin/phpunit --testsuite integration
```

### 4. Run a Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/UtilsTest.php
./vendor/bin/phpunit tests/Integration/GatewayLifecycleTest.php
```

### 5. Run a Specific Test Method
```bash
./vendor/bin/phpunit --filter test_resolve_real_order_id
```

### 6. Run JavaScript Blocks Unit Tests
Executes Jest unit tests covering JavaScript modules in `/blocks`:
```bash
npm test
# or with coverage
npm test -- --coverage
# or in watch mode
npm run test:watch
```

---

## When to Run Each Test Suite

### Run Unit Tests (`composer test:unit`):
- **During daily local development**: Fast feedback loop while writing or refactoring methods.
- **Pre-commit / Git hooks**: Instant verification that core algorithms and unit logic remain intact.
- **Pull Request CI workflows**: Lightweight checks that run without needing a database container or services.
- **When testing**:
  - Cryptographic signature and digest calculations (SHA-1, SHA-512, HMAC).
  - Order number formatting, hashing, and utility functions.
  - Installment fee computation and percentage formulas across installment tiers (2–36).
  - Webhook callback payload parsing and verification logic.
  - Gateway adapter selection and alternative payment components initialization.
  - WooCommerce Checkout Blocks data registration and schemas.

### Run Integration Tests (`composer test:integration`):
- **Before merging PRs / Releases**: Full sanity check against real WordPress and WooCommerce hooks and tables.
- **Integration CI pipelines**: Automated test stages equipped with a MySQL service container.
- **When testing**:
  - WooCommerce payment gateway filter registration (`woocommerce_payment_gateways`).
  - Settings retrieval and persistence to `wp_options`.
  - Custom payment tokens persistence (`Monri_WC_Payment_Token_Webpay`, `Monri_WC_Payment_Token_Wspay`) and retrieval via `WC_Payment_Tokens`.
  - Order status workflows and refund eligibility checks on live WooCommerce order instances.
  - High-Performance Order Storage (HPOS) and database schema interactions.

---

## Directory Structure

```
tests/
├── README.md                      # This documentation
├── bootstrap.php                  # Main test suite router & unit test bootstrap
├── stubs.php                      # WordPress/WooCommerce stubs for isolated unit testing
├── blocks/                        # JavaScript Checkout Blocks Jest Test Suite
│   ├── setup-globals.js           # Browser globals & React Act environment setup
│   ├── mocks/                     # WooCommerce & WordPress dependency mocks
│   ├── data.test.js               # Settings & data layer tests
│   ├── monri.test.js              # Base checkout block integration tests
│   ├── registration.test.js       # Payment method block registration tests
│   └── integration/               # WebPay, WSPay, & Additional payment component tests
├── Unit/                          # Isolated Unit Test Suite
│   ├── TestCase.php               # Base unit test case (Brain Monkey & Mockery setup/teardown)
│   ├── UtilsTest.php              # Monri_WC_Utils tests
│   ├── SettingsTest.php           # Monri_WC_Settings tests
│   ├── ApiTest.php                # Monri_WC_Api (WebPay API & SHA-1 digests) tests
│   ├── WspayApiTest.php           # Monri_WC_Wspay_Api (WSPay API & SHA-512 signatures) tests
│   ├── CallbackTest.php           # Monri_WC_Callback webhook verification tests
│   ├── InstallmentsFeeTest.php    # Monri_WC_Installments_Fee calculation tests
│   ├── GatewayTest.php            # Monri_WC_Gateway adapter selection tests
│   ├── LegacyMigrationTest.php    # PikPay legacy migration tests
│   ├── LoggerTest.php             # Monri_WC_Logger tests
│   ├── Adapters/                  # Gateway adapter tests
│   │   ├── WebpayFormAdapterTest.php
│   │   ├── WebpayComponentsAdapterTest.php
│   │   ├── WebpayLightboxAdapterTest.php
│   │   └── WspayAdapterTest.php
│   ├── Components/                # Alternative payment method gateway tests
│   │   ├── ApplePayGatewayTest.php
│   │   ├── GooglePayGatewayTest.php
│   │   ├── KeksPayGatewayTest.php
│   │   └── PayCekGatewayTest.php
│   └── Blocks/                    # WooCommerce Blocks integration tests
│       └── BlocksSupportTest.php
└── Integration/                   # WordPress Integration Test Suite
    ├── bootstrap.php              # wp-phpunit loader & WooCommerce/Monri activation
    ├── wp-tests-config.php        # WordPress test DB credentials & environment config
    ├── TestCase.php               # Base integration test case (WP_UnitTestCase wrapper)
    ├── GatewayLifecycleTest.php   # Gateway registration, settings DB persistence, action links
    └── OrderProcessTest.php       # Order lifecycle, refund eligibility, payment token persistence
```

---

## Writing New Tests

### Adding a Unit Test
1. Place the test file in `tests/Unit/` (or a relevant subdirectory like `Adapters/` or `Components/`).
2. Extend `Monri\Tests\Unit\TestCase`.
3. Use Brain Monkey helpers for mocking WordPress functions and hooks:
   ```php
   namespace Monri\Tests\Unit;

   use Brain\Monkey\Functions;

   class ExampleUnitTest extends TestCase {
       public function test_example_behavior(): void {
           Functions\when( 'get_option' )->justReturn( 'sample_value' );
           // Assertions...
       }
   }
   ```

### Adding an Integration Test
1. Place the test file in `tests/Integration/`.
2. Extend `Monri\Tests\Integration\TestCase`.
3. Use WordPress/WooCommerce factory helpers or helper methods provided in the base test case:
   ```php
   namespace Monri\Tests\Integration;

   class ExampleIntegrationTest extends TestCase {
       public function test_example_db_interaction(): void {
           $order = $this->create_order();
           $order->payment_complete( 'trx_123' );
           $this->assertSame( 'processing', $order->get_status() );
       }
   }
   ```

---

## Integration Test Database Configuration

Integration tests read database configuration from `tests/Integration/wp-tests-config.php`.

Default test database credentials:
- **Database Name**: `wp_monri`
- **Database User**: `wp_monri`
- **Database Password**: `wp_monri`
- **Database Host**: `localhost`
- **Table Prefix**: `wptests_`

You can override the test configuration path by setting the `WP_PHPUNIT__TESTS_CONFIG` environment variable:
```bash
WP_PHPUNIT__TESTS_CONFIG=/path/to/custom/wp-tests-config.php composer test:integration
```
