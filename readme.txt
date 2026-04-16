=== Flexi WhatsApp Automation ===
Contributors: darpansarmah
Tags: whatsapp, automation, woocommerce, messaging, campaigns
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp Web-based messaging automation for WordPress & WooCommerce. Multi-account management, campaigns, scheduling, and logging.

== Description ==

**Flexi WhatsApp Automation** is a production-grade WordPress plugin that brings WhatsApp messaging automation to your WordPress site. It works **standalone** or enhances **Flexi Revive Cart** when present.

= Core Features =

* **Multi-Account Support** – Connect and manage multiple WhatsApp accounts (instances) with isolated messaging per account.
* **Session Management** – Connect via QR code or pairing code with automatic reconnection and real-time status tracking.
* **Full Message Types** – Send text, images, videos, audio, documents, contacts, polls, and link previews.
* **Campaign Engine** – Bulk messaging with contact selection, scheduling, delivery tracking, and rate limiting.
* **Automated Workflows** – Hook into WooCommerce events (new order, status changes, payment) and WordPress events (user registration, login) to trigger messages automatically.
* **OTP Verification** – Phone number verification via WhatsApp OTP. Login, signup, and checkout verification shortcodes.
* **Chat Widget** – Floating WhatsApp chat button with multi-agent support, display rules, and analytics.
* **Template Engine** – 40+ dynamic placeholders for orders, users, and site data with a built-in cheat-sheet.
* **Scheduled Messages** – One-time and recurring scheduled messages via WP Cron with retry logic.
* **Contact Management** – Import/export contacts (CSV), tagging, subscription management, and WooCommerce customer sync.
* **Dashboard Widget** – Quick-send form, instance status overview, and recent activity right on the WordPress dashboard.
* **Logging System** – Full activity logging with database storage, optional file fallback, log viewer with filters, and CSV export.
* **Webhook System** – REST API endpoints for receiving incoming messages, delivery statuses, and session updates.
* **Flexi Revive Cart Integration** – When Flexi Revive Cart is active, automatically hooks into abandoned cart and cart recovery events.

= For Developers =

The plugin is designed to be extensible. See `hooks.txt` in the plugin directory for a complete list of all actions and filters available for third-party integration.

**Custom Trigger API:**

`do_action( 'fwa_trigger_automation', 'my_custom_event', $data );`

Any plugin can fire this action to trigger WhatsApp automation rules.

= Requirements =

* WordPress 6.0+
* PHP 7.4+
* An external WhatsApp API service endpoint (configured in Settings → API Configuration)

= Optional =

* WooCommerce (for order-related automations)
* Flexi Revive Cart (for abandoned cart WhatsApp notifications)

== Installation ==

1. Upload the `flexi-whatsapp-automation` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WhatsApp Auto → Settings** to configure your API base URL and access tokens.
4. Go to **WhatsApp Auto → Instances** to add and connect your WhatsApp accounts.
5. Set up automation rules under **WhatsApp Auto → Automation**.

== Frequently Asked Questions ==

= Does this plugin require Flexi Revive Cart? =

No. Flexi WhatsApp Automation works completely standalone. When Flexi Revive Cart is also active, the plugin automatically enables abandoned cart and cart recovery WhatsApp notifications.

= Does this use the official WhatsApp Business API? =

No. This plugin uses a session-based system via an external WhatsApp Web API service. You need to provide your own API endpoint.

= Can I connect multiple WhatsApp accounts? =

Yes. The plugin supports multiple instances (accounts). Each instance has its own credentials, status tracking, and isolated message queue.

= How does the deactivation data cleanup work? =

When you deactivate the plugin, a prompt asks whether you want to **Delete Data** or **Keep Data**. If you choose Delete Data, all database tables, options, and logs are removed. If you choose Keep Data, everything is preserved for when you reactivate.

= Can other plugins trigger WhatsApp messages? =

Yes. Use the `fwa_trigger_automation` action or any of the documented hooks. See `hooks.txt` for details.

== Screenshots ==

1. Dashboard – Overview of connected accounts, messages, and activity.
2. Instances – Multi-account management with QR code connection.
3. Campaigns – Bulk message campaign creation and tracking.
4. Automation – Event-driven messaging rule editor.
5. Settings – API configuration, rate limits, and logging options.

== Changelog ==

= 1.1.0 =
* NEW: Phone number OTP verification as primary onboarding method.
* NEW: Frontend OTP login shortcode [fwa_otp_login].
* NEW: Phone verification shortcode [fwa_otp_verify].
* NEW: Phone verification bar shortcode [fwa_phone_verify_bar].
* NEW: WhatsApp Chat Widget with multi-agent support.
* NEW: Chat widget shortcodes [fwa_chat_widget] and [fwa_chat_button].
* NEW: Message template engine with 40+ placeholders.
* NEW: Placeholder cheat-sheet API for admin UI.
* IMPROVED: Onboarding wizard with 5-step OTP flow.
* IMPROVED: Security with SHA-256 hashed OTP storage and rate limiting.

= 1.0.0 =
* Initial release.
* Multi-instance WhatsApp account management.
* Full messaging support (text, image, video, audio, document, contact, poll, link preview).
* Campaign engine with bulk messaging and scheduling.
* Automation engine with WooCommerce, WordPress, and Flexi Revive Cart event hooks.
* Scheduled messages (one-time and recurring).
* Contact management with CSV import/export and WooCommerce sync.
* Dashboard widget with quick-send and status overview.
* Central logging system with database and file fallback.
* REST API webhook endpoints.
* Deactivation data-cleanup prompt.

== Upgrade Notice ==

= 1.1.0 =
New: OTP login, chat widget, template engine. Onboarding wizard now uses phone verification as primary method.

= 1.0.0 =
Initial release.
