## 0.8.11 — 2026-11-09 (SANDBOX)

- Dorade osam prioritetnih vizualnih regija: oba CTA bannera, hero i paketi cjenika, bočna navigacija, naslov Transakcija, modal za novi link te redoslijed sadržaja checkouta.
- Naslovnica prikazuje CTA prije dopunske sekcije, kao u referentnom viewportu. Oba CTA gumba vode na stvarne, postojeće rute.
- Vizualne ilustracije izrađene su CSS-om i postojećim brendingom; bez umetanja referentnog screenshot-a u UI i bez generiranih korisničkih transakcija.
- Checkout ostaje hosted kod službenog pružatelja; nema lokalnog spremanja kartičnih brojeva ni CVC-a. U neaktivnom sandboxu izbor je onemogućen.
- Dodatna provjera točne prisutnosti CTA-a u desktop viewportu, preglednog modala i sažetka prije nastavka na naplatu.
- **Pixel-perfect cilj 1:1 / 0% još nije ostvaren.** Referentni rezultati se moraju iskreno izvještavati; 3 WordPress snimke još nisu snimljene, a za Android ne postoji zasebna referenca.
- Ovo izdanje nije certificirano za produkcijsku platnu obradu. Windows aplikacija nije dio repozitorija.

## 0.8.10 — 2026-11-09 (SANDBOX)

- Jedinstvena provjera isteka platnih poveznica u webu, WordPress API-ju i Androidu; zabranjena reaktivacija isteklih poveznica, a stanje u svim panelima je usklađeno.
- WordPress uninstall sada čisti vlastite API vjerodajnice, registraciju instalacije, sinkronizacijske statuse i cron, bez brisanja WooCommerce narudžbi.
- Uklonjena zastarjela CSS pravila `.bp-dashboard-grid` nakon prelaska na `.bp-dashboard-layout`.
- Uveden nedestruktivni CI inventar PHP/JS/CSS mrtvog koda i sigurnosnih primitiva; odvojeno se štite dinamički WordPress/WooCommerce callbackovi.
- Objavljen sigurnosni i vizualni audit od 9. studenoga te regionalne metrike odstupanja. Cilj manje od 10 % **još nije ostvaren**.
- Nema Windows implementacije u repozitoriju. SANDBOX način rada ostaje obvezan; nema produkcijske naplate bez zasebnog odobrenja.

## 0.8.9 — 2026-11-07 (SANDBOX)

- Nastavljena pixel-perfect geometrija korisničke nadzorne ploče, tablice transakcija, detaljnog panela i javnog checkouta prema referencama.
- Modal za nove platne poveznice ima stvarni odabir preferiranog sandbox pružatelja i 7/14/30-dnevni rok valjanosti s poslužiteljskom provjerom pri naplati; nepovezani pružatelji ne mogu se odabrati.
- Checkout prikazuje istinite, nedostupne opcije kada merchant nije konfigurirao pružatelja. Kartični podaci i dalje se ne prikupljaju lokalno.
- Početna stranica i mobilni rasporedi vizualno su dorađeni bez izmišljanja korisničkih recenzija, prihoda ili aktivnih integracija.
- WordPress dodatak i dalje ima sedam zasebnih administratorskih podstranica, ne jednu stranicu koja skrola među sekcijama.
- Automatizirana provjera deset referenci sada izričito bilježi prag **ispod 10 %** i neprolazne ili neizmjerene zaslone; ručni režim može zahtijevati strogi prolaz.
- **Cilj ispod 10 % još nije postignut.** Stvarne WordPress/Gutenberg/WooCommerce snimke te produkcijska platna certifikacija ostaju otvoreni. Izdanje je SANDBOX.

## 0.8.8 — 2026-11-07 (SANDBOX)

