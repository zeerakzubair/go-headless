# GoHeadless — WordPress & WooCommerce Headless CMS

Convert your WordPress or WooCommerce site into a headless CMS. Block frontend access while keeping REST API, Store API, and wp-admin fully functional.

## Features (Free)

- Toggle headless mode on/off with a visual switch
- Custom blocked page message
- Frontend redirect to your decoupled app
- Configurable HTTP response codes (403, 200, 404, 503)
- Route whitelisting
- CORS header management
- REST API access restriction
- Security hardening (RSS, XML-RPC, oEmbed, WP version removal)
- Status dashboard
- Admin bar indicator when active
- WooCommerce compatible

## Perfect For

- Next.js / Nuxt / Gatsby frontends
- Headless WooCommerce stores
- React or Vue.js SPAs
- Mobile app backends
- API-only WordPress installations

## Installation

1. Download the latest release or clone this repo into `/wp-content/plugins/`
2. Activate through **Plugins** in wp-admin
3. Go to **Settings > GoHeadless**

## Screenshots

| General Settings | Security Tab | Status Dashboard |
|---|---|---|
| Toggle, message, redirect, response code | RSS, XML-RPC, oEmbed, version removal | At-a-glance config overview |

## Requirements

- WordPress 5.8+
- PHP 7.4+

## GoHeadless Pro (Coming Soon)

- Custom HTML/CSS blocked page template
- IP-based access whitelisting
- Multiple redirect rules with conditions
- Import & export settings
- Role-based frontend access
- Maintenance mode with countdown
- Priority support

## Author

**Zeerak Zubair** — [Zetherial Labs](https://zetheriallabs.com)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
