# Brendigo Pay 0.8.8 — postavljanje i povezivanje

Brendigo Pay povezuje javnu web-platformu, korisnički i administracijski panel, WordPress dodatak i Android aplikaciju. **Trenutačna verzija radi isključivo u sandbox/testnom okruženju.**

## Preduvjeti za web

Potreban je hosting koji podržava Brendigo Pay aplikaciju, pouzdan HTTPS certifikat, PHP 8.3 ili noviji i potrebna standardna PHP proširenja. Administrator hostinga mora dovršiti inicijalnu konfiguraciju i odrediti ovlaštene administratore. **Samo raspakiravanje ZIP-a nije dovoljno za sigurnu javnu objavu bez dovršetka postavljanja.**

Prije komercijalnog lansiranja potrebno je unijeti stvarni pravni identitet operatera, poslovnu adresu i službeni kontakt, potvrditi dokumente o privatnosti te proći sigurnosnu i integracijsku provjeru.

## Korisnički račun i paket

1. Otvorite Brendigo Pay web-platformu preko HTTPS-a i registrirajte poslovni račun.
2. Odaberite **Start (1 domena)**, **Business (5)** ili **Pro (25)** — nema besplatnog paketa.
3. Ovlašteni administrator potvrđuje uplatu i aktivira pretplatu za odgovarajuće razdoblje.
4. U korisničkom panelu otvorite **Web-stranice**, unesite svoju punu HTTPS domenu i izradite licencu.

## WordPress i WooCommerce

1. U WordPressu otvorite **Dodaci → Dodaj novi → Prenesi dodatak**.
2. Instalirajte `brendigo-pay-wordpress-0.8.8.zip` i aktivirajte ga.
3. Pod **Brendigo Pay → Postavke** spremite HTTPS URL Brendigo Pay platforme.
4. Upišite domenski licencni ključ iz korisničkog panela i odaberite Aktiviraj licenciju.
5. Kliknite **Sinkroniziraj sada**; status na webu i u WordPressu treba se podudarati.
6. U WordPressu dodajte Gutenberg blok za naplatu ili, za WooCommerce, uključite Brendigo Pay Sandbox u postavkama plaćanja.
7. Odaberite vlastitog testnog platnog pružatelja i provjerite testnu narudžbu.

Korisničke upute: [WordPress](https://brendigopay.com/docs/wordpress), [WooCommerce](https://brendigopay.com/docs/woocommerce), [QR kodovi](https://brendigopay.com/docs/qr) i [Rješavanje poteškoća](https://brendigopay.com/docs/troubleshooting).

## Android

1. Instalirajte **Brendigo-Pay-Android-0.8.8-debug.apk** za testiranje.
2. Unesite HTTPS adresu Brendigo Pay platforme.
3. Prijavite se istom e-adresom i lozinkom koje koristite na webu ili registrirajte novi račun.
4. Pregledajte vlastite linkove, testne transakcije, domene, licence i konfigurirane integracije.
5. Iz izbornika korisničkog računa otvorite pomoć i dokumentaciju.

**AAB u izdanju nije potpisan za Google Play.** Potrebni su izdavateljev upload ključ, verifikacija na uređajima i Play Console odobrenje. Treba zasebno procijeniti Google Play pravila za prodaju digitalnih usluga.

## Obvezne provjere prije produkcije

- Potpuna prijava u oba smjera i zabrana uvida u tuđe korisničke podatke.
- Izvješća i licencije usklađene na webu, Androidu i WordPressu.
- WordPress/WooCommerce Classic i Blocks checkout testovi s isteklog, valjanom i opozvanom licencom.
- Pružatelji testirani prema službenim uputama, uz pozitivan, negativan i prekinut scenarij te pouzdanu potvrdu konačnog statusa.
- Provjerena pravna dokumentacija, sigurnosna revizija, stvarni screenshotovi i potpisana Android distribucija.

**Brendigo Pay nije financijska institucija, a trenutačno izdanje ne omogućuje stvarno terećenje kupaca.**
