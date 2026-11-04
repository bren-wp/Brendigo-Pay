# Brendigo Pay 0.8.3 — sigurnosni pregled i granice produkcije

Datum: 2026-11-04. Opseg: nova sinkronizacija WP instalacije, plaćene licence, admin panel i WooCommerce zaštitne kontrole. **Ovaj zapis nije vanjski penetration test i ne potvrđuje regulatornu usklađenost.**

## Pregled provedenih kontrola

| Kontrola | Status | Gdje |
|---|---|---|
| Odvojeni admin i korisnički panel | Implementirano, dodatni E2E potreban | \`/admin\`, \`/sites\` |
| Tri plaćena paketa, bez free plana | Implementirano | \`Plans.php\` |
| Kvota aktivnih web-stranica 1/5/25 | Implementirano, zaključavanje pri izdavanju | \`SiteLicenses.php\` |
| Samo operator može potvrditi plaćeni plan | Implementirano, ručna provjera uplate | \`admin_user_ids\` iz privatnog configa |
| Domain-bound licencija, jednokratni ključ | Implementirano, SHA-256 hash u JSON-u | \`SiteLicenses.php\` |
| Site-scoped API token, revokacija na rotaciju | Implementirano | \`SiteLicenses.php\`, \`authenticate_api\` |
| Zaštićena sinkronizacija WP instalacije | Implementirano, HTTP smoke | \`POST /api/v1/sites/sync\` |
| Aktivacija provjerava installation ID i origin | Implementirano | \`SiteLicenses.php\` |
| Validacija verzije dodatka i WordPressa | Implementirano, max 32 znakova i whitelist | \`/api/v1/sites/sync\` |
| WP admin nonce i operator CSRF | Implementirano, offline provjera | WordPress i platforma |
| WooCommerce ne prihvaća stare potvrde licence | Implementirano, offline testovi | Classic, Blocks, \`process_payment\` |
| Sanitizacija HTML izlaza i tajni ključeva | Pregledano ciljano, bez potvrde cjelovitog audita | PHP views |
| Mobilno/tablet sučelje | CSS prilagodbe, stvarni 1:1 vizualni QA još otvoren | platform i WP \`assets/*.css\` |
| Stvarni Stripe/PayPal/Revolut live payment proces | Nije implementirano / validirano | SANDBOX ONLY |
| Automatska naplata paketa, povrat i obnova | Nije implementirano | Ručna potvrda pretplate |

## Kritični blokatori za produkcijsku naplatu (otvoreni)

1. **Vlasništvo domene:** samo poznavanje licence i izjava o originu ne dokazuje DNS/HTTP kontrolu domene. Potrebna je dodatna sigurna potvrda vlasništva uz ograničenje SSRF-a i DNS-rebindinga.
2. **Platni pružatelji:** potrebno je implementirati i validirati stvarne payment intent/order/capture tokove, idempotency, potpise webhookova, refundacije, chargebackove, PSD2/SCA i sinkronizaciju narudžbi. Postojeći konektori ostaju u TEST/Sandbox načinu.
3. **Pretplate i licencije:** sustav ručno potvrđuje uplate; treba pouzdani produkcijski billing, webhook potvrdu, obnove, proraciju, porezne dokumente, promjene plana i korisničku obavijest prije isteka.
4. **WordPress end-to-end:** treba testirati stvarne WordPress/WooCommerce verzije (uključujući Blocks, HPOS, multisite, promjenu domene, site cloning, PHP 8.1+ i dodatke u sukobu).
5. **Raspoređivanje:** WP-Cron ovisi o posjetama. Za stabilne intervale potreban je stvarni cron ili task runner, te nadzor izostanka heartbeat događaja.
6. **Operativna sigurnost:** potrebno je provesti neovisni penetration test, pregled ovisnosti/licenci, provjeru HTTPS i upravljanja ključevima, backup/restore vježbu, threat model i GDPR/PSD2 pravnu provjeru.
7. **Vizualna kvaliteta:** referentne slike služe kao dizajnerska specifikacija; bez stvarnih usporednih snimki na jednakom viewportu **nije potvrđena 1:1 pixel parity**.

## Verifikacija iz ove iteracije

- PHP lint, sandbox adapters, JSON store, domenske licence, administratorske radnje, HTTP smoke scenariji, WooCommerce Classic/Blocks bootstrap, CSS/JS statičke provjere prolaze na CI.
- HTTP smoke odbacuje pogrešni ID instalacije, pogrešni origin i pogrešan format verzije; provjerava vidljivost statusa u korisničkom i administratorskom panelu te suspenziju plana.
- WordPress offline kontrakt provjerava fail-closed ponašanje kod udaljene greške, isteka statusa, neispravne domene i promjene site ID-ja.
- Potpuno produkcijsko izdanje **ne smije** imati oznaku live-payment ready dok su blokatori otvoreni.

## Dead-code napomena

Novi helperi \`sync_installation\`, \`schedule_sync\`, \`unschedule_sync\`, \`manual_sync\` i \`license_is_current\` imaju eksplicitne pozivatelje u WordPress akcijama, admin formi i gatewayima. Na temelju ove ciljane provjere nisu prepoznate neiskorištene funkcije novog modula; to **nije** cjeloviti dead-code audit svih povijesnih datoteka projekta.