- WordPress administracija: sedam odvojenih WordPress podstranica u lijevom izborniku umjesto jedne duge stranice s gornjim tabovima. Potvrđeno izolacijskim regresijskim testovima.
- Stranice i paneli: ciljana 1:1 prilagodba visine javnog zaglavlja, početnog hero prikaza, cjenika, usporedne tablice, FAQ-a i modala za novi platni link.
- Android: responzivne KPI kartice, kontrast/brending i pristupačne oznake navigacije.
- WordPress: uređeni admin, Gutenberg i WooCommerce stilovi i dodan lokalni dnevnik sinkronizacije bez pohrane tajni.
- GitHub Actions: automatizirano snimanje šest stvarnih web prikaza i dijagnostička usporedba svih deset referenci s odvojenim statusima nesnimljenih WordPress zaslona.
- Naplatni sustavi ostaju SANDBOX; produkcijska certifikacija i potpuna pixel-perfect 1:1 podudarnost nisu potvrđene.

## 0.8.7 — 2026-11-07 (SANDBOX)

- Usklađeni vizualni tokeni i izgled naslovnice, cjenika, korisničke ploče, platnog dijaloga i sandbox checkouta sa slikovnim referencama.
- Broj povezanih pružatelja i njihovi statusi sada odražavaju konfiguraciju prijavljenog trgovca, bez lažnog prikaza tri povezana pružatelja za svaki račun.
- Iz korisničkih poruka uklonjeni su nepotrebni detalji o internoj pohrani podataka.
- Dodane strukturne UI provjere i usklađene verzije weba, WordPress dodatka, Androida, testova i distribucijskih paketa.
- Izdanje ostaje SANDBOX: 1:1 vizualna podudarnost svih ekrana i produkcijski PSP E2E još nisu potvrđeni. Android debug APK nije Play release, a AAB je nepotpisan.

## 0.8.6 — 2026-11-04 (SANDBOX)

- 34 zasebnih korisničkih vodiča i podstranice Mogućnosti, Integracije i Dokumentacija; ukupno 37 novih URL-ova uključujući tri indeksa.
- Responzivan navigacijski prikaz s detaljnim koracima za registraciju, licence, WordPress, WooCommerce, Android, QR kodove, platne linkove, potvrde i rješavanje poteškoća.
- Transparentno razdvojeni Stripe/PayPal/Revolut sandbox načini od planiranih Monri/CorvusPay integracija i ručnih WooCommerce BACS/COD metoda.
- U dokumentaciji je stvarna fotografija lokalno pokrenutog web korisničkog panela; nije tvrdnja o potpunoj 1:1 vizualnoj podudarnosti.
- Android korisnički panel i WordPress administrator imaju izravne poveznice na odgovarajuće vodiče i podršku.
- Ispravljen nedostatak adb alata u Android emulator screenshot CI radnom toku, s dodatnom provjerom da je otvoren stvarni ekran registracije.
- Osvježeni marketing README, WordPress opis, web/Android testovi i upute za postavljanje. Sanbox bez stvarnog terećenja.

## 0.8.5 — 2026-11-04 (SANDBOX)

