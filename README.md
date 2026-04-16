# Flexi WhatsApp Automation

**WhatsApp Web-based messaging automation for WordPress & WooCommerce.**

A production-grade WordPress plugin that brings full WhatsApp messaging automation to your site — multi-account management, campaigns, scheduling, OTP verification, chat widget, and event-driven workflows. Works standalone or as a premium add-on for [Flexi Revive Cart](https://github.com/darpan-startandgrow/flexi-revive-cart).

> **Architecture:** This plugin uses WhatsApp Web session-based automation (NOT WhatsApp Business API). You connect your own WhatsApp number via QR code and send messages through a session engine endpoint.

---

## Table of Contents

1. [Plugin Overview](#plugin-overview)
2. [Features](#features)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [Onboarding Guide](#onboarding-guide)
6. [Connecting WhatsApp](#connecting-whatsapp)
7. [Sending Messages](#sending-messages)
8. [Campaigns](#campaigns)
9. [Automation Setup](#automation-setup)
10. [OTP Verification & Login](#otp-verification--login)
11. [Chat Widget](#chat-widget)
12. [Template Placeholders](#template-placeholders)
13. [Shortcodes](#shortcodes)
14. [Webhook System](#webhook-system)
15. [Logs & Troubleshooting](#logs--troubleshooting)
16. [Developer Hooks](#developer-hooks)
17. [Third-Party Integration](#third-party-integration)
18. [Known Limitations](#known-limitations)
19. [Changelog](#changelog)

---

## Plugin Overview

**Flexi WhatsApp Automation** replaces expensive WhatsApp Business API services with a free, self-hosted WhatsApp Web session approach — similar to how [wawp.net](https://wawp.net) works.

### How It Works

1. You provide an external WhatsApp session engine endpoint (API base URL)
2. The plugin connects your WhatsApp number via QR code scanning
3. Messages are sent through the connected session — no Business API needed
4. Supports multiple WhatsApp accounts (instances) with isolated sessions

### Use Cases

- **E-commerce:** Order confirmations, shipping updates, abandoned cart recovery
- **Marketing:** Bulk WhatsApp campaigns with segmentation
- **Support:** Multi-agent chat widget for customer service
- **Authentication:** OTP-based login and phone verification via WhatsApp
- **Notifications:** Admin alerts, user registration messages, scheduled reminders

---

## Features

### Core Messaging
- ✅ **Full message types:** text, image, video, audio, document, contact, poll, link preview
- ✅ **Multi-account support:** Connect and manage multiple WhatsApp instances
- ✅ **Session management:** QR code connection, status tracking, auto-reconnection
- ✅ **Message logging:** Every sent/received message is tracked in the database

### Campaign Engine
- ✅ **Bulk campaigns:** Send messages to hundreds of contacts at once
- ✅ **Rate limiting:** Configurable delays between messages to avoid bans
- ✅ **Scheduling:** Set campaign start times and dates
- ✅ **Progress tracking:** Real-time campaign progress monitoring
- ✅ **Pause/Resume:** Control active campaigns

### Automation Workflows
- ✅ **WooCommerce triggers:** New order, status change, payment complete, checkout
- ✅ **WordPress triggers:** User registration, user login
- ✅ **Flexi Revive Cart triggers:** Cart abandoned, cart recovered, reminders
- ✅ **Custom triggers:** Any plugin can fire `fwa_trigger_automation`
- ✅ **Condition evaluation:** Configurable rules with template placeholders

### Scheduling
- ✅ **One-time messages:** Schedule a message for a future date
- ✅ **Recurring messages:** Hourly, daily, weekly, monthly recurrence
- ✅ **Queue processing:** WP-Cron based with duplicate prevention

### Contact Management
- ✅ **Contact CRUD:** Create, edit, delete, search contacts
- ✅ **CSV import/export:** Bulk contact management
- ✅ **WooCommerce sync:** Import customers as contacts
- ✅ **Tagging system:** Organize contacts with tags
- ✅ **Subscription management:** Track opt-in/opt-out status

### OTP Verification
- ✅ **Phone verification via WhatsApp** (not SMS)
- ✅ **OTP login shortcode** `[fwa_otp_login]`
- ✅ **Phone verification shortcode** `[fwa_otp_verify]`
- ✅ **Verification bar shortcode** `[fwa_phone_verify_bar]`
- ✅ **Rate limiting:** 5 requests per phone per hour
- ✅ **Attempt limits:** 5 incorrect attempts per OTP
- ✅ **5-minute expiry** with countdown timer
- ✅ **Admin onboarding OTP flow**

### Chat Widget
- ✅ **Floating WhatsApp button** with customizable position
- ✅ **Multi-agent support:** Route chats to different team members
- ✅ **Welcome message** and customizable header
- ✅ **Display rules:** Page include/exclude, mobile/desktop toggle
- ✅ **Analytics integration:** Google Analytics event tracking
- ✅ **Shortcodes:** `[fwa_chat_widget]` and `[fwa_chat_button]`

### Template Engine
- ✅ **Dynamic placeholders:** 40+ variables for orders, users, and site data
- ✅ **Cheat-sheet:** Built-in reference of all available placeholders
- ✅ **Extensible:** Add custom placeholders via filters

### Logging & Monitoring
- ✅ **5 log levels:** debug, info, warning, error, critical
- ✅ **Database + file logging** with configurable minimum level
- ✅ **Log viewer** with filters and pagination
- ✅ **CSV export** for log data
- ✅ **Auto-cleanup** of old logs

### Webhook System
- ✅ **REST API endpoints** for incoming messages, delivery status, session updates
- ✅ **Secret validation** for security
- ✅ **Extensible** via WordPress hooks

### Dashboard
- ✅ **Stats overview:** Connected accounts, messages today, active sessions
- ✅ **Quick-send form** directly from the dashboard
- ✅ **Instance status list** with real-time indicators
- ✅ **Recent activity log**
- ✅ **WordPress Dashboard widget**

### Admin UI
- ✅ **9 dedicated admin pages:** Dashboard, Instances, Messages, Campaigns, Contacts, Automation, Schedules, Logs, Settings
- ✅ **5-step onboarding wizard** with phone OTP verification
- ✅ **Tabbed settings** (General, API, Messaging, Automation, Advanced)
- ✅ **Deactivation data-cleanup prompt**
- ✅ **HPOS compatible** (WooCommerce High-Performance Order Storage)

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| External WhatsApp API endpoint | Required |

### Optional
- **WooCommerce** — Enables order-related automations and customer sync
- **Flexi Revive Cart** — Enables abandoned cart WhatsApp notifications

### WhatsApp Session Engine

This plugin requires an external WhatsApp Web session engine (e.g., a self-hosted WhatsApp Web API service). The engine handles:
- QR code generation
- Session persistence
- Message delivery via WhatsApp Web protocol

You configure the engine's base URL and API token in **Settings → API Configuration**.

---

## Installation

### Via WordPress Admin

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload and activate

### Manual

1. Upload the `flexi-whatsapp-automation` folder to `/wp-content/plugins/`
2. Activate via the **Plugins** menu
3. The setup wizard will launch automatically

---

## Onboarding Guide

After activation, the 5-step onboarding wizard guides you through setup:

### Step 1: Phone Verification (Primary)
- Enter your WhatsApp phone number
- Receive a 6-digit code **on WhatsApp** (not SMS)
- This confirms phone ownership

> **Skip option:** You can skip phone verification and connect directly via QR code.

### Step 2: Enter OTP
- Enter the 6-digit code from WhatsApp
- Code expires in 5 minutes
- Resend available after 30 seconds

### Step 3: Connect WhatsApp
- Enter your API Base URL and API Key
- Create an instance (name your WhatsApp account)
- Scan the QR code with your WhatsApp mobile app

### Step 4: Configure
- Enable/disable features (Automation, Logging, Campaigns)
- Set up quick-start automation rules:
  - WooCommerce order notifications
  - Welcome messages for new users

### Step 5: Finish
- Review your configuration
- Send a test message
- Go to the dashboard

---

## Connecting WhatsApp

### Adding a New Instance

1. Go to **WhatsApp Auto → Instances**
2. Click **Add Instance**
3. Enter a name for the account
4. The QR code will appear — scan it with WhatsApp on your phone:
   - Open WhatsApp → **Linked Devices** → **Link a Device**
   - Scan the QR code

### Instance States

| Status | Description |
|---|---|
| `connected` | Active and ready to send messages |
| `disconnected` | Session ended — needs reconnection |
| `qr_required` | QR code needs to be scanned |
| `initializing` | Session is starting up |
| `banned` | Account was banned by WhatsApp |

### Multi-Account

- Connect multiple WhatsApp numbers
- Set one as the **active instance** for default message sending
- Each instance is isolated with its own session
- Health checks run every 5 minutes

---

## Sending Messages

### Quick Send (Dashboard)

1. Go to **WhatsApp Auto → Dashboard**
2. Use the Quick Send form
3. Enter phone number and message
4. Click Send

### Via API

```php
$sender = new FWA_Message_Sender();

// Text message
$sender->send_text( '+1234567890', 'Hello World!' );

// Media message
$sender->send_media( '+1234567890', 'image', 'https://example.com/photo.jpg', 'Check this out!' );

// Poll
$sender->send_poll( '+1234567890', 'Favorite color?', array( 'Red', 'Blue', 'Green' ) );

// Contact card
$sender->send_contact( '+1234567890', array( 'name' => 'John', 'phone' => '+1111111111' ) );

// Link preview
$sender->send_link_preview( '+1234567890', 'https://example.com', 'Visit our site' );
```

### Supported Message Types

| Type | Description |
|---|---|
| `text` | Plain text message |
| `image` | Image with optional caption |
| `video` | Video with optional caption |
| `audio` | Audio file |
| `document` | PDF, DOC, or other documents |
| `contact` | vCard contact sharing |
| `poll` | Interactive poll |
| `link_preview` | URL with rich preview |

---

## Campaigns

### Creating a Campaign

1. Go to **WhatsApp Auto → Campaigns**
2. Click **Create Campaign**
3. Configure:
   - **Name:** Campaign identifier
   - **Instance:** Which WhatsApp account to use
   - **Message type:** Text, image, etc.
   - **Content:** Message body with placeholders
   - **Recipients:** Contact filter criteria
   - **Schedule:** When to start (optional)
4. Click **Create**

### Managing Campaigns

- **Start:** Begin sending messages
- **Pause:** Temporarily stop a running campaign
- **Resume:** Continue a paused campaign
- **Delete:** Remove a campaign

### Rate Limiting

Campaigns use configurable rate limiting (default: 3 seconds between messages) to avoid triggering WhatsApp's spam detection.

---

## Automation Setup

### WooCommerce Automations

1. Go to **WhatsApp Auto → Automation**
2. Add a rule:
   - **Event:** `new_order`, `order_status_changed`, `payment_complete`, `checkout_complete`
   - **Template:** Use placeholders like `{customer_name}`, `{order_id}`, `{order_total}`
   - **Phone field:** `billing_phone`

#### Example: Order Confirmation

```
Event: new_order
Template: Hi {customer_name}! Your order #{order_id} for {order_total} has been placed. We'll update you when it ships!
```

### WordPress Automations

- **user_register:** Welcome message on signup
- **wp_login:** Login notification

### Custom Triggers

Any plugin can trigger WhatsApp automation:

```php
do_action( 'fwa_trigger_automation', 'my_custom_event', array(
    'phone'         => '+1234567890',
    'customer_name' => 'John Doe',
    'custom_field'  => 'value',
));
```

---

## OTP Verification & Login

### Admin Onboarding OTP

The onboarding wizard uses OTP as the primary verification method:
- System bot sends OTP via WhatsApp
- 5-minute expiry, 5 attempt limit
- Rate limited: 5 requests per phone per hour

### Frontend OTP Login

Use the `[fwa_otp_login]` shortcode on any page:

```
[fwa_otp_login title="Login with WhatsApp" redirect="/my-account"]
```

**Flow:**
1. User enters phone number
2. Receives OTP on WhatsApp
3. Enters code to log in
4. Account created automatically if new user

### Phone Verification

```
[fwa_otp_verify title="Verify Your Phone"]
```

### Verification Bar

```
[fwa_phone_verify_bar message="Verify your phone number to unlock all features."]
```

---

## Chat Widget

### Enabling the Widget

1. Go to **WhatsApp Auto → Settings**
2. Configure the chat widget:
   - Enable/disable
   - Position (bottom-right, bottom-left)
   - Button text and colors
   - Welcome message
   - Add agents with phone numbers and roles

### Shortcodes

**Inline widget:**
```
[fwa_chat_widget phone="+1234567890" name="Sales Team" role="Support" message="Hi, I need help!"]
```

**Simple button:**
```
[fwa_chat_button phone="+1234567890" text="Chat with us" message="Hello!"]
```

---

## Template Placeholders

### Site Variables (Always Available)

| Placeholder | Description |
|---|---|
| `{site_name}` | Site name |
| `{site_url}` | Site URL |
| `{store_name}` | Store name |
| `{admin_email}` | Admin email |
| `{date}` | Current date |
| `{time}` | Current time |
| `{year}` | Current year |

### User Variables

| Placeholder | Description |
|---|---|
| `{user_id}` | WordPress user ID |
| `{username}` | Username |
| `{user_email}` | Email address |
| `{display_name}` | Display name |
| `{user_first_name}` | First name |
| `{user_last_name}` | Last name |
| `{user_role}` | User role |

### WooCommerce Order Variables

| Placeholder | Description |
|---|---|
| `{order_id}` | Order ID |
| `{order_number}` | Order number |
| `{order_total}` | Formatted total |
| `{order_status}` | Status label |
| `{order_date}` | Order date |
| `{order_items}` | Items list |
| `{customer_name}` | Full name |
| `{customer_phone}` | Phone number |
| `{customer_email}` | Email |
| `{payment_method}` | Payment method |
| `{shipping_method}` | Shipping method |
| `{billing_address}` | Full billing address |
| `{shipping_address}` | Full shipping address |
| `{currency}` | Currency code |
| `{subtotal}` | Subtotal |
| `{discount}` | Discount |
| `{shipping_total}` | Shipping cost |
| `{tax_total}` | Tax amount |

---

## Shortcodes

| Shortcode | Description |
|---|---|
| `[fwa_otp_login]` | OTP-based login/signup form |
| `[fwa_otp_verify]` | Standalone phone verification |
| `[fwa_phone_verify_bar]` | Compact verification bar |
| `[fwa_chat_widget]` | Inline chat widget |
| `[fwa_chat_button]` | Simple WhatsApp button |

---

## Webhook System

The plugin exposes REST API endpoints for receiving webhook events from your WhatsApp session engine:

### Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/wp-json/fwa/v1/webhook` | POST | General webhook receiver |
| `/wp-json/fwa/v1/webhook/message` | POST | Incoming messages |
| `/wp-json/fwa/v1/webhook/status` | POST | Delivery status updates |
| `/wp-json/fwa/v1/webhook/session` | POST | Session status changes |
| `/wp-json/fwa/v1/webhook/verify` | GET | Webhook URL verification |

### Security

Webhooks are validated using a shared secret configured in **Settings → API → Webhook Secret**.

---

## Logs & Troubleshooting

### Viewing Logs

Go to **WhatsApp Auto → Logs** to see activity:
- Filter by level (debug, info, warning, error, critical)
- Filter by source module
- Filter by date range
- Export to CSV

### Common Issues

| Issue | Solution |
|---|---|
| QR code not appearing | Check API Base URL and API Key in Settings |
| Messages not sending | Verify instance status is "connected" |
| OTP not received | Ensure bot session is connected and phone has WhatsApp |
| Campaign stuck | Check rate limit settings; try pausing and resuming |
| Session disconnects | Health checks auto-detect this; reconnect via Instances page |

### Debug Mode

Enable debug logging in **Settings → Advanced → Debug Mode** to capture detailed logs for troubleshooting.

---

## Developer Hooks

The plugin provides 60+ actions and filters. See `hooks.txt` for the complete reference.

### Key Actions

```php
// After plugin loads
add_action( 'fwa_loaded', 'my_init_function' );

// After a message is sent
add_action( 'fwa_message_sent', function( $message_id, $args ) {
    // Track sent messages
}, 10, 2 );

// Custom automation trigger
do_action( 'fwa_trigger_automation', 'my_event', $data );

// After OTP login
add_action( 'fwa_otp_login_success', function( $user, $phone ) {
    // Post-login logic
}, 10, 2 );
```

### Key Filters

```php
// Modify message before sending
add_filter( 'fwa_before_send_message', function( $args ) {
    $args['content'] .= "\n\nSent from MyPlugin";
    return $args;
});

// Add custom template variables
add_filter( 'fwa_template_variables', function( $vars, $data ) {
    $vars['custom_field'] = 'custom_value';
    return $vars;
}, 10, 2 );

// Control chat widget display
add_filter( 'fwa_chat_widget_display', function( $display, $settings ) {
    return is_front_page() ? true : $display;
}, 10, 2 );

// Customize OTP login redirect
add_filter( 'fwa_otp_login_redirect', function( $url, $user ) {
    return home_url( '/dashboard/' );
}, 10, 2 );
```

---

## Third-Party Integration

### Standalone Mode

This plugin works completely independently. No other plugins are required.

### Flexi Revive Cart Integration

When [Flexi Revive Cart](https://github.com/darpan-startandgrow/flexi-revive-cart) is active:
- Abandoned cart events trigger WhatsApp notifications automatically
- Cart recovery events send confirmation messages
- Reminder schedules integrate with the scheduling engine

### WooCommerce Integration

When WooCommerce is active:
- Order lifecycle events (new, status change, payment, checkout) trigger automations
- Customer sync imports WooCommerce customers as contacts
- Checkout phone verification via OTP shortcodes
- 25+ WooCommerce-specific template placeholders

### Custom Plugin Integration

Any plugin can integrate using:

```php
// Trigger an automation
do_action( 'fwa_trigger_automation', 'plugin_event', array(
    'phone' => $recipient_phone,
    'data'  => $your_data,
));

// Send a direct message
$sender = new FWA_Message_Sender();
$sender->send_text( $phone, $message );

// Add automation events
add_filter( 'fwa_automation_events', function( $events ) {
    $events['my_plugin_event'] = 'My Plugin Event';
    return $events;
});
```

---

## Known Limitations

1. **Session stability:** WhatsApp Web sessions can disconnect unexpectedly. Health checks run every 5 minutes, but messages during downtime will fail.

2. **Rate limits:** WhatsApp may temporarily ban accounts that send too many messages too quickly. Use the built-in rate limiting.

3. **No group messaging:** Currently focused on individual (1:1) messaging. Group support is planned.

4. **External engine required:** You need a WhatsApp Web session engine running separately.

5. **Phone verification requires active session:** OTP sending requires at least one connected WhatsApp instance (the bot account).

6. **Not official API:** This uses WhatsApp Web protocol, which may change without notice. Use at your own risk for production messaging.

---

## Changelog

### 1.1.0
- **NEW:** Phone number OTP verification as primary onboarding method
- **NEW:** Frontend OTP login shortcode `[fwa_otp_login]`
- **NEW:** Phone verification shortcode `[fwa_otp_verify]`
- **NEW:** Verification bar shortcode `[fwa_phone_verify_bar]`
- **NEW:** WhatsApp Chat Widget with multi-agent support
- **NEW:** Chat widget shortcodes `[fwa_chat_widget]`, `[fwa_chat_button]`
- **NEW:** Message template engine with 40+ placeholders
- **NEW:** Placeholder cheat-sheet API for admin UI
- **NEW:** Google Analytics integration for chat widget
- **NEW:** Display rules (page include/exclude, device targeting)
- **IMPROVED:** Onboarding wizard with 5-step OTP flow
- **IMPROVED:** Security — SHA-256 hashed OTP storage, rate limiting

### 1.0.0
- Initial release
- Multi-instance WhatsApp account management
- Full messaging support (text, image, video, audio, document, contact, poll, link preview)
- Campaign engine with bulk messaging and scheduling
- Automation engine with WooCommerce, WordPress, and Flexi Revive Cart hooks
- Scheduled messages (one-time and recurring)
- Contact management with CSV import/export and WooCommerce sync
- Dashboard widget with quick-send and status overview
- Central logging system with database and file fallback
- REST API webhook endpoints
- Deactivation data-cleanup prompt

---

## License

GPL-2.0+ — See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

[Darpan Sarmah](https://github.com/darpan-startandgrow)
