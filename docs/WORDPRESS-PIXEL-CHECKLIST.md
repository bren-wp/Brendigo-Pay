# Brendigo Pay — kontrolni popis 1:1 za tri WordPress zaslona

**Reference:** `design/references/01-woocommerce-checkout.jpeg`, `02-gutenberg.jpeg`, `03-wp-admin.jpeg`  
**Polazno izdanje:** v0.7.2 SANDBOX  
**Izvori:** `wordpress-plugin/brendigo-pay/`  
**Status:** kontrolni popis i mjerni okvir, **nije potvrđena 1:1 podudarnost**.

## Jedinstveni protokol mjerenja

- [ ] Fotografije u `design/references` uspoređivati sa **stvarnim snimkama** WordPress stranice pri **1448 × 1086 px**, zoom **100%**, bez automatskog povećanja fontova. Referentne slike imaju upravo 1448 × 1086 px.
- [ ] Evidentirati WordPress, WooCommerce, temu, PHP, preglednik, OS, uređaj, DPR, zoom i jezik. Preuzeti screenshot nakon učitavanja fontova i sadržaja, a ne tijekom renderiranja.
- [ ] Osigurati identične demonstracijske podatke, jer će različit naziv proizvoda / iznos / poreski izračun promijeniti geometriju.
- [ ] Testirati 1448 × 1086, tablet 768 px i mobitel 390 px. Tablet/mobitel nemaju zasebne vizualne reference: potvrđujemo funkcionalan responsive raspored, a ne doslovnu desktop 1:1 geometriju.
- [ ] Snimiti zasebno: cijeli ekran, glavni panel, pojedinu karticu i fokus/odabrano stanje. Nikada ne koristiti referentni JPEG kao background produkcijske stranice.
- [ ] Izmjeriti pozicije `x, y, širina, visina`, granice komponenti, razmake, font-size/weight/line-height i boje. Usporediti preko `scripts/visual_audit_wp.py`.
- [ ] Izvještaj s brojčanim razlikama i snimkama stvarnog programa dodati u PR. I kod malog MAE, pregledati interakcije i ponašanje forme; automatizirana metrika nije potvrda kvalitete.

**Mjerni ROI prozori ispod orijentacijske su koordinate očitane iz dostavljenih referenci**, ne verificirane dimenzije aktualne aplikacije.

## Ekran 01 — WooCommerce checkout

Referenca: `01-woocommerce-checkout.jpeg`.

| Područje | Približni ROI [x1,y1,x2,y2] | Što mora biti podudarno |
|---|---|---|
| Zaglavlje | [0,0,1448,60] | logo/wordmark, separator, WooCommerce oznaka, Pomoć |
| Koraci | [385,60,1040,111] | tri koraka, krugovi, razmak i linije |
| Podaci o kupcu | [50,185,797,574] | dvije kolone, redovi polja, padding i rubovi |
| Načini plaćanja | [50,582,798,1034] | visine radio redova, logo, aktivni okvir i CTA |
| Sažetak | [819,185,1366,756] | redovi proizvoda, porez, dostava, ukupno |
| Donji desni stupac | [819,760,1365,1016] | dodatne informacije i glavni gumb |

### Označi nakon provjere

- [ ] Zaglavlje ne prekriva checkout; logotip i horizontalni razdjelnik imaju iste optičke margine.
- [ ] Koraci `Košarica → Plaćanje i naplata → Potvrda narudžbe` usklađeni su sa stvarnim WooCommerce tijekom; nikada se ne smije lažno prikazati dovršeni korak.
- [ ] Širina dviju kolona, razmak između njih, bočna margina i početak obrazaca vizualno odgovaraju referenci.
- [ ] Billing i shipping obrasci koriste **WooCommerce** stvarna polja, validaciju, obračun poreza i dostupne države. Nemoj duplicirati polja samo radi slike.
- [ ] Label, required zvjezdice, placeholder, fokusni okvir, disabled i error stanja imaju jednake dimenzije i kontraste.
- [ ] Brendigo Pay kartica sadrži stvarni logo, naslov, kratak opis, oznaku **SANDBOX**, radio opcije za stvarno dostupne testne pružatelje.
- [ ] Stripe / PayPal / Revolut imaju ujednačene redove (radio, oznaka, naslov, opis, desni indikator), fokus i odabrano stanje, bez probijanja teksta na 390 px.
- [ ] Jedan provider odabir mora biti prenesen preko oba WooCommerce sustava: klasični `bp_provider` i Blocks `paymentMethodData.bp_provider`; status narudžbe nije određen lokalnim tekstom gumba.
- [ ] Ukloniti testne hardkodirane cijene: stvarni zbroj, popust, PDV, dostava i dostupni načini plaćanja moraju odgovarati WooCommerce narudžbi.
- [ ] Ne prikazivati lokalna polja broja kartice/CVC ili tvrditi da je plaćanje dovršeno prije pouzdane provjere kod pružatelja; kartice obrađuju pružatelji.
- [ ] Veličina i nagib glavnog gumba `Plati narudžbu`, tipografija iznosa, rubovi, sjena i focus-visible izgled odgovaraju referenci.
- [ ] Test: bez WooCommercea, Classic Checkout, Checkout Block, mobilni checkout, kartična pogreška, otkazano plaćanje, povrat sa stranice pružatelja, duplicirani webhook.

