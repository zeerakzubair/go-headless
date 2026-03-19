=== GoHeadless — WordPress & WooCommerce Headless CMS ===
Contributors: zeerakzubair
Tags: headless, woocommerce, api, decoupled, rest-api, headless cms, nextjs, gatsby
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Convert your WordPress or WooCommerce site into a headless CMS. Block frontend access while keeping REST API, Store API, and wp-admin fully functional.

== Description ==

**GoHeadless** instantly converts your WordPress or WooCommerce site into a headless CMS. When activated, it blocks all frontend access while keeping your REST API, WooCommerce Store API, and wp-admin dashboard fully functional.

= Why GoHeadless? =

Building a modern frontend with Next.js, Nuxt, Gatsby, or any other framework? You don't need WordPress rendering HTML on the frontend. GoHeadless ensures your WordPress backend serves only as a content API and admin interface.

= Key Features (Free) =

* **Toggle On/Off** — Enable or disable headless mode with a single click
* **Custom Blocked Message** — Set a custom message for visitors who access the frontend directly
* **Frontend Redirect** — Optionally redirect all frontend traffic to your frontend application URL
* **HTTP Response Code** — Choose between 403, 200, 404, or 503 response codes
* **Route Whitelisting** — Define URL prefixes that should bypass headless mode
* **CORS Header Management** — Configure Access-Control-Allow-Origin for your frontend domain
* **REST API Restriction** — Optionally require authentication for REST API access
* **Disable RSS Feeds** — Block access to all RSS, Atom, and RDF feeds
* **Disable XML-RPC** — Turn off the XML-RPC interface for improved security
* **Disable oEmbed** — Remove oEmbed discovery links and scripts
* **Remove WordPress Version** — Strip version numbers from HTML head and asset URLs
* **Disable Emoji Scripts** — Remove the default WordPress emoji detection scripts
* **Remove Shortlink & RSD** — Clean up unnecessary tags from the HTML head
* **Status Dashboard** — View your current configuration at a glance
* **WooCommerce Compatible** — Works seamlessly with WooCommerce REST API and Store API

= Perfect For =

* Next.js, Nuxt, or Gatsby frontends
* Headless WooCommerce stores
* React or Vue.js single-page applications
* Mobile app backends
* API-only WordPress installations
* Static site generators using WordPress as a data source

= Coming Soon: GoHeadless Pro =

Unlock advanced features with a one-time payment (no subscriptions):

* Custom HTML/CSS blocked page template
* IP-based access whitelisting
* Multiple redirect rules with conditions
* Import & export settings
* Custom response headers
* Role-based frontend access
* Maintenance mode with countdown
* Priority email support

== Installation ==

1. Upload the `headless-mode` folder to the `/wp-content/plugins/` directory, or install directly from the WordPress plugin repository.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > GoHeadless** to configure the plugin.
4. Enable headless mode and customize your settings across the available tabs.

== Frequently Asked Questions ==

= Will this break my wp-admin? =

No. The wp-admin dashboard, login page, and all admin functionality remain fully accessible. Only the public frontend is blocked.

= Does it work with WooCommerce? =

Yes. The WooCommerce REST API, Store API, and WooCommerce admin pages all work normally. The `/wc-auth` route is whitelisted by default.

= Can I still use the REST API? =

Absolutely. The WordPress REST API (`/wp-json`) is whitelisted by default and remains fully functional. You can optionally restrict it to authenticated users only.

= What happens when someone visits my site? =

They will either see your custom message with the configured HTTP response code, or be redirected to your frontend application URL — depending on your settings.

= Can I whitelist specific routes? =

Yes. On the **API & Routes** tab, you can add any URL prefix to the whitelist. Routes starting with those prefixes will bypass headless mode.

= Does this affect SEO? =

Since the frontend is meant to be served by your decoupled application, WordPress frontend SEO is not relevant. The default 403 response code signals to search engines that the WordPress frontend is intentionally blocked.

= Is this plugin translation-ready? =

Yes. All strings use WordPress internationalization functions and the `headless-mode` text domain.

= How do I uninstall completely? =

Deactivate and delete the plugin. All settings are automatically removed from the database on uninstall.

== Screenshots ==

1. General settings tab — enable headless mode and configure the blocked message
2. API & Routes tab — manage whitelisted routes and CORS settings
3. Security tab — disable RSS, XML-RPC, oEmbed, and more
4. Status dashboard — view your current configuration at a glance
5. About tab — plugin information and Pro feature preview

== Changelog ==

= 1.0.0 =
* Initial release
* Tabbed admin interface (General, API & Routes, Security, Status, About)
* Toggle headless mode on/off with visual switch
* Custom blocked page message
* Configurable HTTP response code (403, 200, 404, 503)
* Frontend redirect to external app URL
* Route whitelisting
* CORS header management with specific origin support
* REST API restriction option for authenticated-only access
* Security hardening: disable RSS feeds, XML-RPC, oEmbed
* Cleanup options: remove WP version, emoji scripts, shortlink, RSD link, WLW manifest
* Status dashboard showing current configuration and environment
* Admin bar indicator when headless mode is active
* Plugin action link (Settings) on the Plugins page
* Clean uninstall handler that removes all plugin data
* Multisite support
* Full internationalization support
* WordPress Coding Standards compliant
