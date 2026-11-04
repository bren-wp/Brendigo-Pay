# Brendigo Pay 0.8.5 — instalacija i povezivanje

## Potrebni uvjeti

- PHP 8.3+, OpenSSL, cURL, libsodium i mbstring; HTTPS certifikat na javnoj domeni.
- Apache s `mod_rewrite` ili ekvivalentan Nginx routing koji sve nedostajuće rute predaje u `public/index.php`.
- **DocumentRoot mora biti `platform/public`**, a `platform/config.php`, `platform/src` i `platform/storage/data` moraju biti nedostupni preko HTTP-a.
- Sigurne datotečne dozvole za servisni račun te privatni backup i restore proces.
- WordPress + WooCommerce samo ako se koristi trgovina; običan WordPress koristi blokove/kratke kodove.

## Postavljanje weba

1. Prenesite `Brendigo-Pay-platform-0.8.5.zip` na PHP hosting, raspakirajte i podesite DocumentRoot na `platform/public`.
2. Izvan HTTP web-roota pokrenite `php platform/bin/setup.php https://vasa-domena.hr`. Skripta generira lokalni aplikacijski ključ, putanju privatne JSON pohrane i konfiguraciju. **Ne postavljajte postojeći `config.php` preko drugoga.**
3. U privatnom `platform/config.php` dodajte:
   - `admin_user_ids`: identifikatore **provjerenih** administratorskih računa. Nikada ne dodjeljivati administratora na temelju slobodno unesenog e-maila pri registraciji.
   - `legal_operator_name`, `legal_address`, `legal_contact_email`: stvarne provjerene podatke operatera. Stranice se javno otvaraju i bez njih, ali prikazuju upozorenje da dokumentacija nije gotova.
4. Testirajte `/api/v1/health`, `/register`, `/login`, `/pricing`, `/privacy`, `/terms`, `/contact`, `/admin`, te prava pristupa. Bez valjane konfiguracije aplikacija se **namjerno neće automatski pokrenuti** — to sprječava otvaranje nezaštićenog admina ili generiranje predvidljivih tajni.
5. Učitajte ugovorene pružatelje tek nakon testnog onboardinga; ne unosite stvarne tajne dok funkcionalnosti imaju SANDBOX status.

## Povezivanje WordPress dodatka

1. U WordPress adminu prenesite `brendigo-pay-wordpress-0.8.5.zip`, aktivirajte dodatak.
2. Na webu odaberite jedan od tri **plaćena** paketa. Registracija **nije** plaćanje; verificirani operater mora aktivirati pretplatu i datum isteka u admin panelu.
3. U korisničkom panelu otvorite `/sites`, upišite vlastitu HTTPS domenu te kreirajte jednokratnu licencu unutar limita paketa (Start 1, Business 5, Pro 25).
4. U WordPressu pod Brendigo Pay → Postavke unesite URL web-platforme i licencni ključ, aktivirajte i pritisnite **Sinkroniziraj sada**. Provjerite `last_seen_at`, verziju dodatka i status web-domena u oba panela.
5. Korisnički WordPress API token **nije** mobilni token. U slučaju krađe rotirajte licencu; platforma opoziva prethodni pristup.

## Android

- Instalacijski razvojni APK: `Brendigo-Pay-Android-0.8.5-debug.apk`; application ID `com.brendigo.pay.debug`.
- Nepotpisani AAB: `Brendigo-Pay-Android-0.8.5-unsigned.aab`. Za Google Play potrebno je izdavateljevo potpisivanje, Test Track i Play Console review.
- Korisnik može registrirati račun u Androidu, izabrati plaćeni paket **bez automatske aktivacije** i prijaviti se na web istim podacima. Također web-registrirani račun radi u Android aplikaciji.
- Nakon prijave Android čita samo vlastite transakcije, linkove, statuse licenci i konfigurirane integracije iz istog PHP JSON backenda.
- Mobilni token se čuva šifrirano na uređaju; nakon odjave lokalni token se uklanja, a server prima zahtjev za opoziv ako postoji veza.

## Provjere za javno komercijalno puštanje

**Ovo izdanje nije potvrđeno za stvarnu naplatu.** Nedostaju validirane produkcijske transakcije/PSP, potvrde vlasništva domena, automatizirano pretplatničko fakturiranje, neovisni security audit, fizički Android uređajni E2E testovi te konačna pravna i Play Console procjena.

Android 16 (API 36) je target zahtjev za Google Play za nove aplikacije i ažuriranja od 31. kolovoza 2026.: https://support.google.com/googleplay/android-developer/answer/11926878

Google Play Payments policy može zahtijevati Play Billing ili odgovarajuću iznimku/program za plaćene digitalne SaaS funkcije: https://support.google.com/googleplay/android-developer/answer/9858738. Sam odabir paketa **ne** pokreće kupnju niti preusmjerava na plaćanje.

Autentične snimke web UI-a već su u README-u. Android i WordPress screenshotove treba snimiti s pokrenute aplikacije, ne zamijeniti referentnim renderima.
