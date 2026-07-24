# COM Starter Extraction Notes

Living notes for turning the current COM implementation back into a reusable starter theme and plugin set.

## Decisions

- Header and footer should be dynamic:
  - Main navigation comes from the `main` menu location.
  - Header menu links accept friendly custom URLs such as `#museums` and `/tickets/`; the theme normalizes them against `home_url()`.
  - Footer legal links come from `copyright` first, then `footer`, with Privacy/Terms fallbacks.
  - Theme logos and footer copy belong in ACF Theme Settings.
- Use existing theme conventions:
  - Namespace stays project-code based, for example `com\theme`.
  - Block/module scaffolding should use `wp theme create block ...` and `wp theme create module ...`.
  - Gutenberg blocks live in `wp-content/themes/<project-theme>/gutenberg-blocks`.
- `iw-architecture` is the right home for project post types and taxonomies.
  - It is now generic and loads PHP files from `post-types/` and `taxonomies/`.
  - COM starts with a `museum` CPT and a hierarchical `museum-location` taxonomy.
- Tickets should not become a custom `ticket` CPT yet.
  - Ticket sales should lean on WooCommerce products.
  - Issued tickets / QR / wallet logic should stay in `iw-tickets` / related Woo plugins.
  - Revisit only if the editorial model needs a non-product ticket entity.
- `iw-posts-filters` / "Iw Recipes Filters" should be removed from COM.
  - It is inactive.
  - It is not part of `iw-architecture`.
  - It has hardcoded `news`, `recipes`, `sintages`, `producers` rewrite rules.
  - It calls `flush_rewrite_rules()` on every `init`, which is not acceptable for the starter.

## Completed In COM

- Dynamic header:
  - logo comes from `header_logo` / `header_logo_light`, with SVG sprite fallbacks.
  - nav comes from the `main` menu.
  - current local main menu contains `ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ` and `ΑΓΟΡΑ ΕΙΣΙΤΗΡΙΟΥ`.
  - mobile keeps the museum/ticket icons, language switcher, and account action while hiding the two long nav labels.
- Dynamic footer:
  - logo comes from `footer_logo` / `footer_logo_light`, with SVG sprite fallbacks.
  - legal links come from `copyright` / `footer`.
  - footer info, copyright, and credit text come from Theme Settings.
- Dynamic legal pages:
  - Privacy, Terms, and Cookie Declaration page references are stored in Theme Settings.
  - Legal pages are served by the generic `page.php`; no per-slug page templates.
  - The page title/header comes from the reusable `acf/page-header` block.
  - `page-header` uses the page title by default and shows the parent page title as eyebrow; pages without a parent use `Αρχική`.
  - Legal copy lives in WP page content.
  - The legal sidebar is generated from `<h2 id="...">` headings in the page content.
- ACF local JSON:
  - Theme Settings now has a Branding tab.
  - `recipes_page` was removed.
  - `tickets_page` and `buy_tickets_page` were added.
  - Museum Details field group was added for `museum`.
- Local content scaffolding:
  - Placeholder pages were created for `/tickets/` and `/buy-tickets/`.
  - Theme Settings option values point to those pages.
  - These are not the final tickets templates; they exist so navigation does not 404 while the real tickets page is built.
- Architecture:
  - `museum` post type added.
  - `museum-location` taxonomy added.
- Cleanup:
  - `wp-content/plugins/iw-posts-filters` removed from COM.
- Gutenberg block styling:
  - Blocks now use shared `com_theme_block_style_classes()` and `com_theme_block_wrapper_classes()` helpers.
  - Existing ACF blocks have a top-level `Settings` tab before block-specific fields.
  - The shared style group uses a top-level `Styles` tab for background color, text color, and spacing.
  - `theme_block_colors` now preserves default non-color utility classes, swaps only real theme color classes, and auto-selects a readable text color when a background is selected without an explicit text color.
  - WYSIWYG supports scroll sidebar generation from `<h2 id="...">` headings and uses `text-current`/`border-current` for styled legal content.
  - Main block templates now call the style helper; only internal group renderers stay unstyled directly because they are layout containers.
- Theme CLI:
  - `wp theme create block ...` now generates blocks that call the shared style and wrapper helpers.
  - New generated ACF field groups start with a `Settings` top-level tab.
  - The updated scaffold was used successfully for all four COM homepage blocks.
