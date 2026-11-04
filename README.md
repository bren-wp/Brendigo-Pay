> **Brendigo Pay v0.7.1 SANDBOX.** The new interface was built from user-provided design references. It is a responsive HTML/CSS implementation, not screenshots used as backgrounds. This does **not** claim validated pixel-by-pixel parity with those references, or readiness for live payments. The standalone platform continues to store data under `platform/storage/data` (JSON), while WordPress/WooCommerce use their own native databases.

# ◈ Brendigo Pay

[![Brendigo Pay checks](https://github.com/bren-wp/Brendigo-Pay/actions/workflows/ci.yml/badge.svg)](https://github.com/bren-wp/Brendigo-Pay/actions/workflows/ci.yml)

**Repozitorij:** https://github.com/bren-wp/Brendigo-Pay · **Dizajnerske reference:** [PIXEL-PARITY](docs/PIXEL-PARITY.md) · **Doprinosi:** [CONTRIBUTING](CONTRIBUTING.md)

**Web platforma bez vlastite SQL baze · WordPress dodatak · WooCommerce Checkout · Stripe TEST · PayPal Sandbox · Revolut Sandbox**  
**Verzija:** `0.7.1` · **Stanje:** razvojno / SANDBOX, bez stvarnih naplata  
**Autor:** Brendigo · **Službena stranica proizvoda:** [brendigopay.com](https://brendigopay.com) · **Stranica autora:** [brendigo.com](https://brendigo.com)

Brendigo Pay je vlastita platforma za izradu naplatnih poveznica i QR kodova, povezivanje postojećih testnih računa kod pružatelja plaćanja te naplatu iz WordPress stranice s WooCommerceom ili bez njega. Plaćanja vodi **povezani pružatelj**, ne Brendigo Pay. Korisnički i transakcijski podaci **platforme** nalaze se u privatnom `storage/data/*.json`, bez MySQL-a ili PostgreSQL-a.

> **UPOZORENJE:** Ovo je razvojni prototip s integracijama za testna okruženja. **Ne unositi live ključeve, ne koristiti za stvarni novac i ne predstavljati kao gotov platni sustav.** Platforma još nije prošla stvarna end-to-end sandbox testiranja kod tri pružatelja, provjeru na instaliranom WordPressu/WooCommerceu, profesionalni sigurnosni audit ni pravnu provjeru modela usluge.

## Novo u v0.7.1 — prvi korak pixel-parity WordPress administracije

- Dosljednije dimenzije kartica i obrazaca WordPress dodatka, gradient dekoracija i fokus stanja.
- Tabovi slijede URL hash i `aria-current` za pristupačnu navigaciju.
- `scripts/visual_compare.py` uspoređuje stvarne screenshotove sa referencom samo kada su dimenzije jednake.

## Prethodno u v0.7.0 — GitHub izvorni kod i testiranje

- Objavljen projektni izvorni kod web-platforme i WordPress/WooCommerce dodatka u repozitoriju.
- Ugrađeni GitHub Actions testovi koji provjeravaju PHP, lokalne sandbox testove i instalacijske ZIP-ove.
- Dodana mapa `design/references` s korisničkim referentnim slikama i kontrola prikaza u `docs/PIXEL-PARITY.md`.
- Standardizirani CSS dizajnerski tokeni, dokumentacija i metapodaci za autora Brendigo.

## Prethodno u v0.6.0 — dizajn, stvarni podaci i WordPress povezivanje

- Stranica naplate prilagođena referenci: dvije kolone, podaci pravog trgovca iz JSON-a, redovi proizvoda i naplate, aktivni sandbox pružatelji u radio karticama; završetak se odvija na *hosted checkoutu*, bez prikupljanja brojeva kartica na Brendigo Payu.
- Merchant graf više nije statična ilustracija nego dnevni iznos potvrđenih sandbox uplata u prethodnih 30 dana iz `transactions/*.json`. Uključena je legenda da graf nije stvarni promet.
- `GET /api/v1/links` vraća do 100 **aktivnih poveznica isključivo autentificiranog trgovca**. Provjereno je odbijanje neovlaštenog i drugog trgovca.
- WordPress Gutenberg uređivač putem autoriziranog REST proxyja dohvaća postojeće linkove i prikazuje naziv/cijenu, bez izlaganja API tajni u pregledniku. Editor ne postavlja iznos naplate.
- WordPress administracija prikazuje aktivne linkove i kopiranje shortcoda; WooCommerce classic i Checkout Block dobili su ujednačenije kartice pružatelja.
- Dodatno su dotjerani CSS razmaci, tipografija, responzivni paneli, checkout i detalji transakcija.
- Dodani su stvarni lokalni screenshotovi web-platforme u `docs/screenshots/` te skripta `tests/visual-qa.py` za automatsko stvaranje i provjeru HTML/CSS prikaza. Screenshotovi nisu referentne slike ni potvrda potpune 1:1 podudarnosti.

### Snimke stvarne aplikacije iz lokalnog vizualnog testiranja

![Web naslovnica (desktop)](docs/screenshots/web-home-desktop.png)
![Korisnički panel (desktop)](docs/screenshots/user-dashboard-desktop.png)
![Sandbox checkout (desktop)](docs/screenshots/sandbox-checkout-desktop.png)

**Ograničenje:** WordPress administracija i WooCommerce checkout nisu testirani unutar pune WordPress instalacije; njihov sučeljski kod je usklađen prema referencama, ali 1:1 nije potvrđeno.

## Trenutno dostupno

| Modul | Status v0.7.1 |
|---|---|
| Registracija, prijava, PHP sesije u JSON-u | Implementirano, lokalno testirano; nema 2FA ni potvrde e-adrese |
| Naplatne poveznice (EUR), QR, upravljanje aktivnošću | Implementirano i HTTP testirano |
| REST API s hashiranim ključevima vezanim uz domenu | Implementirano i HTTP testirano |
| Stripe Checkout | **Samo Stripe test**; potpisani webhook + opcionalni Connect Standard OAuth (TEST) uz konfiguraciju operatera |
| PayPal Checkout | **PayPal Sandbox adapter**; server-side capture i webhook verification adapter; nije provjeren na pravom sandbox računu |
| Revolut Hosted Checkout | **Revolut Sandbox adapter**; potpisani webhook i server-side order reconciliation; nije provjeren na pravom sandbox računu |
| Wordpress bez WooCommercea | Gutenberg blok, shortcode, gumb prema naplatnoj poveznici |
| WooCommerce Classic | Gateway s izborom Stripe, PayPal ili Revolut sandbox pružatelja |
| WooCommerce Checkout Block | JS `onPaymentSetup` adapter s izborom pružatelja; nije testiran na pravom WooCommerceu |
| WooCommerce HPOS | CRUD i deklarirana kompatibilnost; pravi integracijski test nije proveden |
| WooCommerce potvrda narudžbe | Autentificirano dohvaćanje iznosa, reference, pružatelja i statusa prije `payment_complete` |
| Audit dnevnik, izvoz CSV | Implementirano i lokalno testirano |
| JSON backup/restore | CLI, enkriptirano passphrase ključem (Argon2id + libsodium); testiran round-trip |
| Pretplate, naplata Brendigo članarine, refundi, isplate | **Nisu implementirani** |
| Stvarno povezivanje postojećih računa kroz partner OAuth | **Stripe Connect Standard TEST OAuth: implementiran kod, bez vanjskog E2E testa**. PayPal/Revolut partnerski onboarding nije implementiran |
| Live transakcije | **Onemogućene** |

## Instalacija platforme

**Zahtjevi:** PHP 8.2+, cURL, sodium, OpenSSL, JSON, zlib; HTTPS u javnom okruženju.

```sh
cd platform
php bin/setup.php http://127.0.0.1:8080
php -S 127.0.0.1:8080 router.php
```

Otvorite `http://127.0.0.1:8080`, registrirajte testnog trgovca, kreirajte naplatnu poveznicu. Postavke nude odvojeno unos **sandbox** vjerodajnica:

- **Stripe**: `sk_test_...`, `whsec_...`; webhook `/webhooks/stripe/{user_id}`.
- **Stripe Connect Standard (opcionalno, TEST)**: operater platforme mora konfigurirati zaštićene varijable okruženja `BP_STRIPE_CONNECT_CLIENT_ID=ca_...`, `BP_STRIPE_CONNECT_SECRET=sk_test_...`, `BP_STRIPE_CONNECT_WEBHOOK_SECRET=whsec_...` u PHP-FPM/web serveru. Na Stripe Connect platformi registrirajte OAuth redirect na `{BASE_URL}/settings/stripe-connect/callback` i Connect webhook na `{BASE_URL}/webhooks/stripe-connect`. Merchant zatim klikne **Poveži Stripe TEST račun** na stranici Postavke. Platforma koristi povezani account putem Stripe-Account zaglavlja. Ovo je kodna implementacija (OAuth state i račun provjere), **nije potvrđena na stvarnom Stripe Connect računu**. Testni provider webhookovi moraju biti zasebno konfigurirani.
- **PayPal**: Sandbox REST `Client ID`, `Secret`, `Webhook ID`; webhook `/webhooks/paypal/{user_id}`. Koristiti `PAYMENT.CAPTURE.COMPLETED`.
- **Revolut**: Merchant Sandbox secret key i webhook signing secret; webhook `/webhooks/revolut/{user_id}`. API verzija `2026-08-17`.

Kod za platne adaptere upotrebljava samo testne/sandbox API hostove. **Unošenje proizvoljnog API ključa u obrazac nije partnerski OAuth i ne predstavlja automatsko povezivanje računa**. Vjerodajnice pohranjuju se lokalno šifrirane. Platforma neće sama registrirati webhook kod pružatelja: trgovac to mora učiniti u sandbox upravljačkoj ploči.

**Javni root web-poslužitelja smije biti samo `platform/public`**. Direktoriji `storage/`, `src/`, `bin/` i `config.php` moraju ostati izvan javno dostupnog direktorija.

## WordPress dodatak — s WooCommerceom ili bez njega

Instalirajte `brendigo-pay-wordpress-0.7.1.zip` iz **WordPress → Dodaci → Dodaj novi → Prenesi dodatak**. U **Brendigo Pay** unesite URL platforme i generirani API ključ za točnu HTTPS domenu WordPress stranice. Kliknite **Testiraj povezivanje**.

Za običan WordPress unesite shortcode `[brendigo_pay id="ID_IZ_WEB_NADZORNE_PLOČE"]` ili Gutenberg blok *Brendigo Pay*. U WooCommerceu omogućite gateway pod **WooCommerce → Postavke → Plaćanja**. U classic checkoutu i u Checkout Blocku kupac može odabrati testnog pružatelja; ako njegov račun nije konfiguriran na platformi, API vraća pogrešku i narudžba se ne naplaćuje.

**WordPress i WooCommerce i dalje rade preko WordPress baze podataka**. Samostalna Brendigo Pay platforma ne koristi SQL. Nije moguće pokretati postojeći WordPress bez njegove standardne baze samo postavljanjem Brendigo Pay dodatka.

## JSON struktura

```text
platform/storage/data/
├── users/             # trgovci i Argon2id hash lozinke
├── emails/            # hashirani indeks e-adresa
├── sessions/          # PHP sesije (ne ulaze u backup)
├── links/             # naplatne poveznice
├── transactions/      # evidencija i status testnih plaćanja
├── provider_orders/   # poveznica udaljene narudžbe s transakcijom
├── stripe/            # šifrirani Stripe TEST podaci
├── stripe_connect/    # povezani Stripe Connect TEST account
├── stripe_connect_accounts/ # hashirani indeks račun → trgovac
├── paypal/            # šifrirani PayPal Sandbox podaci
├── revolut/           # šifrirani Revolut Sandbox podaci
├── tokens/            # SHA-256 hash API ključeva
├── rates/             # zaštita prijave
├── audit/             # aktivnosti računa
└── locks/             # flock datoteke
```

Datoteke se atomskim `rename` upisom pohranjuju na *jednom poslužitelju*. Ovo nije distribuirana transakcijska baza. Prije bilo kakve stvarne financijske upotrebe potrebni su trajni queue/outbox, oporavak prekida i provjera financijske konzistentnosti.

## Sigurnosna kopija i obnova

Na *zaštićenoj* lokaciji, izvan `platform/public`, pokrenite:

```sh
BP_BACKUP_PASSPHRASE='DUGA_JEDINSTVENA_LOZINKA' php platform/bin/backup.php /private/brendigo-pay-2026.bpenc
```

Za obnovu isključite aplikaciju i koristite **prazan** `storage/data` direktorij:

```sh
BP_BACKUP_PASSPHRASE='DUGA_JEDINSTVENA_LOZINKA' php platform/bin/restore.php /private/brendigo-pay-2026.bpenc
```

**Odvojeno i sigurno sačuvajte originalni `platform/config.php`**, osobito `app_key`; bez njega pohranjene tajne pružatelja neće biti čitljive ni nakon uspješnog restorea. Sessions i locks se ne vraćaju. Backup skripta odbija postojeće odredišne datoteke i lokacije unutar javnog web roota; restore odbija nepraznu JSON pohranu.

## Testiranje

```sh
bash tests/run-all.sh
```

Provjere uključuju PHP lint; JSON i session mehanizme; Stripe i Revolut potpise; strogu provjeru PayPal/Revolut iznosa/statusa; HTTP smoke test sa zasebnom testnom instalacijom; sandbox forme; negativne autorizacijske testove; backup/restore i WooCommerce mock testove za sva tri sandbox pružatelja. **Mock i offline testovi nisu dokaz funkcionalnosti na stvarnim API računima.**

## Što treba dovršiti prije v1.0

Vanjski Stripe Connect OAuth E2E test i Account Links alternativa, PayPal partnerski onboarding, Revolut odobreni partnerski model; pošteno i transparentno monetiziranje platforme; refundi, pretplate, GDPR alat, e-mail potvrda i 2FA, sustavno praćenje/retry webhookova, integracijska i E2E testiranja, PCI/PSD2 analiza, pravni/regulatorni audit, penetration test i produkcijska infrastruktura. Detaljnije u `docs/RELEASE-CHECKLIST.md`.

## Licenciranje

WordPress dodatak: GPL-2.0-or-later. QR biblioteka je zasebno licencirana prema `docs/THIRD_PARTY_NOTICES.md`. Za samostalnu PHP platformu prije javne komercijalne distribucije treba izabrati uvjete licence.


## Što je novo u v0.5.0 — prikaz i funkcionalnosti

- Dosljednije boje, rubovi, tipografija, razmaci i interaktivna stanja prema priloženim referencama.
- Novi programatski SVG favicon, lokalna ikona za web i WordPress.org materijali u `wordpress-org-assets/` (nisu screenshoti prikazani kao pozadine).
- Glavni navigacijski izbornik, poveznice podnožja te zasebna sekcija *Kako sustav radi*.
- Obrazac novog naplatnog linka sada sprema neobavezni opis u privatni JSON, s ograničenjem duljine i sigurnim prikazom na checkoutu.
- Pregled transakcije otvara pravi detaljni prozor s podacima trgovca, a vidljivih 40 redaka može se filtrirati po ID-u/opisu, statusu i pružatelju. CSV izvoz ostaje cjelovit.
- Responsivni HTML/CSS weba provjeren offline prikazima 1448×1086 i 390×844; lokalni HTTP i PHP testovi odvojeno provjeravaju API funkcionalnosti.
- WordPress plugin header: `Author: Brendigo`, `Plugin URI: https://brendigopay.com`, `Author URI: https://brendigo.com`, `Text Domain: brendigo-pay`; `Contributors: brendigo` u WordPress readmeju, kao traženi identifikator koji mora odgovarati stvarnom WordPress.org računu kod objave.

### Ograničenja

**Nije moguće potvrditi da sve stranice, WordPress administratorski panel i WooCommerce checkout izgledaju 1:1 prema slikama bez stvarne WordPress instalacije, odabranog aktivnog WordPress/WooCommerce predloška i snimaka pri istoj rezoluciji.** Trenutačni CSS je dodatno usklađen, ali nije certificiran kao pixel-perfect. Prikazane referentne slike također sadrže konceptualne kartične forme, dok Brendigo Pay namjerno preusmjerava na hosted checkout pružatelja; vlastita pohrana kartica nije implementirana.