**Kod:** `assets/public.css`, `assets/woocommerce-block.js`, `includes/class-bp-gateway.php`, `tests/woocommerce-gateway.php`.

**Granica odgovornosti:** header/stepper, WooCommerce billing i product summary mogu zahtijevati prilagodbu aktivne teme ili dodatni checkout template. Platni dodatak ne smije globalno mijenjati svaku trgovinu niti lažno prikazivati referentne poslovne podatke.

## Ekran 02 — Gutenberg editor

Referenca: `02-gutenberg.jpeg`.

| Područje | Približni ROI | Što mora biti podudarno |
|---|---|---|
| WordPress gornja traka | [0,0,1448,102] | native editor chrome, alati, ažuriranje |
| Umetanje blokova | [147,102,446,1086] | blokovi/uzorci/mediji, Brendigo kategorija |
| Canvas | [446,102,1082,1086] | širina sadržaja, kartica, QR, spacing |
| Aktivna naplatna kartica | [475,432,1057,878] | obris i ručke, 2 stupca, CTA, QR |
| Desni inspector | [1082,41,1448,1086] | paneli, select/input/switch, poredak |

### Označi nakon provjere

- [ ] Inserter prikazuje **Naplata, Platni link, Cjenik kartica, QR kod**; provjeriti funkcionalno značenje postojećih 4 registrirana bloka, ne samo nazive.
- [ ] Preview ima dosljedan logo, lijevo vizual proizvoda, desno naslov, opis, **točan iznos s Brendigo Pay platforme**, pružatelja, CTA i QR.
- [ ] Nepovezani API, nedostajući ili neaktivan link i pogreška učitavanja moraju prikazati jasan status; ne prikazivati izmišljenu cijenu ni lažan uspjeh.
- [ ] Inspector sadrži: izbor stvarnog naplatnog linka, naziv/opis kao zaključani podaci, stil gumba, tekst gumba, prikaz ikone, QR toggle/tekst i prikaz uspjeha (samo ako je stvarno implementiran).
- [ ] Uređivač ne smije dopustiti promjenu autoritativnog iznosa kroz atribut bloka; iznos potvrđuje poslužitelj u trenutku naplate.
- [ ] Tipografija: naslov, iznos, opis, CTA, podnaslovi sekcija i pomoćni tekst slijede referentni redoslijed i vizualnu hijerarhiju.
- [ ] Fokus, odabir bloka i toolbar ne smiju mijenjati vanjski layout; outline i handle ostaju vidljivi.
- [ ] QR preview ima vlastiti jasan placeholder dok stvarna poveznica nije dostupna; objavljena stranica mora generirati kod koji vodi na **stvarni HTTPS URL**, bez simuliranja skeniranja.
- [ ] Editor preview, frontend i objavljena verzija moraju koristiti jednake fontove, razmake i izgled gumba; provjeriti da nema neželjenih stilova aktivne WP teme.
- [ ] Responsive test: veliki desktop, 768 px, uski editor na 390 px. Inspector može biti u drugoj WP navigaciji, ne forsirati desktop raspored.
- [ ] Pozitivni test: odabir poveznice, promjena teksta/stila, toggle QR, spremanje stranice, ponovno otvaranje, front-end prikaz. Negativni test: brisanje linka ili promjena njegove cijene na platformi.

**Kod:** `assets/block.js`, `assets/editor.css`, `assets/public.css`, `includes/class-bp-blocks.php`.

**Granica odgovornosti:** WP toolbar, inserter i desni inspector izvana su WordPressovo sučelje; dodatak treba stilizirati vlastite kontrole, a ne preuzeti cijeli editor.

## Ekran 03 — WordPress administratorski panel

Referenca: `03-wp-admin.jpeg`.

