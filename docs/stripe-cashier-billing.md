# Stripe & Laravel Cashier Billing

This document explains how MetriFi uses Stripe and Laravel Cashier to manage subscriptions and enforce recommendation generation limits.

## Overview

MetriFi uses Laravel Cashier to integrate Stripe billing, with subscriptions scoped to the **Organization model** rather than the User model. The system includes a built-in free plan that allows organizations to generate up to 2 recommendations per year without providing payment information.

## Architecture

### Organization-Scoped Billing

Unlike typical Laravel Cashier implementations that scope billing to users, MetriFi scopes billing to organizations:

- The `Organization` model uses the `Billable` trait from Laravel Cashier
- Stripe customer columns are added to the `organizations` table (not `users`)
- Each organization has its own Stripe customer ID and subscription status

**Key columns on organizations table:**
- `stripe_id` - Stripe customer ID
- `pm_type` - Payment method type (e.g., "card")
- `pm_last_four` - Last 4 digits of payment method
- `trial_ends_at` - Trial period end date

### Subscription Plans

Plans are stored in the `subscription_plans` table and managed via the `Plan` model.

**Plan structure:**
```php
[
    'title' => 'Plan Name',
    'slug' => 'plan-slug',
    'price' => 2900, // Price in cents
    'interval' => 'month', // or 'year'
    'buyable' => true, // false for free plan
    'limits' => [
        'users' => 5,
        'rates' => 500,
    ],
    'stripe_price_id' => 'price_xxx', // Stripe Price ID
]
```

**Available plans:**
1. **Free Plan** - $0, not buyable, 1 user, 100 rates
2. **Basic Monthly** - $29/month, 5 users, 500 rates
3. **Basic Yearly** - $299/year, 5 users, 500 rates

### Accessing Organization's Plan

The `Organization` model has a `plan()` relationship that returns the active plan:

```php
public function plan()
{
    return $this->hasOneThrough(
        Plan::class, Subscription::class,
        'organization_id', 'stripe_price_id', 'id', 'stripe_price'
    )
        ->where('stripe_status', '=', 'active')
        ->withDefault(Plan::free()->toArray());
}
```

**Key behavior:**
- If organization has an active Stripe subscription, returns that plan
- If no active subscription exists, returns the free plan via `withDefault()`
- The free plan is retrieved using `Plan::free()` which finds the plan where `buyable = false`

## Recommendation Limits

### How Limits Work

Recommendation generation limits are enforced based on billing cycles:

**For paid subscriptions:**
- Billing cycle is determined by Stripe subscription period
- `started_at` = `current_period_start` from Stripe
- `renews_at` = `current_period_end` from Stripe
- Limits reset at the start of each billing period

**For free plan:**
- Billing cycle is based on organization creation anniversary
- `started_at` = Most recent anniversary of organization creation date
- `renews_at` = 12 months after `started_at`
- Limits reset annually on the organization's creation anniversary

### Counting Recommendations

Recommendations are counted using specific criteria to determine usage:

```php
$recommendationsUsed = $organization->recommendations()
    ->whereBetween('created_at', [$startedAt, $renewsAt])
    ->where('status', 'done')
    ->whereHas('user', function ($query) {
        $query->where('role', '!=', 'admin');
    })
    ->count();
```

**Important filters:**
1. **Date range** - Only counts recommendations within current billing cycle
2. **Status** - Only counts recommendations with `status = 'done'`
3. **User role** - Excludes recommendations created by admin users

This means:
- Draft or failed recommendations don't count toward limits
- Admin-generated recommendations don't count (for testing/support)
- Limits are per billing cycle, not lifetime

### Free Plan Specifics

The free plan allows **2 recommendations per year** without requiring payment information:

**Billing cycle calculation:**
```php
// Use organization creation date as starting point
$creationDate = $organization->created_at;

// Calculate years since creation
$yearsSinceCreation = $now->diffInYears($creationDate);

// Set start date to anniversary in current billing year
$startedAt = $creationDate->copy()->addYears($yearsSinceCreation);

// If we've passed this year's anniversary, use that as start
// Otherwise use last year's anniversary
if ($startedAt->gt($now)) {
    $startedAt = $creationDate->copy()->addYears($yearsSinceCreation - 1);
}

// Renewal is always 12 months after start
$renewsAt = $startedAt->copy()->addMonths(12);
```

**Example:**
- Organization created: January 15, 2024
- Current date: March 10, 2025
- Current cycle: January 15, 2025 - January 15, 2026
- Recommendations used in this cycle: Count from Jan 15, 2025 onwards

## Subscription Management

### Checking Subscription Status

The `OrganizationSubscriptionController` provides subscription details:

**Endpoint:** `GET /api/{organization-slug}/subscription`

**Response for paid subscription:**
```json
{
    "subscribed": true,
    "plan": { /* Plan details */ },
    "started_at": "2025-01-01T00:00:00Z",
    "renews_at": "2025-02-01T00:00:00Z",
    "recommendations_used": 5,
    "ends_at": null,
    "upcoming_plan": "Basic - Yearly",
    "upcoming_plan_start_at": "2025-02-01T00:00:00Z"
}
```

**Response for free plan:**
```json
{
    "subscribed": false,
    "plan": { /* Free plan details */ },
    "started_at": "2025-01-15T00:00:00Z",
    "renews_at": "2026-01-15T00:00:00Z",
    "recommendations_used": 1,
    "ends_at": null
}
```

### Subscription Schedules

