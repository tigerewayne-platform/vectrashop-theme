# Vectra theme

The WordPress block theme for vectrashop.co.za. Colours, type and spacing come from the brand system in [vectrabrandOS](https://github.com/tigerewayne-platform/vectrabrandOS), never from this repo directly.

## How it fits together

```
vectrabrandOS/brand/tokens.json ──► scripts/build_theme_json.py ──► theme.json ──► WordPress
                                                                     templates/, parts/, patterns/
GitHub main ──► GitHub Action ──► staging site  (automatic)
GitHub main ──► GitHub Action ──► live site     (run by hand, choose "production")
```

| Path | What it is |
| --- | --- |
| `theme.json` | Generated. Palette, fonts, spacing from the brand tokens, plus element styles. Do not edit by hand. |
| `scripts/theme.base.json` | Styles and settings that are not brand tokens. Edit this, then rebuild. |
| `templates/` | Page templates: home, shop, product, search, page, 404. |
| `parts/`, `patterns/` | Header, footer, hero, category tiles, trust strip. |
| `inc/stock.php` | The "Stock by warehouse" block (Cape Town and Johannesburg). |
| `assets/` | Fonts (self-hosted, no Google requests), logos, favicons, CSS. |

## Changing the design

**Brand change** (a colour, font or spacing step): change it in vectrabrandOS first, then here run
`python3 scripts/build_theme_json.py ../vectrabrandOS/brand/tokens.json` and commit.

**Layout change made in the WordPress Site Editor:** the editor saves to the database, not to these files. To keep a change, install the free Create Block Theme plugin on staging, use "Save changes to theme", download the theme and commit the changed files here. Otherwise the next deploy overwrites it.

**Code change:** open a pull request. The Checks workflow lints PHP and confirms theme.json matches the brand tokens.

## Stock by warehouse

The block reads three product meta fields written by the catalogue sync: `_vectra_stock_cpt`, `_vectra_stock_jhb` and `_vectra_eta` (Y-m-d). Until those exist it falls back to WooCommerce's own stock status.

## Deploy setup (one-off)

In this repo's Settings → Environments, create `staging` and `production`. For each, add:

- Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (an FTP account from your host, limited to that site)
- Variable: `THEME_DIR`, e.g. `/public_html/wp-content/themes/vectra/` (with the trailing slash)

On `production`, tick "Required reviewers" so a live deploy needs your approval.
