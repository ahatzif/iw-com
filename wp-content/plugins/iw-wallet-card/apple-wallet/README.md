# Apple Wallet Credentials

Apple Wallet credentials are project-specific and must not be committed.

Required values:

- Apple Team ID
- Membership Pass Type ID
- Tickets Pass Type ID, if ticket passes are enabled
- APNs key ID
- APNs private key `.p8`
- Membership pass certificate `.p12`
- Tickets pass certificate `.p12`, if ticket passes are enabled
- Certificate passwords
- WWDR certificate `.pem`, if required by the PKPass library

Recommended setup:

```bash
wp option update iw_wallet_card_apple_team_identifier "TEAMID1234"
wp option update iw_wallet_card_apple_key_id "KEYID12345"
wp option update iw_wallet_card_apple_pass_type_identifier "pass.com.example.membership"
wp option update iw_wallet_card_apple_tickets_pass_type_identifier "pass.com.example.tickets"
wp option update iw_wallet_card_apple_private_key_path "/secure/path/AuthKey_KEYID12345.p8"
wp option update iw_wallet_card_apple_certificate_path "/secure/path/membership.p12"
wp option update iw_wallet_card_apple_certificate_password "certificate-password"
wp option update iw_wallet_card_apple_tickets_certificate_path "/secure/path/tickets.p12"
wp option update iw_wallet_card_apple_tickets_certificate_password "tickets-certificate-password"
wp option update iw_wallet_card_apple_wwdr_path "/secure/path/WWDR.pem"
```

Local credential files matching `*.p8`, `*.p12`, `*.cer`, and `*.pem` are ignored in this directory.
