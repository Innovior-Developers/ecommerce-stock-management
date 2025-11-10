# Payment Gateway Implementation Plan - Documentation Overview

## Executive Summary

Complete payment integration strategy for ESM e-commerce platform supporting 3 payment gateways:

- **PayHere**: Sri Lankan customers (LKR currency)
- **PayPal**: International customers (sandbox testing)
- **Stripe**: Primary international gateway (USD/EUR/multi-currency)

---

## Phase 1: Planning & Architecture (Week 1)

### 1.1 Requirements Analysis

#### Payment Flow Requirements

- Customer selects payment method during checkout
- System determines appropriate gateway based on:
    - Customer location (IP geolocation)
    - Selected currency
    - Customer preference
- Real-time payment status tracking
- Automatic order status updates
- Email notifications for payment events

#### Security Requirements

- PCI DSS compliance (Stripe/PayPal handle card data)
- Webhook signature verification
- SSL/TLS encryption for all payment requests
- Token-based authentication for payment initiation
- Rate limiting on payment endpoints (3 attempts/minute)
- Fraud detection integration

#### Business Requirements

- Support for refunds and partial refunds
- Payment history and audit trail
- Multi-currency support (LKR, USD, EUR)
- Sandbox/test mode for all gateways
- Easy switch between sandbox and production

### 1.2 Database Schema Design

#### New Collections Required

**payments Collection:**

- Payment ID (primary key)
- Order reference
- User reference
- Amount and currency
- Payment method (payhere/paypal/stripe)
- Status (pending/processing/completed/failed/refunded)
- Gateway transaction ID
- Gateway response (full JSON)
- Timestamps (created, paid, refunded)
- Metadata (IP address, user agent)

**payment_methods Collection** (for saved payment methods):

- User reference
- Gateway name
- Payment method type (card/bank/paypal account)
- Last 4 digits (for cards)
- Is default flag
- Gateway customer ID
- Expiry date (for cards)

**payment_transactions Collection** (for audit trail):

- Payment reference
- Transaction type (authorize/capture/refund)
- Amount
- Status
- Gateway response
- Timestamps

### 1.3 Service Architecture

#### Service Layer Structure

- **PaymentGatewayInterface**: Common interface for all gateways
- **StripeService**: Stripe-specific implementation
- **PayPalService**: PayPal-specific implementation
- **PayHereService**: PayHere-specific implementation
- **PaymentService**: Orchestration layer that selects appropriate gateway

#### Key Methods Each Gateway Must Implement

- `createPayment()`: Initialize payment intent/session
- `capturePayment()`: Capture authorized payment
- `refundPayment()`: Process refund
- `verifyWebhook()`: Validate webhook signatures
- `getPaymentStatus()`: Check current payment status

---

## Phase 2: Backend Implementation (Week 2)

### 2.1 Environment Configuration

#### Environment Variables to Add

**Stripe Configuration:**

- Public key (`pk_test_` for sandbox)
- Secret key (`sk_test_` for sandbox)
- Webhook secret (`whsec_`)
- API version

**PayPal Configuration:**

- Mode (sandbox/live)
- Client ID (sandbox and production)
- Client secret (sandbox and production)
- Webhook ID
- Return URL
- Cancel URL

**PayHere Configuration:**

- Merchant ID (sandbox)
- Merchant secret
- Mode (sandbox/live)
- Return URL
- Cancel URL
- Notify URL (webhook)

**General Payment Settings:**

- Default currency
- Allowed currencies list
- Payment timeout duration
- Auto-capture setting (immediate or manual)

### 2.2 Models Implementation

#### Payment Model

- **Relationships**: `belongsTo` Order, `belongsTo` User
- **Scopes**: `pending()`, `completed()`, `failed()`, `byGateway()`
- **Accessors**: `isCompleted`, `isPending`, `canRefund`
- **Mutators**: `setAmountAttribute` (ensure 2 decimal places)

#### PaymentMethod Model

- **Relationships**: `belongsTo` User
- **Scopes**: `active()`, `default()`, `byGateway()`
- **Methods**: `setAsDefault()`, `remove()`

### 2.3 Service Layer Implementation

#### Stripe Service Features

- Payment Intent API (supports 3D Secure)
- Customer management (save cards)
- Webhook handling (payment_intent events)
- Automatic retry logic for failed payments
- Idempotency keys for duplicate prevention
- Metadata attachment (order ID, user ID)

#### PayPal Service Features

- Orders API v2
- Customer approval flow (redirect to PayPal)
- Webhook handling (PAYMENT.CAPTURE events)
- Reference transactions (saved PayPal accounts)
- Dispute handling

