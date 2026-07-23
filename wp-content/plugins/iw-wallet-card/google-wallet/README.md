# Google Wallet Credentials

Google Wallet credentials are project-specific and must not be committed.

Required values:

- Google Wallet issuer ID
- Service account JSON with Wallet Objects API access
- Allowed JWT origins for live, UAT, and local domains

Recommended setup:

```bash
wp option update iw_wallet_card_google_issuer_id "3388..."
wp option update iw_wallet_card_google_service_account_path "/secure/path/google-wallet-service-account.json"
wp option update iw_wallet_card_google_class_suffix "membership"
wp option update iw_wallet_card_google_object_prefix "member_"
wp option update iw_wallet_card_google_application_name "Example Museum Wallet"
wp option update iw_wallet_card_google_jwt_origins "https://www.example.com,https://uat.example.com,http://localhost"
```

Local `*.json` files are ignored in this directory. Use `service-account.example.json` as a shape reference only.