- Jedna centralizirana registracija za web i Android preko \`Accounts::register\`, Argon2id pohrana, jedinstven e-mail indeks i rate-limit; prijava u oba smjera.
- Nativan Android obrazac registracije s odabirom plaćenog paketa Start/Business/Pro bez automatske aktivacije ili pristupa drugim računima.
- Android sada prikazuje statuse vlastitih Stripe/PayPal/Revolut sandbox integracija uz dosadašnje linkove, transakcije i licence.
- Novi responzivni footer i javne stranice Privatnost, Uvjeti, Kolačići, Povrati, Kontakt, O nama, Sigurnost i Načini plaćanja, s Built with Brendigo.
- Razrađen katalog domaćih/regionalnih pružatelja i službena dokumentacija. Monri i CorvusPay nisu aktivirani bez testnih računa, ovjere i E2E.
- Upute za instalaciju weba, WP dodatka i Androida te ciljani sigurnosni/dead-code pregled; sve u sandboxu.
- Android Gradle/AGP 9.x, API 36 i paket 0.8.5; CI priprema debug APK i nepotpisani release AAB.

## 0.8.4 — 2026-11-04 (SANDBOX)

- Nova nativna Android aplikacija u /android: prijava, pregled stvarnih podataka trgovca, transakcije, platni linkovi, licence i račun.
- Android Keystore AES-GCM, HTTPS bez redirecta/cleartext prometa, izvanjski browser za web panel, bez SDK-a za analitiku.
- Android korisnički panel: stvaranje, kopiranje i aktivacija/deaktivacija platnih poveznica u zajedničkom JSON backendu.
- API: odvojeni merchant-only mobilni tokeni i rute za prijavu, overview, stvaranje linka te odjavu.
- Jedinstveni JSON backend usklađuje web, korisnički i admin panel, WordPress/WooCommerce i Android.
- Android CI izvodi Gradle 9 gradnju instalacijskog debug APK-a i nepotpisanog release AAB-a.
- Objavljeno kao sandbox prerelease. Produkcijski PSP, stvarne vizualne usporedbe i signing i dalje otvoreni.

# Povijest izdanja

## 0.8.3 — 2026-11-04 (SANDBOX)

- Autentificirani endpointi `GET /api/v1/sites/status` i `POST /api/v1/sites/sync` za stanje registrirane domene i status instalacije.
- Sinkronizacija vezana uz site-scoped token, ID instalacije, canonical HTTPS origin i aktivnu plaćenu pretplatu.
- Korisnički i administratorski panel pokazuju zadnji kontakt; admin vidi verziju dodatka i može suspendirati paket.
- WordPress prikazuje stanje licence, paket, iskorištene domene i datum isteka; podržava ručnu i WP-Cron sinkronizaciju.
- WooCommerce Classic i Blocks skrivaju način naplate ako potvrda licence nedostaje, istekla je ili je odbijena.
- Novo lokalno testiranje stanja sinkronizacije, odbijanja spoofiranih domena/instalacija i obustavljenih pretplata.
- Produkcijsko procesiranje stvarnih uplata i 1:1 vizualna certifikacija nisu dostupni.



## 0.8.2 — 2026-11-04 (SANDBOX)

- Model tri plaćena plana s fizičkim limitima instalacija Start 1, Business 5, Pro 25; uklonjen besplatni paket iz cjenika.
- Registracija odabire željeni plan, a samo verificirani operator ručno može potvrditi plaćenu pretplatu u zasebnom administratorskom panelu.
- Korisnički panel `/sites`: registracija HTTPS domene, pregled dostupnih/iskorištenih licenci, rotacija i opoziv/domenska kvota.
- WordPress dodatak: aktivacijski obrazac razmjenjuje domenski licencni ključ za šifrirano spremljeni site-scoped sandbox API token.
- Domain/site isolation na API endpointima i server-side kontrola aktivnog plana.
- Dodani regresijski testovi kvota, odobravanja plaćenog paketa i aktivacije licence.
- Ostaje SANDBOX-only: nema automatskog plaćanja pretplata, live PSP integracije ili potvrđene vizualne 1:1 podudarnosti.



## 0.8.1 — 2026-11-04 (SANDBOX) — kritični WordPress/WooCommerce hotfix

- Uklonjena nekompatibilna `private array $settings` deklaracija iz `BP_Pay_Blocks`: WooCommerce `AbstractPaymentMethodType` već ima naslijeđeno `protected $settings`, čije preklapanje uzrokuje PHP fatal prilikom `woocommerce_blocks_loaded`.
- Dodana zaštita za oštećene/nepostojeće spremljene WooCommerce postavke (`get_option` se svodi na prazno polje ako nema valjanog zapisa).
- Shortcode callback normalizira ne-array argumente umjesto TypeError.
- Dodat PHP runtime test za kompatibilnost nasljeđivanja i registraciju WooCommerce Blocks (s WordPress/WooCommerce testnim stubovima). Ova provjera nije zamjena za instalacijski E2E test u stvarnom WordPressu.
- Nova verzija dodatka, marketinškog README-a, kontrolnih datoteka i release ZIP-ova.
- Stvarna naplata i vanjska produkcijska certifikacija i dalje su isključene.


## 0.8.0 — 2026-11-04 (SANDBOX)
- Gutenberg blokovi: kontrola ikone naplatnog gumba i teksta uz QR, uz autoritativan iznos s JSON platforme.
- Nova zaštita prijave: ukupno ograničenje neuspješnih pokušaja po IP adresi uz postojeći limiter IP/e-pošta.
- Smanjena maksimalna veličina odgovora WordPress REST klijenta i izbjegnuta neiskorištena izgradnja HTML tablica na dashboardu.
- Usklađeni dodatni CSS razmaci, fokus i prikaz komponenti prema WordPress/Gutenberg/WooCommerce referencama.
- Marketinški README sa stvarnim lokalnim snimkama weba i izvornim WordPress grafičkim resursima; transparentno označena odsutnost stvarnih WP snimaka.
- Dokumentirani sigurnosni i dead-code nalazi, statički CI audit i realni regresijski test zaštite prijave.
- Nije potvrđena 1:1 podudarnost ni spremnost za produkcijska plaćanja.

## 0.7.2 — 2026-11-04 (SANDBOX)
- WordPress/Gutenberg/WooCommerce pixel-parity pass, responsive provider rows, labeled provider monograms and amount display locked to the platform.
- No live payments or claim of pixel-perfect verification in the target WordPress theme.

## 0.7.1 — WordPress visual parity iteration
- Responsive visual refinements for WordPress plugin admin, form controls and provider cards.
- Accessible active menu tabs with URL hash and `aria-current`.
- Pixel-diff command for exactly matching screenshot dimensions; does not certify 1:1 parity.

# Changelog

## 0.7.0 — GitHub import and baseline visual QA
- Source migration to GitHub, CI workflow, safe root gitignore, repo branding/metadata.
- Design reference archive and visual QA matrix for WordPress, WooCommerce and platform.
- Better CSS token naming; sandbox-only behavior unchanged.

# Changelog

## 0.6.0 — realistic dashboard, merchant checkout, Gutenberg link picker (SANDBOX)

- New tenant-scoped GET `/api/v1/links` endpoint and WP editor REST proxy, with isolation smoke tests.
- Gutenberg selector populated from real active merchant links; live server amount and title preview, no editable checkout price.
- Merchant dashboard dynamic 30-day sandbox revenue chart from JSON (replaces illustrative graph).
- Refined real public payment link checkout with provider radio choices, merchant details, hosted checkout only.
- WordPress admin lists active payment links with shortcode copying, and classic/Blocks payment panels refined.
- Desktop, tablet and mobile browser layout checks; real screenshots included; no horizontal overflow in tested routes.
- No production processing; no claim of full 1:1 parity or WP.org approval.


## 0.5.0 — Brendigo visual refinement and functional UX (SANDBOX)

- Updated web navigation, footer, product site and branding metadata; added favicons, app icons and WordPress.org-ready local branding assets.
- Added real link descriptions (validated JSON persistence + API + escaped checkout) and immediate text/price preview in modal.
- Added owned-transaction details dialog and local search, provider and status filters on the displayed transaction subset.
- Refined responsive visual treatments for website, checkout, merchant UI, WordPress admin, Gutenberg and WooCommerce radio selectors.
- Tested PHP syntax, JSON storage, sandbox adapter verification, HTTP smoke, mocked Woo gateway and offline desktop/mobile render without horizontal overflow.
- No production payment support, real provider E2E/approval, or certified 1:1 screenshot parity.

## 0.4.0 — Visual reference implementation (SANDBOX)

- Rebuilt the public site in the Brendigo Pay light/indigo/cyan visual language: homepage and pricing page.
- Rebuilt merchant UI with responsive sidebar, metrics, tabbed views for links, transactions and integrations, and a functional payment-link modal.
- Rebuilt public payment-link checkout with real configured sandbox providers; continued to redirect to their hosted checkouts rather than collecting card data.
- Added standalone local SVG brand mark and externally loaded, readable CSS/JavaScript (no new generated images).
- Redesigned WordPress plugin admin with a responsive Brendigo design system; the platform remains sandbox-only.
- Enhanced Gutenberg with four dynamically rendered block variants (payment button, link, QR, payment card), a branded editor preview, and locally rendered QR support.
- Updated classic WooCommerce and Checkout Block provider selectors to branded radio cards; retained HPOS-compatible order CRUD.
- Verified payment-link ownership in authenticated API retrieval used by the WordPress dynamic shortcode. Pricing and titles render from the Brendigo API, not untrusted editor copy.
- Ran PHP lint, JSON storage tests, offline provider/Webhook tests, HTTP smoke tests, backup/restore, and simulated WooCommerce gateway tests.
- **Limitations:** visual resemblance, not proven pixel-perfect; no live WordPress/WooCommerce browser/E2E environment; no real provider credentials; no live payments or external approvals. See `docs/VISUAL-QA.md`.

# Brendigo Pay — promjene

## v0.4.0 — 2026-11-04

- PayPal Sandbox adapter: OAuth client-credentials token, create order, hosted payer action, capture nakon povratka, provjera order ID-a, `COMPLETED` capturea, EUR i iznosa; verified webhook adapter.
- Revolut Sandbox adapter: Merchant API verzija 2026-08-17, hosted checkout URL, potpisani webhook (`Revolut-Signature` s timestamp), nezavisni dohvat originalne narudžbe i usporedba ID/iznos/EUR/state.
- Web postavke za zasebne Stripe TEST, PayPal Sandbox i Revolut Sandbox račune; šifrirane lokalne vjerodajnice.
- Javni `/p/{id}` omogućuje izbor konfiguriranih sandbox pružatelja.
- REST `/api/v1/checkout` prihvaća `provider`, `GET /api/v1/me` vraća provider status i `GET /api/v1/payments/{id}` vraća provider radi usporedbe s WooCommerce narudžbom.
- WordPress/WooCommerce dodatak v0.4.0: sandbox provider izbor za classic i Blocks checkout (`onPaymentSetup`), host allowlist, pregled provjerenog statusa po pružatelju, dodatna kontrola već zaključenih narudžbi.
- CLI `platform/bin/backup.php` i `restore.php`: šifrirani JSON arhiv s Argon2id, libsodium secretbox i negativnim testovima; sesije se ne vraćaju.
- Dodani adapter unit testovi, autentificirani HTTP smoke testovi i prošireni WooCommerce mock testovi za tri pružatelja.
- Opcionalni Stripe Connect Standard OAuth **TEST**: jednokratni `state`, razdvajanje povezanih računa po trgovcu, potpisani Connect webhook, testni checkout `Stripe-Account` i potvrđeno odspajanje; zahtijeva konfiguraciju Stripe platforme, bez provedenog vanjskog E2E testa. Izolacija starog direktnog Stripe webhooka od Connect događaja.
- **Nisu podržani live računi ni stvarno terećenje.** E2E u službenim sandbox računima i stvarna WooCommerce integracija još nisu provedeni; PayPal/Revolut partnerski onboarding nije implementiran; Stripe Connect OAuth kod jest, no stvarna povezanost nije potvrđena.

## v0.2.0 — 2026-11-04

- Uključivanje i isključivanje poveznica; opozivanje API tokena; `GET /api/v1/me`; audit JSON; CSV izvoz uz formula injection zaštitu.
- WordPress admin *Testiraj povezivanje*, HPOS CRUD deklaracija, dodatne zaštite zatvorenih narudžbi, lokalni HTTP i gateway mock testovi.

## v0.1.0 — 2026-11-04

- Samostalna PHP web-platforma bez SQL baze, JSON pohrana, registracija/prijava, naplatne poveznice, QR, Stripe TEST Checkout i HMAC webhook.
- WordPress shortcode, Gutenberg blok i početna WooCommerce testna integracija.
