# IW Wallet Card

Reusable WordPress wallet card tooling for memberships and tickets.

This plugin lives in `library/` because it needs project credentials and should not be copied/activated by the default installer unless a project explicitly opts in.

## Dependencies

Run Composer inside the plugin directory on the target environment:

```bash
composer install --no-dev --optimize-autoloader
```

The `vendor/` directory is intentionally ignored.

## Project Options

Values can be provided as WordPress options, PHP constants, or filters.

WordPress option names:

```bash
wp option update iw_wallet_card_org_name "Example Museum"
wp option update iw_wallet_card_program_description "Example Museum Membership"
wp option update iw_wallet_card_tickets_program_description "Example Museum Ticket"

wp option update iw_wallet_card_apple_pass_type_identifier "pass.com.example.membership"
wp option update iw_wallet_card_apple_tickets_pass_type_identifier "pass.com.example.tickets"
wp option update iw_wallet_card_apple_team_identifier "TEAMID1234"
wp option update iw_wallet_card_apple_key_id "KEYID12345"
wp option update iw_wallet_card_apple_private_key_path "/secure/path/AuthKey_KEYID12345.p8"
wp option update iw_wallet_card_apple_certificate_path "/secure/path/membership.p12"
wp option update iw_wallet_card_apple_certificate_password "certificate-password"
wp option update iw_wallet_card_apple_tickets_certificate_path "/secure/path/tickets.p12"
wp option update iw_wallet_card_apple_tickets_certificate_password "tickets-certificate-password"
wp option update iw_wallet_card_apple_wwdr_path "/secure/path/WWDR.pem"

wp option update iw_wallet_card_google_service_account_path "/secure/path/google-wallet-service-account.json"
wp option update iw_wallet_card_google_issuer_id "3388..."
wp option update iw_wallet_card_google_class_suffix "membership"
wp option update iw_wallet_card_google_object_prefix "member_"
wp option update iw_wallet_card_google_application_name "Example Museum Wallet"
wp option update iw_wallet_card_google_jwt_origins "https://www.example.com,https://uat.example.com"
```

Constants use the same suffix with the `IW_WALLET_CARD_` prefix, for example `IW_WALLET_CARD_GOOGLE_ISSUER_ID`.

Filters use the `iw_wallet_card_` prefix, for example `iw_wallet_card_google_issuer_id`.

## Credentials

Do not commit Apple or Google credential files. Keep them outside the repo where possible, or place them locally in `apple-wallet/` and `google-wallet/` while relying on `.gitignore`.

See:

- `apple-wallet/README.md`
- `google-wallet/README.md`

## WP-CLI

Push updates to all registered wallet users:

```bash
wp iw-wallet:push-all
```

## Notes

Some ACF field keys still contain legacy `benaki_*` names for data compatibility, but visible labels are generic. A future migration can rename field names once we decide how to handle existing BMW content.
