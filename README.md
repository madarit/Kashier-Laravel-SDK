# Laravel Kashier Payment Integration

A comprehensive Laravel package for integrating Kashier payment gateway with advanced features including refunds, webhooks, and event handling.

## Features

- ✅ Easy installation via Composer
- ✅ Auto-discovery for Laravel 5.5+
- ✅ Support for both test and live modes
- ✅ iFrame and Hosted Payment Page (HPP) integration
- ✅ **Refund API support** (full and partial refunds)
- ✅ **Webhook handling** for asynchronous payment notifications
- ✅ **Event-driven architecture** for payment flows
- ✅ **Signature verification middleware** for security
- ✅ **Order status enum** with helper methods
- ✅ Automatic signature validation
- ✅ Customizable views
- ✅ Facade support for easy access
- ✅ Comprehensive logging

## Requirements

- PHP 8.0 or higher
- Laravel 9.0, 10.0, 11,0 or 12.0

## Installation

### 1. Install via Composer

```bash
composer require madarit/laravel-kashier
```

The package will automatically register itself via Laravel's package auto-discovery.

### 2. Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=kashier-config
```

This will create `config/kashier.php` in your application.

### 3. Publish Views (Optional)

If you want to customize the payment views:

```bash
php artisan vendor:publish --tag=kashier-views
```

Views will be published to `resources/views/vendor/kashier/`.

### 4. Configure Environment Variables

Add the following to your `.env` file:

```env
# Mode Configuration
KASHIER_MODE=test

# Test Credentials
KASHIER_TEST_API_KEY=your-test-api-key
KASHIER_TEST_MID=your-test-mid

# Live Credentials
KASHIER_LIVE_API_KEY=
KASHIER_LIVE_MID=

# Webhook Configuration
KASHIER_WEBHOOK_ENABLED=true
KASHIER_WEBHOOK_PREFIX=kashier

# Logging
KASHIER_LOGGING_ENABLED=true

# Payment Settings
KASHIER_CURRENCY=EGP
KASHIER_ALLOWED_METHODS=card,wallet,bank_installments
```

Replace the test credentials with your actual Kashier credentials from your merchant dashboard.

## Usage

### Basic Payment Operations

```php
use Madarit\LaravelKashier\Facades\Kashier;

// Generate order hash
$hash = Kashier::generateOrderHash($orderId, $amount, $currency);

// Get HPP URL
$hppUrl = Kashier::getHppUrl($orderId, $amount, $currency, $callbackUrl);

// Validate callback signature
$isValid = Kashier::validateSignature($request->all());

// Get configuration
$config = Kashier::getConfig();
$mode = Kashier::getMode();
$mid = Kashier::getMid();
```

### Refund Operations

#### Full Refund
```php
use Madarit\LaravelKashier\Facades\Kashier;

try {
    $result = Kashier::fullRefund(
        orderId: 'order_123',
        transactionId: 'trans_456',
        reason: 'Customer requested refund'
    );
    
    if ($result['status'] === 'REFUNDED') {
        // Refund successful
        echo "Refund processed successfully";
    }
} catch (\Madarit\LaravelKashier\Exceptions\KashierRefundException $e) {
    // Handle refund error
    echo "Refund failed: " . $e->getMessage();
}
```

#### Partial Refund
```php
use Madarit\LaravelKashier\Facades\Kashier;

