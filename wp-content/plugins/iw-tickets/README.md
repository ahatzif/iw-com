# IW Tickets

Reusable WooCommerce ticketing tools imported from BMW as an optional library plugin.

This plugin lives in `library/` because it has product-level dependencies and project credentials. It should not be installed by the default WordPress installer unless a project explicitly opts in.

## Dependencies

Required WordPress plugins/features:

- WooCommerce
- ACF/ACF Extended fields used by the ticketing options and ticket products

Run Composer inside the plugin directory on the target environment:

```bash
composer install --no-dev --optimize-autoloader
```

The `vendor/` directory is intentionally ignored.

## Project Options

Values can be provided as WordPress options, PHP constants, or filters.

WordPress option names:

```bash
wp option update iw_ticketing_brand_name "Example Museum"
wp option update iw_ticketing_pdf_logo_path "/secure/path/ticket-logo.svg"
wp option update iw_ticketing_pdf_qr_logo_path "/secure/path/qr-logo.svg"
wp option update iw_ticketing_pdf_footer_left "Example Museum\nAddress | email@example.com | example.com"
wp option update iw_ticketing_pdf_footer_right "Example Museum\nAddress | email@example.com | example.com"
wp option update iw_ticketing_pdf_visit_info_left "Line 1\nLine 2"
wp option update iw_ticketing_pdf_visit_info_right "Line 1\nLine 2"

wp option update iw_ticketing_google_wallet_service_account_path "/secure/path/google-wallet-service-account.json"
wp option update iw_ticketing_google_wallet_issuer_id "3388..."
wp option update iw_ticketing_google_wallet_class_suffix "tickets"
wp option update iw_ticketing_google_wallet_object_prefix "ticket_"
wp option update iw_ticketing_google_wallet_application_name "Example Museum Tickets"
wp option update iw_ticketing_google_wallet_jwt_origins "https://www.example.com,https://uat.example.com"

wp option update iw_ticketing_cashier_sms_prefix "Example Museum tickets"
wp option update iw_ticketing_cashier_nexi_user_agent "ExampleTickets/1.0 (cms:wordpress)"
wp option update iw_ticketing_zebra_test_qr_value "https://www.example.com"
```

Constants use the same suffix with the `IW_TICKETING_` prefix, for example `IW_TICKETING_GOOGLE_WALLET_ISSUER_ID`.

Filters use the `iw_ticketing_` prefix, for example `iw_ticketing_google_wallet_issuer_id`.

## Provider Credentials

Do not commit provider credentials.

Google Wallet ticket passes can inherit the Google issuer/service-account/origin configuration from `iw-wallet-card` when that plugin is active. Ticket-specific options above can override it.

Apple Wallet ticket passes inherit ticket certificate/team/pass settings from `iw-wallet-card` when available. Filters remain available:

- `iw_ticketing_apple_wallet_pass_type_identifier`
- `iw_ticketing_apple_wallet_team_identifier`
- `iw_ticketing_apple_wallet_certificate_path`
- `iw_ticketing_apple_wallet_certificate_password`
- `iw_ticketing_apple_wallet_wwdr_path`

Nexi POS is configured with environment variables or constants:

- `IW_NEXI_POS_API_USERNAME`
- `IW_NEXI_POS_API_PASSWORD`
- `IW_NEXI_POS_API_BASE_URL`
- `IW_NEXI_POS_TERMINAL_ID`

Twilio SMS is configured with ACF options or constants:

- `IW_CASHIER_TWILIO_ACCOUNT_SID`
- `IW_CASHIER_TWILIO_AUTH_TOKEN`
- `IW_CASHIER_TWILIO_MESSAGING_SERVICE_SID`
- `IW_CASHIER_TWILIO_FROM`

Zebra print agents use the Ticketing settings screen or constants:

- `IW_ZEBRA_PRINT_AGENT_TOKEN`
- `IW_ZEBRA_PRINT_WS_TOKEN`

## Notes

The first portability pass removed BMW/Benaki branding defaults from PDFs, Google Wallet tickets, SMS copy, Nexi User-Agent, and Zebra test data.

Optional guarded theme hooks are still supported when a project theme provides them, but the plugin no longer requires the old `iw_theme` folder.
