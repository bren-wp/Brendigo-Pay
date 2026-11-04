# Brendigo Pay v0.8.0 — sigurnosni audit izvornog koda

**Datum:** 2026-11-04  
**Opseg:** `platform/src`, `platform/public`, `platform/bin`, `wordpress-plugin/brendigo-pay`, `scripts`, `tests`, GitHub workflowovi i dokumentacija.  
**Metoda:** ručni pregled kritičnih tokova i ciljani offline testovi (PHP lint, endpoint smoke, simulirani gateway, strukturalni static policy). **Nije proveden neovisan penetration test, kompletan SAST/DAST ili audit samih vanjskih pružatelja.**

## Ispravljeno u v0.8.0

| Prioritet | Nalaz | Izmjena | Provjera |
|---|---|---|---|
| Visok (online izlaganje) | Prijavni limit ključan je bio po IP/e-pošti; izmjena e-pošte omogućavala je daljnje pokušaje s istog IP-a | Dodan zaseban limit 30 neuspjelih pokušaja/IP u 15 minuta uz postojeći limit 8 po kombinaciji IP/e-pošta | `platform/tests/smoke.py` vrti izmjenu 30 e-adresa i provjerava blokiranje valjane prijave |
| Srednji | Webhook tijelo ranije se čitalo cijelo prije odbijanja prevelikog zahtjeva | Sva četiri webhook endpointa sada čitaju najviše 1.000.001 bajt, a payload veći od 1 MB odbijaju | Statički test i postojeći provjerni testovi potpisa |\n| Srednji | WordPress API odgovor nije imao maksimalnu veličinu u klijentu | `limit_response_size=131072`, HTTPS i `wp_safe_remote_request` ostaju obvezni | Statički test `tests/security-static.py` |
| Nizak | Zastarjela footer verzija i stari marketinški opis imali su nejasnoće oko produkcije | Usklađena verzija, SANDBOX statusi i jasne napomene | UI/testovi i pregled README-a |

## Postojeće sigurnosne kontrole provjerene u kodu

1. **Autentifikacija:** Argon2id, CSRF na obrascima, HttpOnly/SameSite i Secure sesijski kolačići na HTTPS, rotacija session ID-a nakon prijave.
2. **Tenant izolacija:** API tokeni hashirani i trgovcu pridruženi; poslužitelj provjerava vlasništvo nad vezama i transakcijama; API ključevi nisu izloženi Gutenberg JavaScriptu.
3. **Plaćanja:** hosted checkout bez lokalnih polja kartica; provjere potpisanih Stripe/Revolut webhookova i API potvrdnih odgovora PayPala, potvrda iznosa/valute/identifikatora prije statusa 'paid'; treba E2E test.
4. **Pohrana:** privatni JSON zapisi, atomski rename, vlasnički lockovi za ključne mutacije, Argon2id i libsodium za osjetljive ključeve.
5. **WP/Woo:** nonce/capability za administraciju, escaped/sanitized vrijednosti i `wp_safe_remote_request` s zabranjenim redirectovima; narudžbe preko WC CRUD API-ja.
6. **HTTP:** CSP, no-store, anti-framing, nosniff, HSTS na HTTPS; webhook payload size check i odbijanje nevaljanih potpisa.

## Preostali rizici — **blokatori javne produkcije**

| Ozbiljnost | Nedovršeno | Potrebna aktivnost |
|---|---|---|
| Kritično | Nema end-to-end testa s vlastitim Stripe/PayPal/Revolut sandbox računima i WooCommerce instalacijom | Izvršiti stvarne sandbox plaćanja/otkaze/povrate, webhook reorder/replay i idempotency testove |
| Kritično | Nema prodajnog/partner onboarding, regulatorne provjere ili PCI/PSD2/SCA procjene | Zadržati live isključen, pravni i vanjski sigurnosni pregled prije produkcije |
| Visoko | Nema MFA, potvrde e-pošte, oporavka računa, upravljanja sesijama | Implementirati i integracijski ispitati prije javne registracije |
| Visoko | JSON + cron ne osigurava atomsku dvostruku poslovnu knjigu, distribuiranu konkurentnost ni redrive/reconciliation | Transakcijski outbox, trajni događaji, ponovna provjera statusa i obrana od pada diska |
| Srednje | API token je bearer; 'origin' ograničava URL za povrat, ali **nije** dokaz pozivatelja | Rotacija ključeva, opcionalni potpis zahtjeva, ograničenja rate/IP i nadzor zlouporabe; nikad ne opisivati origin kao kriptografsku autentifikaciju |
| Srednje | Datotečni pristupi zavise o konfiguraciji poslužitelja, symlink/tampering u lokalnom filesystemu izvan modela prijetnji | Poseban OS korisnik, izvan web-roota, permisije i sigurnosne kopije sa zasebnim ključem |
| Srednje | Nema neovisnih WPCS/PHPCS/CodeQL/Semgrep rezultata niti dinamičkog penetracijskog testa | Dodati provjerene alate i ručni pregled svih nalaza, uključujući WooCommerce HPOS i Blocks |
| Nisko | Stvarne WordPress snimke i finalni vizualni 1:1 audit nisu potvrđeni | Pregled stvarnih screenshotova s jednakim podacima i rezolucijom |

## Što ovaj audit ne tvrdi

Ne tvrdi `100% sigurno`, `vojna enkripcija`, `PCI certificirano`, `PSD2 usklađeno`, `WordPress.org odobreno` ili spremnost za stvarnu naplatu. Testni sandbox status nije isto što i uspješno provedena uplata.