try {
    $result = Kashier::partialRefund(
        orderId: 'order_123',
        transactionId: 'trans_456',
        amount: 50.00, // Refund 50 EGP out of total
        reason: 'Partial refund for one item'
    );
    
    // Check status
    if ($result['status'] === 'REFUNDED') {
        echo "Partial refund of {$result['amount']} processed";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### Check Refund Status
```php
use Madarit\LaravelKashier\Facades\Kashier;

$status = Kashier::getRefundStatus('order_123', 'trans_456');
echo "Current status: " . $status['status'];
```

### Webhook Handling

The package automatically handles webhooks at `/kashier/webhook`. Configure your webhook URL in Kashier dashboard:

```
https://yourdomain.com/kashier/webhook
```

#### Listen to Webhook Events

Create an event listener to handle payment notifications:

```php
// app/Listeners/HandleKashierWebhook.php
namespace App\Listeners;

use Madarit\LaravelKashier\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;

class HandleKashierWebhook
{
    public function handle(WebhookReceived $event)
    {
        $payload = $event->payload;
        
        // Process webhook data
        Log::info('Webhook received', $payload);
        
        // Update order status in your database
        // Send notifications, etc.
    }
}
```

#### Listen to Payment Events

```php
// app/Listeners/HandleSuccessfulPayment.php
namespace App\Listeners;

use Madarit\LaravelKashier\Events\PaymentSuccessful;
use App\Models\Order;

class HandleSuccessfulPayment
{
    public function handle(PaymentSuccessful $event)
    {
        $orderId = $event->getOrderId();
        $transactionId = $event->getTransactionId();
        $amount = $event->getAmount();
        
        // Update your order
        Order::where('id', $orderId)->update([
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'paid_amount' => $amount,
        ]);
        
        // Send confirmation email, etc.
    }
}
```

#### Listen to Payment Failures

```php
// app/Listeners/HandleFailedPayment.php
namespace App\Listeners;

use Madarit\LaravelKashier\Events\PaymentFailed;

class HandleFailedPayment
{
    public function handle(PaymentFailed $event)
    {
        $orderId = $event->getOrderId();
        $reason = $event->getFailureReason();
        
        // Log failure
        \Log::warning("Payment failed for order {$orderId}: {$reason}");
        
        // Notify customer, update order status, etc.
    }
}
```

#### Listen to Refund Events

```php
// app/Listeners/HandleRefundProcessed.php
namespace App\Listeners;

use Madarit\LaravelKashier\Events\RefundProcessed;

class HandleRefundProcessed
{
    public function handle(RefundProcessed $event)
    {
        if ($event->isSuccessful()) {
            $orderId = $event->getOrderId();
            $amount = $event->getRefundAmount();
            
            // Update order status
            // Send refund confirmation email
        }
    }
}
```

#### Register Event Listeners

Add to `app/Providers/EventServiceProvider.php`:

```php
use Madarit\LaravelKashier\Events\WebhookReceived;
use Madarit\LaravelKashier\Events\PaymentSuccessful;
use Madarit\LaravelKashier\Events\PaymentFailed;
use Madarit\LaravelKashier\Events\RefundProcessed;

protected $listen = [
    WebhookReceived::class => [
        \App\Listeners\HandleKashierWebhook::class,
    ],
    PaymentSuccessful::class => [
        \App\Listeners\HandleSuccessfulPayment::class,
    ],
    PaymentFailed::class => [
        \App\Listeners\HandleFailedPayment::class,
    ],
    RefundProcessed::class => [
        \App\Listeners\HandleRefundProcessed::class,
    ],
];
```

### Using Order Status Enum

```php
use Madarit\LaravelKashier\Enums\OrderStatus;

// Get status from string
$status = OrderStatus::fromString('SUCCESS');

// Check status
if ($status->isSuccessful()) {
    echo "Payment succeeded!";
}

// Get display name
echo $status->displayName(); // "Success"

// Get description
echo $status->description(); // "Payment completed successfully"

// Get CSS class for styling
echo $status->styleClass(); // "status-success"

// Get text color
echo $status->textColor(); // "text-green-600"

// Status checks
$status->isPending();      // false
$status->isFailed();       // false
$status->isRefunded();     // false
```

### Using Dependency Injection

```php
use Madarit\LaravelKashier\KashierService;

class YourController extends Controller
{
    private $kashier;

    public function __construct(KashierService $kashier)
    {
        $this->kashier = $kashier;
    }

    public function processPayment()
    {
        $hash = $this->kashier->generateOrderHash('123', '100', 'EGP');
        // ...
    }
}
```

### Default Routes

The package automatically registers these routes:

- `GET /kashier/checkout` - Display checkout page
- `GET /kashier/iframe/callback` - iFrame payment callback
- `GET /kashier/hpp/callback` - Hosted Payment Page callback
- `POST /kashier/webhook` - Webhook endpoint (secured with signature verification)

### Accessing the Demo

After installation, visit:

```
http://your-app.test/kashier/checkout
```

### Test Cards

- **Success:** 5111 1111 1111 1118 - 06/28 - 100
- **Success 3D Secure:** 5123 4500 0000 0008 - 06/28 - 100
- **Failure:** 5111 1111 1111 1118 - 05/28 - 102

## Customization

### Custom Views

After publishing views, you can customize them in `resources/views/vendor/kashier/`:

- `payment/checkout.blade.php` - Checkout page
- `payment/success.blade.php` - Success page
- `payment/error.blade.php` - Error page

### Custom Controller

You can create your own controller and use the KashierService:

```php
use Madarit\LaravelKashier\KashierService;
use Illuminate\Http\Request;

class CustomPaymentController extends Controller
{
    public function initiatePayment(KashierService $kashier)
    {
        $orderId = uniqid('order_');
        $amount = '100.00';
        $currency = 'EGP';
        
        $hppUrl = $kashier->getHppUrl(
            $orderId, 
            $amount, 
            $currency, 
            route('payment.callback')
        );
        
        return redirect($hppUrl);
    }
    
    public function handleCallback(Request $request, KashierService $kashier)
    {
        if ($kashier->validateSignature($request->all())) {
            if ($request->get('paymentStatus') === 'SUCCESS') {
                // Handle successful payment
                return view('payment-success');
            }
        }
        
        return view('payment-failed');
    }
}
```

## Package Structure

```
laravel-kashier/
├── src/
│   ├── Http/
│   │   └── Controllers/
│   │       └── PaymentController.php
│   ├── Facades/
│   │   └── Kashier.php
│   ├── KashierService.php
│   └── KashierServiceProvider.php
├── config/
│   └── kashier.php
├── resources/
│   └── views/
│       └── payment/
│           ├── checkout.blade.php
│           ├── success.blade.php
│           └── error.blade.php
├── routes/
│   └── web.php
├── composer.json
└── README.md
```


## API Reference

### KashierService Methods

#### Payment Methods

##### `generateOrderHash($orderId, $amount, $currency, $customerReference = null)`
Generate hash for order verification.

##### `validateSignature($queryParams)`
Validate callback signature for security.

##### `getHppUrl($orderId, $amount, $currency, $callbackUrl, $allowedMethods = 'card,wallet,bank_installments')`
Generate Hosted Payment Page URL.

#### Refund Methods

##### `refund($orderId, $transactionId, $amount = null, $reason = null)`
Process a refund (full or partial).
- **Parameters:**
  - `$orderId` (string): The order ID
  - `$transactionId` (string): The transaction ID
  - `$amount` (float|null): Amount to refund (null for full refund)
  - `$reason` (string|null): Refund reason
- **Returns:** array - Refund response
- **Throws:** `KashierRefundException`, `KashierConnectionException`

##### `fullRefund($orderId, $transactionId, $reason = null)`
Process a full refund (convenience method).

##### `partialRefund($orderId, $transactionId, $amount, $reason = null)`
Process a partial refund (convenience method).

##### `getRefundStatus($orderId, $transactionId)`
Get the status of a refund.

#### Configuration Methods

##### `getConfig()`
Get current configuration (test/live).

##### `getMode()`
Get current mode (test/live).

##### `getMid()`
Get merchant ID.

##### `getBaseUrl()`
Get Kashier checkout base URL.

##### `getApiBaseUrl()`
Get Kashier API base URL.

### Available Events

- `WebhookReceived` - Fired when any webhook is received
- `PaymentSuccessful` - Fired when payment succeeds
- `PaymentFailed` - Fired when payment fails
- `RefundProcessed` - Fired when refund is processed

### Available Middleware

- `kashier.webhook` - Verifies webhook signature
- `kashier.callback` - Verifies payment callback signature

### Exceptions

- `KashierException` - Base exception class
- `KashierConnectionException` - API connection errors
- `KashierInvalidSignatureException` - Invalid signature errors
- `KashierConfigurationException` - Configuration errors
- `KashierRefundException` - Refund-specific errors

## Payment Flow

### iFrame Payment
1. User visits `/payment/checkout`
2. Clicks the Kashier payment button
3. Payment popup opens on the same page
4. After payment, redirects to `/payment/iframe/callback`
5. Signature is validated
6. User sees success or error page

### Hosted Payment Page (HPP)
1. User visits `/payment/checkout`
2. Clicks "Pay with Hosted Payment Page" link
3. Redirected to Kashier's hosted page
4. After payment, redirected back to `/payment/hpp/callback`
5. Signature is validated
6. User sees success or error page

## Security

### Signature Verification

All webhooks and callbacks are automatically verified using HMAC SHA256:

- **Webhook signatures** are verified via `VerifyWebhookSignature` middleware
- **Callback signatures** are verified via `VerifyCallbackSignature` middleware
- Invalid signatures throw `KashierInvalidSignatureException`

### Best Practices

- ✅ Never expose your API keys in frontend code
- ✅ Always use HTTPS in production
- ✅ Validate signatures before processing payments
- ✅ Log failed signature validations for security monitoring
- ✅ Use webhook endpoints for critical payment confirmations
- ✅ Store sensitive data encrypted in your database
- ✅ Implement rate limiting on webhook endpoints

### Exception Handling

```php
use Madarit\LaravelKashier\Exceptions\KashierInvalidSignatureException;
use Madarit\LaravelKashier\Exceptions\KashierConnectionException;

try {
    $result = Kashier::refund($orderId, $transactionId);
} catch (KashierInvalidSignatureException $e) {
    // Handle signature validation failure
    Log::error('Invalid signature: ' . $e->getMessage());
} catch (KashierConnectionException $e) {
    // Handle API connection error
    Log::error('Connection failed: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Error: ' . $e->getMessage());
}
```

## Going Live

### Pre-Launch Checklist

- [ ] Obtain live credentials from Kashier merchant dashboard
- [ ] Update `.env` file with live credentials
- [ ] Change `KASHIER_MODE` to `live`
- [ ] Configure webhook URL in Kashier dashboard: `https://yourdomain.com/kashier/webhook`
- [ ] Test webhook endpoint with Kashier's webhook testing tool
- [ ] Implement proper event listeners for payment notifications
- [ ] Enable logging: `KASHIER_LOGGING_ENABLED=true`
- [ ] Test refund flow in live mode (with small amounts)
- [ ] Ensure HTTPS is enabled on your domain
- [ ] Set up monitoring and alerts for failed payments
- [ ] Document your refund policy
- [ ] Test thoroughly before processing real payments

### Webhook Configuration

1. Login to your Kashier merchant dashboard
2. Navigate to Webhook Settings
3. Add your webhook URL: `https://yourdomain.com/kashier/webhook`
4. Select events to receive (payment success, failure, refund)
5. Save and test the webhook connection

## Support

For issues or questions:
- Kashier Documentation: https://developers.kashier.io/
- Kashier Support: Contact through merchant dashboard

## About the Developer

This package is developed and maintained by **Madar IT** - a software development company specializing in creating robust and scalable solutions for businesses.

### Madar IT

**Madar IT** provides professional software development services with expertise in:
- Laravel & PHP Development
- Payment Gateway Integration
- API Development & Integration
- Enterprise Application Development
- Custom Software Solutions

We are committed to delivering high-quality, secure, and well-documented packages that make developers' lives easier.

### Connect With Us

- **GitHub:** [github.com/madarit](https://github.com/madarit)
- **Website:** Contact us for custom development and integration services

For package-related issues, please open an issue on the GitHub repository. For custom development or integration services, feel free to reach out directly.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
