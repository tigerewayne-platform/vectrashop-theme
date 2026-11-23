# Vectra Group B2B site

The business site for The Vectra Group. It runs as a separate WordPress and WooCommerce install from vectrashop.co.za. The domain is assumed to be vectragroup.co.za until Wayne confirms it.

There are two parts:

| Folder | What it is |
| --- | --- |
| `vectra-group/` | Child theme of the Vectra shop theme. It adds the Group logo, the premium homepage (quote box, commitments, offices in Harare, Sandton and Cape Town, a Zimbabwe government and tenders section with a compliance pack, managed support, procurement facts) and business copy. Audience: SMEs in South Africa and Zimbabwe, and government in Zimbabwe. IT only. Prices show ex VAT. |
| `vectra-b2b/` | Plugin that does the B2B work: business sign-up and approval, prices behind login, RFQ to quote, price held and stock reserved until 17:00 on the last day, a quote page with a live countdown, accept to checkout at the held price, PO number and cost centre at checkout, pay on a 30-day account with a credit limit, saved kits, and an asset register with serials and warranty dates (CSV export). |

## Install on a fresh WordPress

1. Install WooCommerce. In **WooCommerce > Settings > Tax**, enter prices excluding tax and set "Display prices in the shop" and "in cart and checkout" to **Excluding tax**.
2. Upload the shop theme `vectra` (the parent), then upload `vectra-group` and activate **Vectra Group**.
3. Upload and activate the `vectra-b2b` plugin. Activation creates the pages **/quote/** and **/open-an-account/** and schedules the hourly job that expires quotes.
4. Go to **Settings > Permalinks** and press Save once. This registers the My account tabs: Quotes, Saved kits, Assets and warranty.
5. Go to **Settings > Reading** and set the homepage to "Your latest posts". The theme's front-page template takes over.
6. Install the Vectra Sync plugin so the catalogue and the Cape Town and Johannesburg stock come from Syntech. The quote engine reads `_vectra_stock_cpt` and `_vectra_stock_jhb`.
7. In **WooCommerce > Settings > Vectra B2B**, set the hold period, the email that receives new quotes, and, if you want PDF RFQs read by AI, a Claude API key. You can also put the key in `wp-config.php` as `define( 'VECTRA_B2B_ANTHROPIC_KEY', '...' );`.
8. In **WooCommerce > Settings > Payments**, enable **30-day account**. Keep your EFT and card methods for customers who aren't on terms.

## Running it day to day

- **Approving a business:** Users > the customer > "Vectra business account". Set the status to Approved (this emails the customer), choose the payment terms, set a credit limit and, if agreed, a fleet-standard laptop with a lock-until date.
- **Quotes:** WooCommerce > Quotes. The "To check by hand" column counts lines the engine couldn't match. Those customers were promised a reply within the time set in settings.
- **Serials:** on a completed order, add item meta named `Serial numbers` (comma-separated). They show in the customer's Assets tab and in the CSV.
- **Warranty length:** product meta `_vectra_warranty_months`. If it isn't set, warranty defaults to 12 months from the completed date.
- **Account payments:** use the order action "Mark account invoice paid". Unpaid account orders count against the customer's credit limit.

## How quoting works

1. The buyer pastes a list or uploads a CSV or TXT file. If an API key is set, a PDF RFQ also works: Claude only reads the request into lines and never sees prices or picks products.
2. Each line is matched against the catalogue. The product type has to match (a request for a laptop is never answered with a RAM stick), and specs count double. Among close matches, the engine prefers one with enough free stock, then the cheapest.
3. Stock is split across Cape Town first, then Johannesburg. Open quotes reserve their units, so two buyers can't be promised the same stock.
4. The quote holds the price until 17:00 Johannesburg time, a set number of working days later (default 4). When an approved buyer approves the quote, the cart charges the held price. Once the quote expires, the held price no longer applies.

## Not built yet

- Several buyers per company with approval limits. Today each login is its own account.
- Reading Word and Excel RFQs. The box asks people to paste those.
- Fleet-change alerts sent 60 days ahead. The fleet standard is shown in the account but not yet monitored.
- Office street addresses and phone numbers, the B-BBEE level, the managed support costing, and the Zimbabwe compliance documents (PRAZ, ZIMRA tax clearance, CR14, NSSA, incorporation, HP letter) still need Wayne's input.
- USD pricing for Zimbabwe tenders. Quotes are in rand today; tender quotes are prepared by hand from the Harare office.