- Dynamic homepage:
  - `home-hero` provides editable copy, CTAs, background image, and an Embla carousel populated from selected or queried museums.
  - `museums-list` queries the `museum` CPT, supports curated relationships, and renders a reusable museum card partial with image and ticket fallbacks.
  - `anniversary-banner` is an editable CTA block.
  - `featured-experiences` is an editable repeater grid with mobile carousel behavior.
  - Shared COM buttons render through `templates/parts/com-button.php`, including consistent variants, sizing, icons, and hover states.
  - Experience images use direct `hover:` transforms. Avoid unnamed `group-hover:` selectors in content because the starter currently places `group` on `<body>`, which can trigger every matching descendant.
  - Header hash links use the existing Lenis scroll module when their target is present on the current page.
  - Five museum posts, taxonomy terms, images, excerpts, ticket prices, and links were seeded as real WordPress content.
  - Homepage blocks use the common `Settings` / `Styles` model and respect global background, text-color, and spacing fields.
  - Desktop and 390px mobile QA passed without page overflow or overlapping content.
- Dynamic museum single:
  - `single-museum.php` renders hero, visit details, content, features, ticket CTA, gallery image, and related museums from the `museum` CPT and Museum Details fields.
  - Related museums use a curated relationship first and fall back to other published museum posts.
  - The History & Art museum has complete seed content for layout testing; its phone, email, map, and timetable are placeholders pending approved production data.
  - All five current museum permalinks return HTTP 200 without PHP warnings.

## Starter Theme Follow-Ups

- Remove recipe/product/news starter leftovers from Theme Settings unless a project explicitly opts into them.
- Keep only generic page settings by default:
  - `privacy_page`
  - `terms_page`
  - `cookies_policy_page`
  - `search_page`
  - `tickets_page` only when Woo/tickets is enabled
- Add a clean Branding tab:
  - `header_logo`
  - `header_logo_light`
  - `footer_logo`
  - `footer_logo_light`
- Keep footer fields generic:
  - `footer_info_text`
  - `footer_copyright_text`
  - `footer_credit_text`
- Move block style helpers into the starter theme:
  - `com_theme_block_style_classes()`
  - `com_theme_block_wrapper_classes()`
- Keep legal/content blocks starter-ready:
  - `page-header` should derive title from `get_the_title()` by default.
  - `page-header` eyebrow should link to parent page, or home when there is no parent.
  - `wysiwyg` should support headings, lists, tables, blockquotes, figures, inline code, and optional scroll sidebar.
- Audit project-specific block defaults before extracting:
  - Some COM blocks have intentional `bg-blue text-white` defaults.
  - Media/promo blocks may need per-project defaults even though they now support Styles.
- Extract only genuinely reusable homepage pieces:
  - `anniversary-banner` and `featured-experiences` are good starter candidates after neutralizing COM copy and color defaults.
  - `home-hero` and `museums-list` are COM-specific blocks, but their relationship/query fallback, image fallback, shared card partial, and Embla setup are reusable patterns.
  - A neutral shared button partial can inherit the COM variant/icon pattern, but project colors and labels must stay outside the starter.
  - Keep project seed scripts local; do not move COM content or slugs into the starter.

## COM Site Build Follow-Ups

- Replace placeholder contact, map, timetable, and ticket information with approved content.
- Enter complete body copy, features, gallery images, and curated related museums for the remaining four museum posts.
- Build tickets page from Woo products and ticket-related plugins, not from static arrays.
  - Replace the current placeholder `/tickets/` and `/buy-tickets/` pages with the real Gutenberg/page templates.
- Keep legal pages dynamic in the starter:
  - Seed Terms/Privacy/Cookie Declaration as pages, not PHP strings.
  - The Cookie Declaration page should use the existing `acf/cookiebot-declaration` block.
  - Do not create `page-privacy-policy.php`, `page-terms-of-use.php`, or `page-cookie-declaration.php` in starters/projects unless a genuinely different layout is required.

## Cleanup Queue

- Audit `gutenberg-blocks/post-list.php` recipe-specific branches before starter extraction.
- Audit `templates/parts/breadcrumbs/breadcrumbs.php` for `recipe`, `products`, `new` project leftovers.
- Audit `page-promo`, `promos`, `featured-image`, `hero-video`, `full-screen-photo-video`, and `video-player` visually after real COM content is entered; they are now style-aware but still have design-specific inner backgrounds/overlays.

## Plugin Follow-Ups

- `iw-theme-gutenberg-blocks`:
  - Keep the improved color filter behavior in the reusable plugin.
  - Keep `Settings` / `Styles` admin card behavior consistent with BMW.
  - Consider moving any generic editor polish for collapsed ACF blocks into the plugin instead of the COM theme.
- `iw-theme-cli`:
  - Keep the updated block boilerplate as the default for new projects.
  - Add future options only if needed, for example `--no-styles`, `--nested`, `--with-module`.
  - Consider adding a small smoke command that creates a temp block, validates the generated PHP/ACF JSON, then deletes it.
