# Paketni model i licencije — Brendigo Pay v0.8.2

## Paketi (isključivo plaćeni)

| Plan | Cijena mjesečno (prijedlog) | Broj domena |
|---|---:|---:|
| Start | 19,90 € | 1 |
| Business | 49,90 € | 5 |
| Pro | 99,90 € | 25 |

Ovo je prijedlog komercijalnog cjenika. U ovoj verziji **nema automatske naplate pretplate**. Registracija računa ne daje besplatan pristup; plaćenu pretplatu nakon neovisne provjere uplate potvrđuje jedino administrator sustava.

## Administratorski panel

- Adresa: `/admin`, za prijavljene korisnike čiji se ID nalazi u privatnom polju `admin_user_ids` u `platform/config.php`.
- Prvi administrator se ne stvara automatski. Registrirajte osobni administratorski račun, provjerite ID iz privatne JSON evidencije `users`, zatim taj ID ručno upišite u `config.php` na poslužitelju. Tu datoteku nikada ne commitajte.
- Admin vidi registrirane trgovce, tražene pakete, aktivne pretplate i povezane domene.
- Nakon potvrde stvarne uplate označava potvrdu i zadaje paket i datum isteka. Upisuje se JSON `entitlements/<merchant-id>.json` (ne u bazu).
- Smanjenje paketa ispod trenutačnog broja aktivnih domena odbija se.

## Korisnički panel

- Tijekom registracije korisnik bira jednu od tri plaćene razine; upis domene je neobvezan dok je plan u čekanju.
- Nakon potvrde aktivnog paketa otvara `/sites`, dodaje HTTPS domenu bez putanje, dobiva jedan ključ u obliku `bp_lic_<64 hexadecimal characters>`.
- Ključ se prikazuje **samo jednom**. JSON pohrana sadrži SHA-256 hash umjesto originala.
- Broj domena kontrolira se pod zaključavanjem; stranice izbrisane od korisnika ne troše kvotu.
- Rotiranje ključa ili uklanjanje web-stranice opoziva prethodni API token. Istek ili suspenzija plaćenog paketa blokira API pristup.

## WordPress aktivacija i sinkronizacija

1. Na WordPressu instalirati ZIP dodatka i otvoriti **Brendigo Pay → Postavke**.
2. Spremiti HTTPS adresu platforme, na kojoj korisnik ima aktivni plaćeni paket.
3. Na Brendigo Pay platformi `/sites` upisati identičan origin kao `home_url()` WordPressa, npr. `https://trgovina.hr` (bez `/wp` putanje).
4. Ključ `bp_lic_...` unijeti u WordPress aktivacijski obrazac. Plugin po HTTPS-u šalje licenciju, kanonski origin i lokalni 128-bit ID instalacije na `POST /api/v1/license/activate`.
5. Platforma provjerava origin, aktivan plaćeni plan, zapis licencije i dosljednost ID-ja instalacije. Vraća `bp_test_...` token vezan uz `site_id` i `user_id`.
6. WordPress čuva samo šifrirani API token u standardnom WP options sustavu. API `/api/v1/me` prikazuje paket, site ID i maksimalni broj domena; podaci o platnim poveznicama filtriraju se po `site_id`.

## Sinkronizacija instaliranog dodatka (v0.8.3)

Nakon aktivacije licencije WordPress automatski obavlja provjeru `POST /api/v1/sites/sync`. Zahtjev uključuje URL domene (origin), trajni ID instalacije, verzije WordPressa, WooCommercea i dodatka. API zahtijeva važeći site-scoped bearer token i validira hash registrirane instalacije; verzije su strogo ograničene i nikad se ne smatraju potvrdom plaćanja.

WordPress zakazuje WP-Cron sinkronizaciju jednom na sat. Administracija prikazuje zadnji kontakt, aktivni Start/Business/Pro paket, iskorišteni limit domena i datum isteka. Ako stanje nije potvrđeno u zadnja **2 sata**, ili posljednja provjera nije uspjela, WooCommerce Classic/Blocks ne nude Brendigo Pay. Server dodatno provjerava plan i licenciju na svakom API zahtjevu, neovisno o lokalnoj provjeri. `GET /api/v1/sites/status` vraća samo podatke autorizirane web-stranice.

Administrator može suspendirati račun u `/admin`; server odmah blokira sve API tokene tog računa. WP sučelje će to prikazati pri sljedećoj provjeri. WP-Cron nije pouzdan periodični scheduler na webovima bez posjeta: za produkciju treba konfigurirati stvarni sistemski cron/monitoring.

## Sigurnost i produkcijski blokatori

- Web-platforma isključivo koristi privatni `storage/data` JSON; WordPress koristi svoju standardnu WordPress bazu za lokalne opcije.
- Sigurni, slučajni licencni i API ključevi nikada nisu prikazani ponovno nakon izdavanja; prijenos po HTTPS-u.
- Registracija domene **nije samostalna dokazana provjera vlasništva**. Ona provjerava samo točan origin i tajni ključ pri instalaciji dodatka. Za live naplatu potrebna je dodatna provjera vlasništva (DNS ili challenge endpoint bez SSRF-a).
- Naplata paketa je ručna (nikad automatski potvrđena). Implementacija pravog Stripe/PayPal/Revolut obračuna pretplate, naplate poreza, izdavanja računa, periodičke obnove i webhooks još je otvorena.
- Dostupni WooCommerce adapteri su isključivo sandbox/test. Ne koristiti s pravim karticama, bez live PSP provjera i vanjskog sigurnosnog audita.

## Granice UX testiranja

Sučelje se usklađuje s referentnim dizajnom, ali nije potvrđena točna vizualna 1:1 podudarnost na pravim WordPress/WooCommerce instalacijama, preglednicima i rezolucijama.


## Android pregled i ista licencna pravila (v0.8.4)

Android se prijavljuje korisničkim računom i dobiva poseban mobilni token s trajanjem sedam dana. Nije dopušteno aktivirati WP dodatak mobilnim tokenom; WordPress i dalje ima ključ vezan uz konkretnu domenu i instalaciju. Android prikazuje **isti** broj iskorištenih domenskih mjesta (1 / 5 / 25) i aktivni paket kao korisnički panel, te može kreirati/aktivirati/deaktivirati vlastite testne linkove za već aktiviranu domenu. Neaktivni ili suspendirani paket na serveru odbija nove promjene linkova. Detalji API-ja u `docs/API.md`.
