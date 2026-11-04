# Visual QA — Brendigo Pay v0.8.0

The user supplied ten reference screenshots on 2026-11-04. **These are visual targets, not source code and not proof of implementation.** The implemented UI uses actual HTML, CSS, SVG, PHP templates and WordPress components. No newly generated imagery or screenshot-background substitution.

## Screen-by-screen implementation map

| Reference | Implemented location | Verification |
|---|---|---|
| Brand identity | `platform/public/assets/brand-mark.svg`, `assets/app.css`; plugin `assets/admin.css` and `public.css` | Approximate logo; exact vector shape requires final art approval |
| Web landing | `GET /` via `platform/src/Ui.php::bp_site_home` | Offline rendering at 1448px/390px, no horizontal overflow |
| Plans/pricing | `GET /pricing` via `bp_site_pricing` | HTML/PHP and CSS lint; illustrative prices, not official commercial rates |
| Checkout via link | `GET /p/{id}` via `bp_public_payment` | Sandbox provider redirects; no local card-number input |
| Merchant overview | `GET /dashboard` via `bp_merchant_ui` | HTTP smoke tests |
| New link modal | `dialog#bp-new-link` on merchant dashboard | Uses existing POST `/dashboard/links`, CSRF and amount validation; description is saved and escaped; live preview updates |
| Transactions | `GET /dashboard?tab=transactions` | Tenant-owned records, CSV export, visible-row filters and transaction detail dialog |
| WordPress admin settings | `views/admin-settings.php`, `assets/admin.css` | PHP lint, not yet screenshot-compared on a real WordPress site |
| Gutenberg editor | `assets/block.js`, `assets/editor.css` | JS syntax; requires real WordPress/Gutenberg E2E testing |
| WooCommerce checkout | `class-bp-gateway.php`, `assets/public.css`, `assets/woocommerce-block.js` | Offline simulated gateway tests; no live WP instance |

## Visual tokens (as implemented)

- Indigo `#1e2bb8`, royal blue `#315bfa`, cyan `#06c6b6`, navy `#0f172a`.
- Panels: 12–18px corner radii, soft blue border `#dce4f4` and light shadows.
- Layout: responsive CSS grid; mobile breakpoint at 800px and compact breakpoint at 520px on web.
- Typography: Inter with system sans-serif fallback; the Inter font is not bundled.
- Brand: programmatic favicon and app icons plus WordPress.org icon/banner files in `wordpress-org-assets/`. These are original SVG-derived files and can be revised when a final approved vector logo is supplied.
- WC payment panel is styled inside the existing WooCommerce checkout: **it does not overwrite the merchant's theme/layout**.

## Remaining work needed for literal 1:1 pixel parity

1. Obtain test installations of WordPress and WooCommerce, with active theme information, plus target viewport and browser zoom.
2. Obtain exact source logo/vector and licensed/product media assets if required. The supplied screenshots only contain rasterized views.
3. Render all logged-in pages and WordPress/WooCommerce pages and compare screenshot overlays/diffs at matching viewport sizes; tune line heights, spacing, radii and component dimensions. Public web routes were rendered offline at 1448 and 390 pixels; this is not an overlay parity measurement.
4. Test responsive views at 390px, 768px, 1024px and 1448px; keyboard focus and screen readers.
5. Test Gutenberg block insertion, saving, reopening and dynamic link rendering; test WC classic + Blocks checkout using official test orders.
6. Complete integration and compliance work before enabling real money. Sandbox UI never claims that a real provider is connected.

This release does not claim those steps have been completed.

## Hr — napomene za predaju

Svih deset dostavljenih slika korišteno je kao smjernica za redizajn. Rezultat je izveden stvarnim komponentama, nije statična slika preko koje su postavljeni neaktivni gumbi. U ovom izdanju **nije potvrđena pixel-po-pixel jednakost**. Dodatak i WooCommerce treba pregledati unutar stvarne instalacije kako bi se usporedio s referentnom slikom pri istoj širini prozora, fontovima i postavkama teme.

## v0.6.0 — status 10 referenci

| Referenca | Implementirano | Preostalo prije 1:1 |
|---|---|---|
| Brand identity, logo, ikonice | Lokalni SVG brend, app ikona, favicon i zajedničke boje | Originalni vektorski izvor logotipa i optičko poravnanje |
| Naslovnica | Responzivan hero, mockup i kartice | Fina usporedba geometrije i marketinški ilustrativni detalji |
| Cjenik | Kartice, usporedba i FAQ | Godišnji izračun nije funkcionalan; cijene su koncept |
| Naplatna poveznica / checkout | Trgovac, stvarni iznos, sigurni pružatelji, redoslijed koraka | Ne uključivati direktan obrazac kartice bez certificiranog hosted SDK-a |
| Korisnički pregled | KPI, dnevni graf iz JSON-a, poveznice i aktivnosti | Analitički filtri i vremenska usporedba |
| Novi platni link | Funkcionalan modal, pregled naziva i iznosa | Odabir više opcija / rok važenja i QR u live previewu |
| Transakcije | Filtri, detalji u bočnoj ladici, CSV | Napredni filtri, straničenje i refundi |
| WordPress admin | Brendirani panel, integracije, stvarna lista poveznica | Stvarni screenshotovi i responsive validacija u WP adminu |
| Gutenberg editor | Četiri bloka, editor bira link i prikazuje stvarni iznos | Usporedba na stvarnom Gutenberg editoru |
| WooCommerce checkout | Classic i Blocks payment gateway, kartice pružatelja | Puni WooCommerce E2E, stvarni theme layout i HPOS test |

Vizualni mockup nije izvor podataka ni dokaz transakcije. Reference sadrže ilustrativne financijske metrike i neke marketinške tvrdnje koje ne smijemo prikazivati kao stvarne.

## Marketing README screenshot policy v0.8.0

Snimke u `docs/screenshots/` stvarni su lokalno renderirani web-programski zasloni (HTML/CSS/JS u Chromiumu), a ne korisničke reference. U repozitoriju **nema stvarnih WordPress admin/Gutenberg/WooCommerce screenshotova** iz instaliranog WordPressa. Marketinški README smije koristiti WordPress.org banner/ikonu, ali ih mora jasno razlikovati od stvarnih screenshots. Ove snimke izraditi u testnoj WordPress instalaciji s identificiranom temom i WooCommerce verzijom prije prikazivanja kao stvarnih.