#### PayHere Service Features

- Payment page integration (redirect method)
- Hash generation for security
- Webhook handling (MD5 signature verification)
- Manual refund process (through merchant dashboard)
- Support for recurring payments

### 2.4 Controller Implementation

#### PaymentController Endpoints

**POST `/api/payment/initiate`**: Start payment process

- Validates order exists and belongs to user
- Creates payment record with "pending" status
- Calls appropriate gateway service
- Returns client secret (Stripe) or redirect URL (PayPal/PayHere)

**POST `/api/payment/confirm`**: Confirm payment after gateway redirect

- Validates payment ID
- Checks gateway for payment status
- Updates payment record
- Updates order status to "processing"
- Triggers confirmation email

**GET `/api/payment/status/{id}`**: Get payment status

- Returns current payment state
- Used for polling during payment process

#### PaymentWebhookController Endpoints

**POST `/api/webhooks/stripe`**: Handle Stripe events

- Verifies signature
- Processes: `payment_intent.succeeded`, `payment_intent.failed`
- Updates payment and order status

**POST `/api/webhooks/paypal`**: Handle PayPal events

- Verifies webhook signature
- Processes: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`
- Updates payment and order status

**POST `/api/webhooks/payhere`**: Handle PayHere notifications

- Verifies MD5 hash
- Processes status codes (2=success, others=failure)
- Updates payment and order status

### 2.5 Validation & Security

#### Payment Initiation Validation

- User must be authenticated (JWT)
- Order must exist and belong to user
- Order status must be "pending"
- Payment method must be valid
- Currency must match order currency or be convertible
- Amount must match order total

#### Webhook Security

- Signature verification for all webhooks
- Timestamp validation (reject old webhooks)
- Idempotency check (prevent duplicate processing)
- IP whitelist (optional, for extra security)

#### Rate Limiting

- Payment initiation: 3 requests/minute per user
- Payment confirmation: 10 requests/minute per user
- Webhook processing: 100 requests/minute per IP

---

## Phase 3: Frontend Integration (Week 3)

### 3.1 Payment Selection UI

#### Checkout Page Updates

- Add payment method selection section
- Display available methods based on:
    - User location (IP-based)
    - Order currency
    - Configured gateways
- Show payment method icons/logos
- Explain each payment method (processing time, fees if any)

#### Payment Method Cards

- **Stripe**: "Credit/Debit Card (Visa, Mastercard, Amex)"
- **PayPal**: "PayPal Account"
- **PayHere**: "Local Sri Lankan Banks (Credit/Debit Cards, Bank Transfer)"

### 3.2 Stripe Integration (Client-Side)

#### Components to Create

**StripePaymentForm**: Wraps Stripe Elements

- Uses `@stripe/stripe-js` and `@stripe/react-stripe-js`
- Implements Stripe Payment Element (all-in-one)
- Handles 3D Secure authentication automatically

#### Payment Flow

1. User clicks "Pay with Card"
2. Frontend calls `/api/payment/initiate` → receives `client_secret`
3. Frontend mounts Stripe Payment Element with client secret
4. User enters card details
5. Frontend calls `stripe.confirmPayment()` with client secret
6. Stripe redirects to return URL after completion
7. Frontend confirms payment via `/api/payment/confirm`

### 3.3 PayPal Integration (Client-Side)

#### Components to Create

**PayPalButton**: Uses PayPal JavaScript SDK

- Implements PayPal Smart Payment Buttons

#### Payment Flow

1. User clicks "Pay with PayPal"
2. Frontend calls `/api/payment/initiate` → receives `approval_url`
3. Frontend redirects user to PayPal approval URL
4. User logs in to PayPal and approves payment
5. PayPal redirects back to return URL
6. Frontend extracts transaction ID from URL
7. Frontend confirms payment via `/api/payment/confirm`

### 3.4 PayHere Integration (Client-Side)

#### Components to Create

**PayHereForm**: Generates HTML form with hidden fields

- Automatically submits form to PayHere checkout page

#### Payment Flow

1. User clicks "Pay with PayHere"
2. Frontend calls `/api/payment/initiate` → receives `payment_data` and `action_url`
3. Frontend creates hidden form with payment data
4. Form auto-submits to PayHere checkout page
5. User completes payment on PayHere page
6. PayHere redirects back to return URL
7. Frontend confirms payment via `/api/payment/confirm`

### 3.5 Payment Status Tracking

#### Real-Time Status Updates

- Polling mechanism: Check status every 3 seconds during payment
- Display loading spinner with status message
- Show success/failure message with appropriate icons
- Redirect to order confirmation page on success
- Show retry option on failure

#### Payment Status Page

- Display current payment status (pending/processing/completed)
- Show transaction ID
- Show payment method used
- Show amount and currency
- Show timestamps (initiated, completed)
- Option to download receipt/invoice

---

## Phase 4: Testing Strategy (Week 3)

### 4.1 Test Data & Credentials

#### Stripe Test Cards

- **Success**: 4242 4242 4242 4242 (any future expiry, any CVC)
- **Decline**: 4000 0000 0000 0002
- **Requires authentication**: 4000 0027 6000 3184
- **Insufficient funds**: 4000 0000 0000 9995

#### PayPal Sandbox Accounts

- Create personal sandbox account (buyer)
- Create business sandbox account (seller/merchant)
- Test all flows: login → approve → payment completion

#### PayHere Sandbox Cards

- **Visa**: 4916 2177 7127 3434 (Exp: 12/25, CVV: 123)
- **Mastercard**: 5313 5816 7637 2833 (Exp: 12/25, CVV: 123)
- Use any cardholder name

### 4.2 Test Scenarios

#### Successful Payment Tests

- Complete payment with each gateway
- Verify payment record created
- Verify order status updated to "processing"
- Verify confirmation email sent
- Verify webhook received and processed

#### Failed Payment Tests

- Test declined card (Stripe)
- Test cancelled PayPal payment
- Test failed PayHere payment
- Verify payment record status = "failed"
- Verify order status remains "pending"
- Verify failure email sent

#### Edge Case Tests

- Duplicate payment attempts
- Expired payment intent
- Webhook received before redirect return
- Network interruption during payment
- Browser closed during payment
- Multiple webhooks for same payment

### 4.3 Integration Testing

#### End-to-End Test Flow

1. Create test user account
2. Add products to cart
3. Proceed to checkout
4. Fill shipping address
5. Select payment method
6. Complete payment
7. Verify order confirmation page
8. Check database: payment status = "completed"
9. Check database: order status = "processing"
10. Verify email received

#### Webhook Testing

- Use Stripe CLI for webhook forwarding
- Use PayPal webhook simulator
- Use PayHere test notifications
- Verify idempotency (send duplicate webhooks)
- Verify signature validation rejects invalid signatures

---

## Phase 5: Security Hardening (Week 4)

### 5.1 PCI DSS Compliance

#### Stripe & PayPal (PCI Compliant)

- Never store card numbers on server
- Never log full card details
- Use tokenization (Stripe Payment Methods)
- Use hosted payment pages (PayPal/PayHere redirect)

#### Server-Side Security

- All payment endpoints require JWT authentication
- CSRF protection disabled for webhooks (use signature verification instead)
- SQL injection prevention (already handled by QuerySanitizer)
- XSS prevention (already handled by SanitizeInput)

### 5.2 Fraud Prevention

#### Basic Fraud Checks

- Verify order total matches payment amount
- Check payment currency matches order currency
- Verify user owns the order
- IP address mismatch alerts (user IP vs payment IP)
- Velocity checks (max 3 payment attempts per 10 minutes)

#### Advanced Fraud Prevention (Optional)

- Stripe Radar (automatic fraud detection)
- PayPal Seller Protection
- Manual review queue for suspicious orders
- Blacklist by IP/email/card fingerprint

### 5.3 Error Handling

#### Payment Errors

- **Insufficient funds** → Show user-friendly message
- **Card declined** → Suggest alternative payment method
- **Network timeout** → Retry mechanism
- **Gateway downtime** → Show maintenance message

#### Webhook Errors

- **Signature verification failed** → Log and reject
- **Payment not found** → Log warning (might be timing issue)
- **Database error** → Retry webhook processing
- **Email sending failed** → Queue for retry

### 5.4 Audit Logging

#### Events to Log

- Payment initiated (user, order, gateway, amount)
- Payment completed (transaction ID, timestamp)
- Payment failed (reason, error code)
- Refund requested (admin user, reason)
- Refund completed (refund ID, amount)
- Webhook received (gateway, event type, status)
- Suspicious activity (multiple failed attempts, mismatched amounts)

#### Log Storage

- Store in `audit_logs` collection
- Include: timestamp, user ID, IP address, user agent
- Retain logs for 7 years (financial compliance)

---

## Phase 6: Monitoring & Maintenance (Ongoing)

### 6.1 Performance Monitoring

#### Metrics to Track

- Payment success rate (per gateway)
- Average payment processing time
- Webhook latency (time from payment to webhook)
- Failed payment reasons (declined/timeout/error)
- Refund rate and reasons

#### Alerting Thresholds

- Payment success rate < 95% → Alert
- Webhook latency > 30 seconds → Warning
- Failed payments > 10% → Critical alert
- Gateway downtime → Immediate notification

### 6.2 Financial Reconciliation

#### Daily Reconciliation Tasks

- Compare Stripe dashboard balance with database
- Compare PayPal transaction report with database
- Verify all webhooks received (no missed payments)
- Check for stuck "pending" payments (> 24 hours)
- Generate daily payment summary report

#### Monthly Tasks

- Generate payment gateway fee reports
- Reconcile refunds with original payments
- Check for unclaimed payments
- Review fraud alerts and chargebacks

### 6.3 Gateway Updates

#### Stripe

- Monitor API version updates (quarterly)
- Test new features in sandbox
- Update SDK libraries regularly
- Review security advisories

#### PayPal

- Monitor Orders API changes
- Update SDK when PayPal releases updates
- Test changes in sandbox before production

#### PayHere

- Monitor for new payment methods
- Check for API updates (manual integration)
- Test after any merchant dashboard changes

---

## Phase 7: Documentation & Training (Week 4)

### 7.1 Developer Documentation

#### API Endpoints

- Full endpoint documentation with request/response examples
- Error codes and meanings
- Rate limiting details
- Authentication requirements

#### Webhook Documentation

- Signature verification examples
- Event types and payloads
- Retry logic explanation
- Idempotency handling

#### Testing Guide

- How to use sandbox environments
- Test card numbers for each gateway
- How to trigger webhooks manually
- How to test refunds

### 7.2 Admin User Guide

#### Payment Management

- How to view payment history
- How to search for payments
- How to process refunds
- How to handle disputes/chargebacks

#### Troubleshooting

- Common payment errors and solutions
- How to check webhook delivery
- How to manually verify payment status
- When to contact gateway support

### 7.3 Customer Support Guide

#### Customer FAQs

- "Why was my payment declined?"
- "How long does a refund take?"
- "Which payment methods do you accept?"
- "Is my payment information secure?"

#### Support Workflows

- How to help customer with failed payment
- How to verify payment was received
- How to process refund request
- How to escalate to technical team

---

## Phase 8: Deployment Checklist (Week 4)

### 8.1 Pre-Deployment

#### Code Review

- All payment endpoints reviewed
- Security checks passed
- Error handling verified
- Logging implemented

#### Testing Completed

- All test scenarios passed
- Load testing completed (100 concurrent payments)
- Webhook delivery tested
- Email notifications working

#### Configuration

- Production API keys added to environment
- Webhook URLs configured in gateway dashboards
- Rate limiting configured
- SSL certificates valid

### 8.2 Deployment Steps

#### Backend Deployment

1. Run database migrations (create new collections)
2. Deploy new code to staging environment
3. Test all payment flows in staging
4. Deploy to production
5. Verify webhooks receiving events
6. Monitor logs for 24 hours

#### Frontend Deployment

1. Update API endpoints to production URLs
2. Build production frontend bundle
3. Deploy to CDN/hosting
4. Test from multiple devices/browsers
5. Verify payment flows work end-to-end

### 8.3 Post-Deployment

#### Immediate Checks (First 24 Hours)

- Process 5-10 test payments per gateway
- Verify webhooks received for all test payments
- Check email notifications sent
- Monitor error logs
- Verify payment reconciliation matches

#### First Week Monitoring

- Daily payment success rate review
- Check for unexpected errors
- Review customer support tickets
- Monitor gateway status pages
- Check financial reconciliation

---

## Success Criteria

### Technical Success

- All 3 gateways integrated and functional
- 99%+ payment success rate (excluding customer declines)
- Webhooks processed within 5 seconds
- Zero security vulnerabilities
- Zero payment data leaks

### Business Success

- Customers can complete checkout in < 2 minutes
- Support tickets related to payments < 5% of orders
- Accurate financial reconciliation daily
- Easy refund process (< 5 minutes per refund)
- Multi-currency support working correctly

---

## Risk Mitigation

### High-Risk Areas

- **Webhook Failures**: Implement retry mechanism and manual reconciliation backup
- **Gateway Downtime**: Display fallback payment methods when primary fails
- **Currency Conversion**: Use real-time exchange rates, not hardcoded
- **Refund Disputes**: Document all refunds with screenshots/timestamps
- **Data Breach**: Encrypt all payment logs, never store card details

### Contingency Plans

- If Stripe down → Route to PayPal
- If all gateways down → Enable "Pay Later" option with manual processing
- If webhook system fails → Implement polling mechanism as backup
- If fraud spike detected → Enable manual review queue temporarily

