# Instalacija Brendigo Pay v0.3.0 — razvojni sandbox

## Zahtjevi

PHP 8.2+ s JSON, sodium, OpenSSL, cURL i zlib; HTTPS za javne instalacije; jedan server i mogućnost postavljanja web-roota na `platform/public`. Za WordPress je potrebna normalna WordPress baza (MySQL/MariaDB); ona nije dio Brendigo Pay platforme.

## Platforma

```sh
cd /var/www/brendigo-pay/platform
php bin/setup.php https://pay.example.hr
```

Nginx/Apache virtual host `root` mora biti **samo** `/var/www/brendigo-pay/platform/public`; `config.php`, `src`, `bin`, `storage/data` moraju biti izvan web-roota i čitljivi samo PHP sistemskom korisniku. Onemogućite `display_errors`, dodajte HTTPS/HTTP2, WAF, backup politiku i sustavni nadzor. **Ne koristite PHP development server za javni promet.**

## Konfiguracija sandbox računa

Prijavite se na platformu, **Postavke**. Za Stripe upišite `sk_test_...` i `whsec_...`; za PayPal Sandbox REST Client ID/Secret + Webhook ID; za Revolut Merchant Sandbox secret i webhook signing secret. Točne URL-ove webhookova prikazuje platforma. Registrirajte URL u službenom sandbox portalu pružatelja i izvršite end-to-end test s njegovim testnim instrumentima. Verifikacija statusa na Brendigo Pay platformi ne zamjenjuje autorizaciju kod pružatelja.

## Opcionalni Stripe Connect Standard OAuth (samo test)

Platforma zahtijeva vlastiti Stripe Connect platform account i njegova testna ovlaštenja. Postavite **isključivo serverske varijable okruženja**, ne u JS ili repozitorij:

```sh
BP_STRIPE_CONNECT_CLIENT_ID=ca_TVOJ_CONNECT_CLIENT_ID
BP_STRIPE_CONNECT_SECRET=sk_test_TVOJ_PLATFORM_KEY
BP_STRIPE_CONNECT_WEBHOOK_SECRET=whsec_TVOJ_CONNECT_SIGNING_SECRET
```

U Stripe Connect developer postavkama konfigurirajte točan OAuth redirect `https://pay.example.hr/settings/stripe-connect/callback` i webhook za povezane račune `https://pay.example.hr/webhooks/stripe-connect`. Nakon spremanja env varijabli u PHP-FPM konfiguraciji korisnik u **Postavke** može kliknuti *Poveži Stripe TEST račun*. Onemogućeno je kada konfiguracija nedostaje. Povezivanje se temelji na Stripe OAuth state tokenu vezanom za korisničku sesiju, a događaji se verificiraju i povezuju isključivo s odgovarajućim povezanim testnim računom. Ne predstavljati kao dovršeni onboarding dok stvarni testni račun nije prošao puni E2E postupak. Direktni Stripe webhook i Connect webhook imaju zasebne signing secret vrijednosti.

## WordPress

Prenesite `brendigo-pay-wordpress-0.3.0.zip` kao standardni dodatak. Generirajte `bp_test_...` token za točan WordPress HTTPS origin i unesite ga pod **Brendigo Pay → Postavke**. Pokrenite *Testiraj povezivanje*. Bez WooCommercea: `[brendigo_pay id="..." ]` ili Gutenberg blok. S WooCommerceom: uključite gateway u WooCommerce Payments. U classic i Checkout Block oblicima dostupni su Stripe, PayPal i Revolut sandbox izbori. Za redovito ažuriranje statusa transakcija potreban je pouzdano pokretan WP-Cron. Nisu provedeni stvarni WP/Checkout Block integracijski testovi.

## Nadogradnja s 0.1.x / 0.2.x

Najprije obavezno kopirajte **oba** `platform/config.php` i `platform/storage/data`. Zamijenite samo aplikacijski kod; zadržite podatke i originalni `app_key`. Novi JSON folderi `paypal/`, `revolut/`, `provider_orders/` stvaraju se automatski. Postojeći Stripe testni ključevi i transakcije ostaju u starom formatu (Stripe je zadani provider). Ažurirajte WordPress dodatak novim ZIP-om, provjerite API vezu i ponovo pokrenite lokalne testove.

## Enkriptirani backup

```sh
BP_BACKUP_PASSPHRASE='DUGA_SNAŽNA_LOZINKA' php bin/backup.php /private/brendigo-2026.bpenc
# Obnova: zaustavite aplikaciju, prazan storage/data, originalni config.php
BP_BACKUP_PASSPHRASE='DUGA_SNAŽNA_LOZINKA' php bin/restore.php /private/brendigo-2026.bpenc
```

Ne spremati `.bpenc` datoteke unutar web root direktorija ni u javni Git. Posebno sigurno sačuvajte `app_key`. Backup je ograničen na 50 MB JSON sadržaja i traži dostupnu PHP memoriju za enkriptiranje, pa nije rješenje za sustave velikog volumena.
