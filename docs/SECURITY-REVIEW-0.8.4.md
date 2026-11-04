# Brendigo Pay v0.8.4 — ciljano sigurnosno testiranje i preostali rizici

Datum: 2026-11-04. Opseg: Android aplikacija, mobilni merchant API, povezivanje s web/WordPress sustavom i distribucijski artefakti. **Ovo nije neovisni penetration test niti certifikat usklađenosti za produkcijske uplate.**

## Implementirane kontrole

| Zaštita | Mehanizam | Provjera |
|---|---|---|
| Android/TLS | HTTPS jedino; HttpsURLConnection; zabrana redirecta i cleartext prometa | source contract + Android CI |
| Pohrana mobilne sesije | AES-GCM ključ u Android Keystoreu; SharedPreferences nema backup | source contract |
| Prijava | Provjera postojećeg Argon2id hasha; zajednički IP/account limiti kao web | HTTP smoke |
| Životni ciklus tokena | Kriptografski nasumičan mobilni token, SHA256 hash na serveru, 7 dana, logout revokacija | HTTP smoke |
| Razdvojene ovlasti | bp_mobile_ ne može autentificirati bp_test_ WordPress endpoint | HTTP smoke |
| Tenant izolacija | Svi zapisi filtrirani po user_id; vlastiti site_id obavezan pri kreiranju linkova | HTTP smoke |
| Link lifecycle | Server-side provjera plaćenog plana i vlasnika, zapis pod lockom, audit log | HTTP smoke |
| Paket i kvote | Start 1 / Business 5 / Pro 25; samo aktivne licencije omogućuju kreiranje linkova | postojeći license testovi |
| URL kontrola | Mobilni klijent prihvaća HTTPS origin bez userinfo, query, fragmenta ili putanje | source contract |
| Privatnost | Bez prikupljanja kartica i bez analytics SDK-a; samo internet permission | manifest/source contract |
| Android izgradnja | Gradle 9.4.1, AGP 9.2.0, compile/target 36, minSDK 24 | GitHub Actions Android build |

## Otvoreni produkcijski blokatori

- Nisu implementirani niti neovisno verificirani stvarni PSP platni tokovi (Stripe/PayPal/Revolut), refundacije i dispute processing. Android ne obavlja naplatu.
- AAB je bez potpisa izdavatelja; APK je debug-signed. Ne smije ih se predstavljati kao Play produkcijski potpisan release.
- Nisu provedeni uređajni E2E testovi (više API levela, različite dijagonale, TalkBack, locale, offline/recovery, UI screenshot parity).
- Registracija i odabir paketa i dalje ne znače uspješno plaćenu pretplatu; operator ručno verificira uplatu. Billing i owner-of-domain dokaz nedostaju.
- Potreban je javni auth hardening (MFA/step-up, upravljanje aktivnim sesijama, upravljanje kompromitiranim uređajima, čišćenje isteklih tokena, lokalno ograničenje pokušaja, sigurnosna revizija auth sustava).
- Poslužiteljska JSON pohrana zahtijeva opterećenje, disaster recovery i višeposlužiteljski test; Android prikazuje ograničene zadnjih 50 transakcija i linkova.
- Potreban je cjelovit sigurnosni pregled svih starih modula web platforme, WP dodatka i trećih strana; ciljane statičke provjere nisu zamjena za full audit.
- Korisničke slike su referentni dizajn; vizualna 1:1 podudarnost nije potvrđena usporednim snimkama svih uređaja.

## Ciljani dead-code pregled

Novo dodani `MobileApi::login`, `authenticate`, `overview`, `createLink`, `toggleLink` i `logout` imaju eksplicitne rute i pozivatelje. Android `MainActivity` poziva `ApiClient` i `SecureStore` za aktivne prikaze. Neiskorišteni importi i nekorištene konstantne boje uklonjeni su tijekom ovog ciklusa. Preostali legacy dead-code i dependency audit cijelog repozitorija nije završen.

## Izlazni kriteriji za oznaku produkcije

1. Potpuni vanjski end-to-end testovi PSP-a i WordPress/WooCommerce narudžbi.
2. Potpis AAB-a valjanim izdavateljevim upload ključem uz Play App Signing.
3. Android emulator/fizički uređaji, stvarni screenshotovi i 1:1 vizualne usporedbe.
4. Neovisni security i GDPR/PSD2/PCI scope review s izvješćem i otklonjenim nalazima.
5. Operativni nadzor, backup/restore i održivi release/rollback.
