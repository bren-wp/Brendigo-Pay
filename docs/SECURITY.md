# Brendigo Pay — sigurnost (v0.8.0 SANDBOX)

## Implementirano i pokriveno lokalnim testovima

- PHP sesije `HttpOnly`, `SameSite=Lax`, `Secure` na HTTPS; Argon2id za lozinke, CSRF tokeni i dva odvojena limita prijave (IP + IP/e-adresa).
- JSON per-record atomic `rename`, `flock`, 0700 direktoriji i 0600 datoteke; bez SQL-a u web-platformi.
- Hashirani API ključevi vezani uz origin, opozivanje; Stripe/PayPal/Revolut sandbox podaci šifrirani libsodium secretbox algoritmom s ključem iz konfiguracije.
- Stripe HMAC webhook s vremenskom tolerancijom; Revolut HMAC webhook s vremenskom tolerancijom, te dohvat originalne narudžbe; PayPal server-side capture i postback provjera potpisa.
- Iznosi u EUR centima, provjera `provider_order_id` i identifikatora transakcije, zaštita od pogrešnog iznosa i valute.
- API autorizacija po korisniku i domeni. WordPress dodatak šifrira API token s WP salt-derived AES-256-GCM-om.
- SSRF zaštita: fiksne sandbox API domene, `wp_safe_remote_request`, checkout URL allowlist; bez automatskog praćenja redirectova u PHP HTTP pozivima.
- Form protection, escaping, CSP, HTTP security headers, CSV formula injection zaštita.
- CLI šifrirani backup kroz Argon2id PWHASH + sodium secretbox; prazni direktorij pri obnovi; sesije se ne vraćaju.

## Neprovjereno / nije implementirano

- **Nema stvarnog E2E testa Stripe, PayPal ili Revolut sandbox računa.** Offline verificiranje potpisa i provider JSON modela ne zamjenjuje realan API test.
- **Nema stvarne WordPress/WooCommerce instalacijske provjere**, automatiziranog testiranja Checkout Blocka/HPOS-a u živom plugin okruženju, ni modernih WC subscriptions/refunds procesa.
- **Nema produkcijskog Stripe Connect OAuth-a, PayPal partnerskog onboardinga ni Revolut partnerskog modela.** Korisnik trenutačno ručno unosi osobne sandbox vjerodajnice.
- Nema MFA/2FA, e-mail potvrde, sigurnog resetiranja lozinke, managementa pristupnih sesija, provjere identiteta trgovca, GDPR self-service alata ni trajnog provider outbox/redrive sustava.
- Nema redovite reconciliacije transakcija; ako webhook izgubi status a korisnik ne osvježi, JSON stanje može zaostajati. WP-Cron polling ne jamči vremenski determinističko ažuriranje.
- Nisu provedeni vanjski penetration test, PCI procjena opsega, PSD2/SCA analiza ni regulatorni pregled.
- JSON audit nije nepromjenjiva financijska knjiga; backup kopija **ne sadrži** `config.php`/`app_key`, koji se mora sigurno čuvati odvojeno.

**Ne puštati u javnu produkcijsku naplatu prije dovršetka navedenih obveza.**

## Nalazi iz prolaza v0.8.0

Pogledajte [SECURITY-AUDIT-0.8.0.md](SECURITY-AUDIT-0.8.0.md) i [DEAD-CODE-AUDIT-0.8.0.md](DEAD-CODE-AUDIT-0.8.0.md). Pregled izvornog koda i automatizirane provjere ne zamjenjuju neovisni penetration test ni regulatornu provjeru platne usluge.
