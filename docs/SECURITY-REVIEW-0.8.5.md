# Sigurnost i dead code — ciljane izmjene 0.8.5

**Datum:** 4. studenoga 2026. **Opseg:** registracija, mobilni merchant API, javne pravne stranice, sinkronizacija integracija, WordPress/GitHub release. Ovo nije vanjski penetracijski test, potpuni legacy dead-code audit niti regulatorna certifikacija.

## Izvedene kontrole

- Web i Android registracija pozivaju isti `Accounts::register`, uz centraliziran email indeks i Argon2id hash lozinke. Rate-limit 6 registracijskih zahtjeva po IP adresi / 15 min.
- Prijava na webu i Androidu provjerava istu bazu računa, čime je omogućena prijava u oba smjera bez kopiranja osobnih podataka.
- Mobilni `bp_mobile_` token ne može pristupiti WordPress `bp_test_` API-ju ili administratorskom panelu. Token je hashiran u JSON pohrani, s istekom sedam dana i revokacijom.
- Android Keystore lokalno šifrira mobilni token; nema backup-a, cleartext HTTP-a, izravne pohrane kartica ni SDK-a za praćenje.
- Pregled naplate, transakcija, linkova, domena i postavki platnih sustava filtriran je po `user_id`. Pokus tuđeg računa nad linkom testira se u HTTP smoke suite.
- Registracija ne daje aktivan plaćeni paket, ne aktivira licencu i ne generira WordPress token.
- Novi pravni sadržaj i poslovni podaci konfiguracije prolaze kroz escaping u izlazu; bez unaprijed izmišljenog OIB-a, adrese ili pravnog subjekta.
- Javna stranica integracija razdvaja postojeće sandbox adaptere i buduće domaće pružatelje; ne pruža neautoriziranu ili lažnu naplatu.

## Preostali rizici

1. Produkcijske PSP integracije, ovjera webhooks i bankovni tokeni moraju proći testne račune, različite pokušaje, naknadne autorizacije, webhook replay i provjere stanja.
2. Interno zasnovana JSON baza nije dokazana pod produkcijskim opterećenjem / višestrukim instancama. Potrebna je revizija atomskih transakcija, backup-a i oporavka.
3. Operater mora popuniti pravne podatke, politiku retention/deletion i identitet voditelja obrade prije javnog lansiranja.
4. Android paket isporučuje debug APK i nepotpisani AAB, ne potpisani produkcijski Play paket.
5. Nije proveden potpuni UI vizualni diff za sve reference i stvarne rezolucije, niti puni WordPress i Android E2E na fizičkim uređajima.
6. Google Play policy, Data safety, App access, Financial features i potencijalna Play Billing obveza zahtijevaju zasebnu provjeru.
7. Registracija nema verifikaciju e-mail adrese i MFA; dodati prije produkcije. Rate-limit ne zamjenjuje zaštitu od botova i DoS-a.

## Dead-code scope

Nove klase `Accounts`, `MobileApi` i `LegalPages` imaju eksplicitne rute/pozive. Android registration UI poziva novi endpoint. Novi CSS razredi koriste se u javnim pravnim stranama i footeru. To je **ciljana** revizija dosega novih promjena; potpuna dinamička pokrivenost i dead-code čišćenje stare platforme nije dovršeno.

## Test gates

PHP lint, HTTP smoke registracija Android↔web, web session cookies/CSRF, licence i kvote, cross-merchant read/write isolation, WooCommerce gateway/bootstrap, JS syntax, statički security contract, Android Gradle 9 izgradnja APK/AAB. Izlaz tih testova nije dokaz sigurnosti stvarnog platnog pružatelja.