The system supports Stripe subscription schedules for plan changes:

- When a subscription schedule exists, `upcoming_plan` shows the next plan
- `upcoming_plan_start_at` shows when the new plan takes effect
- This allows for end-of-cycle plan changes without immediate billing

## Models & Relationships

### Organization Model

**Location:** `app/Domain/Organizations/Organization.php`

**Key traits:**
- `Billable` - Laravel Cashier billing functionality
- `SoftDeletes` - Soft delete support
- `HasSlug` - URL-friendly slug generation

**Key relationships:**
```php
public function plan() // Current active plan
public function recommendations() // All recommendations
public function users() // Organization members
```

### Recommendation Model

**Location:** `app/Domain/Recommendations/Recommendation.php`

**Key traits:**
- `BelongsToOrganization` - Links to organization
- `BelongsToUser` - Links to creator

**Key attributes:**
- `status` - Current status (draft, queued, processing, done, failed)
- `title` - Recommendation title
- `metadata` - Additional data stored as JSON
- `runs` - Processing run history stored as JSON array

### Plan Model

**Location:** `app/Domain/Base/Subscriptions/Plans/Plan.php`

**Key methods:**
```php
Plan::free() // Returns the free plan (where buyable = false)
```

## Database Schema

### Organizations Table (Cashier columns)

```sql
stripe_id VARCHAR(255) NULL
pm_type VARCHAR(255) NULL
pm_last_four VARCHAR(4) NULL
trial_ends_at TIMESTAMP NULL
```

### Subscriptions Table (Cashier)

Standard Laravel Cashier subscriptions table with:
- `organization_id` - Foreign key to organizations
- `stripe_id` - Stripe subscription ID
- `stripe_status` - Subscription status
- `stripe_price` - Stripe price ID
- `quantity` - Subscription quantity
- `trial_ends_at` - Trial end date
- `ends_at` - Cancellation date

### Subscription Plans Table

```sql
id BIGINT PRIMARY KEY
title VARCHAR(255)
slug VARCHAR(255) UNIQUE
price INTEGER
interval VARCHAR(255) NULL
buyable BOOLEAN DEFAULT true
limits JSON NULL
stripe_price_id VARCHAR(255) NULL
```

## Implementation Notes

### No Frontend Enforcement

Currently, there is **no policy or middleware** that prevents recommendation creation when limits are exceeded. The system:

1. Tracks usage via the subscription controller
2. Displays usage to users in the frontend
3. Relies on frontend to prevent creation when limit reached

**To add backend enforcement, you would need to:**
1. Create a `RecommendationPolicy` with a `create()` method
2. Check `$organization->plan->limits['recommendations']` vs current usage
3. Register the policy in `AuthServiceProvider`
4. Add authorization check in `RecommendationController@store`

### Admin Exclusion

Recommendations created by admin users (`role = 'admin'`) are excluded from usage counts. This allows:
- Internal testing without affecting customer limits
- Support team to generate recommendations for troubleshooting
- Demo recommendations without consuming customer quota

### Status Filtering

Only recommendations with `status = 'done'` count toward limits. This means:
- Failed generation attempts don't count
- Draft recommendations don't count
- In-progress recommendations don't count
- Only successfully completed recommendations are billable

## Common Tasks

### Adding a New Plan

1. Add plan to Stripe dashboard
2. Copy the Stripe Price ID
3. Add plan to `SubscriptionPlansSeeder`:
```php
[
    'title' => 'Pro - Monthly',
    'price' => 9900,
    'interval' => 'month',
    'buyable' => true,
    'limits' => [
        'users' => 20,
        'rates' => 2000,
    ],
    'stripe_price_id' => 'price_xxx',
]
```
4. Run seeder: `php artisan db:seed --class=SubscriptionPlansSeeder`

### Checking Organization Usage

```php
$organization = Organization::find($id);
$subscription = $organization->subscription('default');

if ($subscription) {
    $stripeSubscription = $subscription->asStripeSubscription();
    $startedAt = Carbon::createFromTimeStamp($stripeSubscription->current_period_start);
    $renewsAt = Carbon::createFromTimeStamp($stripeSubscription->current_period_end);
} else {
    // Calculate free plan cycle dates
}

$used = $organization->recommendations()
    ->whereBetween('created_at', [$startedAt, $renewsAt])
    ->where('status', 'done')
    ->whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))
    ->count();
```

### Testing Subscription Flows

1. Use Stripe test mode with test cards
2. Create test organization
3. Subscribe via Stripe Checkout or Billing Portal
4. Verify `stripe_id` is set on organization
5. Check `subscriptions` table for active subscription
6. Verify `plan()` relationship returns correct plan
7. Generate recommendations and verify counting

## Related Files

- `app/Domain/Organizations/Organization.php` - Organization model with Billable trait
- `app/Domain/Recommendations/Recommendation.php` - Recommendation model
- `app/Domain/Base/Subscriptions/Plans/Plan.php` - Plan model
- `app/Http/Organizations/OrganizationSubscriptionController.php` - Subscription status endpoint
- `app/Http/Recommendations/RecommendationController.php` - Recommendation CRUD
- `database/migrations/2019_05_03_000001_create_customer_columns.php` - Cashier columns
- `database/migrations/2019_05_03_000002_create_subscriptions_table.php` - Subscriptions table
- `database/migrations/2023_02_01_082341_create_subscription_plans_table.php` - Plans table
- `database/seeders/SubscriptionPlansSeeder.php` - Plan seeding