| Područje | Približni ROI | Što mora biti podudarno |
|---|---|---|
| WP bočni izbornik | [0,0,166,1086] | nativna administracija |
| Brendirano zaglavlje | [193,39,1409,171] | logo, slogan, gradient, dekoracija |
| Tabovi | [193,229,1394,284] | razmaci i aktivna sekcija |
| Povezivanje | [193,297,905,410] | status, ikona, opis, chips |
| Okruženje | [914,297,1395,410] | SANDBOX aktivan, produkcija zaključana |
| API podaci | [193,423,1395,550] | API URL/ključ i stvarni opis webhooka |
| Pružatelji | [193,611,1395,749] | tri jednake kartice i stvarni statusi |
| Donje funkcije | [193,757,1395,982] | linkovi, QR, sigurnost, CTA |

### Označi nakon provjere

- [ ] Autor Brendigo, Plugin URI `https://brendigopay.com`, Author URI `https://brendigo.com`, Contributors `brendigo`, text domain `brendigo-pay`, WordPress GPL-kompatibilni metapodaci.
- [ ] Brendirani header: logo i wordmark isti kao na webu, bez generičkog `B` znaka; autorska prava i branding dosljedni.
- [ ] Tabovi: `Opće postavke`, `Pružatelji plaćanja`, `Platni linkovi`, `QR kodovi`, `Sigurnost`, `Dnevnici` — samo navigacija do stvarnih funkcionalnih sekcija.
- [ ] Status `Povezano i spremno` prikazivati **samo nakon provjere API-ja i pružatelja**; unos URL-a/ključa znači konfigurirano, ne nužno povezano.
- [ ] `Sandbox` aktivan, `Produkcija` onemogućena i jasno označena, sve dok stvarne integracije i regulatorni uvjeti nisu ispunjeni.
- [ ] API ključ je masked, sigurno spremljen, ne vraća se u HTML. Kopiranje javnog URL-a nema pristup skrivenom ključu.
- [ ] Webhook URL odgovara stvarno implementiranom endpointu. Ako WP ne prima webhook, polje mora biti drugačije nazvano (trenutačno `Adresa platforme`); ne glumiti endpoint iz reference.
- [ ] Stripe/PayPal/Revolut kartice prikazuju stvarnu konfiguraciju/status s platforme, ne stalne zelene `Povezano` oznake.
- [ ] Svaki toggle mora imati stvarnu radnju, sanaciju, nonce i capability provjeru ili biti označen kao informativan/disabled.
- [ ] Naplatni linkovi i QR su stvarne poveznice korisnikova računa; kopiranje shortcoda radi i na mobilnom uređaju; nema tuđih podataka.
- [ ] Logovi su filtrirani i redigirani (bez tokena i kartičnih podataka), samo za ovlaštenog administratora.
- [ ] Tipografija, kartice, margine, gumbi i zaključni status imaju jednake osnovne dimenzije na 1448 px; WP sidebar se ne mijenja globalnim CSS-om.
- [ ] Test: nema WooCommercea, ima WooCommerce, loš ključ, offline API, više trgovaca, reset/postavke, desktop/tablet/mobitel, WC HPOS i Classic/Blocks.

**Kod:** `views/admin-settings.php`, `assets/admin.css`, `assets/admin.js`, `includes/class-bp-settings.php`, `includes/class-bp-api.php`.

## Zajednički prihvatni kriteriji

- [ ] Funkcionalnost **prije** kozmetike: svaki klik, izbor i obrazac ima smisleno ponašanje ili točnu disabled poruku.
- [ ] Nema vanjskog CDN-a, tajnih ključeva u JavaScriptu, spremanja broja kartice ni izmišljenih korisničkih recenzija/rezultata.
- [ ] Kontrast, keyboard navigation, `focus-visible`, `aria-label` i smanjeni pokreti usklađeni s pristupačnošću.
- [ ] Desktop: usporediti svaku regiju i dokumentirati koordinate odstupanja. Mobile: testirati navigaciju i forme bez horizontalnog scrolla.
- [ ] Kod pregledati kroz `php -l`, `bash tests/run-all.sh`, WP Coding Standards/PHPCS te stvarnu WP+WooCommerce instalaciju.
- [ ] U PR uključiti realne screenshotove i tablicu prije/poslije s razlikama. **Ne označavati 1:1 kao završeno prije vizualne provjere.**

## Lokalna mjerenja (bez generiranja slika)

```bash
python3 scripts/visual_audit_wp.py woocommerce /putanja/stvarni-checkout.png
python3 scripts/visual_audit_wp.py gutenberg /putanja/stvarni-gutenberg.png
python3 scripts/visual_audit_wp.py wp-admin /putanja/stvarni-admin.png
```

Alat zahtijeva `Pillow`, **ne skalira** slike različitih dimenzija i daje MAE po regiji. JPEG kompresija, različiti fontovi, datum, status, placeholder i browser chrome utječu na brojke. Rezultati su dijagnostika, **ne potvrda 1:1**.
