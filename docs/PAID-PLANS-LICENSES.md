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

## Sigurnost i produkcijski blokatori

- Web-platforma isključivo koristi privatni `storage/data` JSON; WordPress koristi svoju standardnu WordPress bazu za lokalne opcije.
- Sigurni, slučajni licencni i API ključevi nikada nisu prikazani ponovno nakon izdavanja; prijenos po HTTPS-u.
- Registracija domene **nije samostalna dokazana provjera vlasništva**. Ona provjerava samo točan origin i tajni ključ pri instalaciji dodatka. Za live naplatu potrebna je dodatna provjera vlasništva (DNS ili challenge endpoint bez SSRF-a).
- Naplata paketa je ručna (nikad automatski potvrđena). Implementacija pravog Stripe/PayPal/Revolut obračuna pretplate, naplate poreza, izdavanja računa, periodičke obnove i webhooks još je otvorena.
- Dostupni WooCommerce adapteri su isključivo sandbox/test. Ne koristiti s pravim karticama, bez live PSP provjera i vanjskog sigurnosnog audita.

## Granice UX testiranja

Sučelje se usklađuje s referentnim dizajnom, ali nije potvrđena točna vizualna 1:1 podudarnost na pravim WordPress/WooCommerce instalacijama, preglednicima i rezolucijama.
