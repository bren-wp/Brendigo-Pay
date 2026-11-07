# Brendigo Pay — vizualna kontrola / pixel parity

**Reference:** korisnički priloženih 10 konceptualnih slika u `design/references/`. Slike služe za usporedbu, **ne** kao pozadine produkcijskih UI elemenata.

## Prioriteti po ekranu

| Ekran | Referenca | Usporedba |
|---|---|---|
| Logo, ikone, boje, tipografija | `10-brand-identity.jpeg` | Wordmark, favicon, Inter fallback, CSS design tokens |
| Naslovnica | `09-home.jpeg` | Header visina, hero 2 stupca, kartice, CTA, footer |
| Cjenik | `08-pricing.jpeg` | Tri paketa, tablice, FAQ, CTA |
| Checkout naplatne poveznice | `07-payment-checkout.jpeg` | Dvostupčana struktura i hosted payment redirect |
| Nadzorna ploča trgovca | `06-merchant-dashboard.jpeg` | Sidebar, KPI, graf iz testnih podataka, aktivnosti |
| Novi platni link | `05-payment-link-modal.jpeg` | Forma, live preview, mobilni modal |
| Transakcije | `04-transactions.jpeg` | Filteri, tablica i detalji transakcije |
| WordPress administracija | `03-wp-admin.jpeg` | WP sidebar, tabovi, API polja, pružatelji, statusi |
| Gutenberg blokovi | `02-gutenberg.jpeg` | Odabir poveznice, prikaz bloka, inspector |
| WooCommerce checkout | `01-woocommerce-checkout.jpeg` | Classic i Checkout Block različiti sustavi; odgovarajući provider UI |

## Pravila implementacije

- Ne zamjenjivati stvarna polja slikom dizajna. Svaki gumb mora imati radnju ili biti nedostupan s objašnjenjem.
- Sve javne tvrdnje o prometu, recenzijama, dostupnosti ili certificiranju moraju biti stvarne i provjerene; referentni brojevi su primjeri.
- Kartična polja sa slike nisu dio Brendigo Paya: testni hosted checkout vodi pružatelj.
- Mjeriti desktop na 1448×1086, tablet na 768 px, mobitel 390 px; kontrolirati širine teksta, sticky header, tablice i `overflow-x`.
- Uvijek uključiti snimke stvarne aplikacije kao rezultat usporedbe. Ne označavati 1:1 bez izmjerene vizualne usporedbe.
- WooCommerce i Gutenberg usporediti u pravoj WordPress instalaciji; lokalni mock testovi nisu dovoljni.

**Stanje v0.7.0:** postoje funkcionalni predlošci i responsive stilovi; potpuna 1:1 podudarnost i produkcijski platni servisi nisu potvrđeni.

## Aktualno stanje mjerenja

Matrica svih deset referenci dostupna je u [VISUAL-10-REFERENCE-STATUS.md](VISUAL-10-REFERENCE-STATUS.md). Workflow `visual-parity.yml` pokušava snimiti šest stvarnih web ekranâ pri 1448×1086. Tri WordPress reference i identitetski koncept zahtijevaju zasebno mjerenje.

## Reproducibilna usporedba

`python3 scripts/visual_compare.py design/references/09-home.jpeg putanja/do/stvarnog-prikaza.png`

Usporedba **odbija** snimke različite veličine (ne rasteže snimke jer bi rezultat bio pogrešan).
Metrika prikazuje prosječnu apsolutnu RGB razliku i postotak različitih piksela; ne potvrđuje automatski kvalitetu niti upotrebljivost. Poredak slojeva, margine, visine, statusi i ponašanje kontrola obvezno se ručno provjeravaju.

**WordPress v0.7.1:** aktivni tabovi sada slijede URL hash, `aria-current` i fokus; unaprijeđeni su responzivni razmaci i kontrole. To nije konačna 1:1 verifikacija.

## v0.7.2 — komponentne izmjene

- `01-woocommerce-checkout.jpeg`: jednaki obrubi, raspored, ikonice i opisi Stripe/PayPal/Revolut pružatelja u Classic i Blocks izboru. Matična WooCommerce tema i dalje upravlja podacima kupca, dostavom i košaricom; ne stvarati lažna polja kartica.
- `02-gutenberg.jpeg`: poboljšan odnos ilustracijskog i sadržajnog stupca; iznos na platformi prikazuje se kao zaključana vrijednost. Izmjena iznosa unutar bloka nije dopuštena.
- `03-wp-admin.jpeg`: ujednačene visine polja, razmaci kartica, tabovi, fokus stanja i responzivni razmaci. Izgled WordPress chrome-a ostaje pod kontrolom administratorove teme.
- Vizualna točnost 1:1 još nije potvrđena u stvarnoj WordPress i WooCommerce instalaciji.

## Fokusirani 1:1 checklist za WordPress

Vidi [WORDPRESS-PIXEL-CHECKLIST.md](WORDPRESS-PIXEL-CHECKLIST.md) za detaljan element-po-element plan, ROI koordinate i provjere za WooCommerce checkout, Gutenberg editor i WordPress administraciju. Pokretanje lokalnih mjerenja: `python3 scripts/visual_audit_wp.py SCREEN /path/to/screenshot.png`.


## v0.8.8 — geometrija potvrđena tek nakon stvarnog mjerenja

Poboljšani su visina zaglavlja, hero i kartica cijena, dvostupčani prikaz, margine, FAQ i dijalog novog platnog linka. Izmjene su CSS komponente, ne pozadine od referentnih slika. Žive cijene i stvarni sandbox podaci namjerno se ne zamjenjuju ilustrativnim podacima s referenci. Usporedbu obvezno ponoviti na 1448 × 1086, 768 × 1024 i 390 × 844; bez izvještaja ne tvrditi da je dizajn 1:1.
