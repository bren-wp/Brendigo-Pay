# Brendigo Pay — vizualna iteracija 2026-11-09 / v0.8.14

**Opseg:** web naslovnica i cjenik, provjera korisničke ploče i modala, testovi i sukladnost web/WordPress/Android izdanja. Projekt ostaje **SANDBOX**, bez produkcijske certifikacije platnih sustava.

## Provjerena vizualna usporedba (1448 × 1086, DPR 1)

Metrika: udio piksela s razlikom luminancije većom od 8/255, bez umetanja referentne slike u web sučelje. Ne jamči jednakost na svim uređajima ili potpuno 1:1.

| Prikaz | Polazno v0.8.13 | Završno nakon ispravaka | Promjena |
|---|---:|---:|---:|
| 04 Transakcije | 35,71 % | 35,71 % | 0,00 p.b. |
| 05 Novi platni link | 43,48 % | 43,48 % | 0,00 p.b. |
| 06 Korisnička ploča | 47,39 % | 47,39 % | 0,00 p.b. |
| 07 Javni checkout | 39,49 % | 39,49 % | 0,00 p.b. |
| 08 Cjenik | 56,65 % | **56,28 %** | −0,37 p.b. |
| 09 Naslovnica | 51,05 % | **48,21 %** | −2,84 p.b. |

Dokaz: [Visual audit run 42713229153](https://github.com/bren-wp/Brendigo-Pay/actions/runs/42713229153), uključujući originalne stvarne snimke, razlike i `assessment/report.json`.

**Neispunjen cilj:** svih šest izmjerenih web zaslona ostaje znatno iznad 10 %, a cilj 0 % nije potvrđen. `01` WooCommerce, `02` Gutenberg i `03` WP admin i dalje nemaju snimke iz stvarne instalacije. `10` je priručnik vizualnog identiteta, a ne samostalan aplikacijski zaslon. Nema izvorne Windows implementacije.

## Implementirane poboljšane komponente

- Naslovnica: zamijenjen prazni/razvojni teaser stvarnim prikazom tri plaćena paketa Start (19,90 €/mj), Business (49,90 €/mj) i Pro (99,90 €/mj); kartice vode na stvarne registracijske rute `/register?plan=...`. Prikaz ne izmišlja besplatni paket, korisnike, transakcije ili recenzije.
- Naslovnica: WordPress/WooCommerce kartica dobila je dekorativni pregled, bez vanjskih poziva i bez lažnih aktivnih integracija. Hero prijenosno računalo pomaknuto je bliže položaju iz referentnog koncepta, a plutajući testni platni panel ostao je istinit.
- Cjenik: za funkcionalne iznose ostalo je ranije uvedeno prebacivanje mjesečno/godišnje (12 × mjesec, bez fiktivnog popusta). Vizualno su poboljšani zelene oznake pogodnosti, slojevi Business kartice i lijevi poravnati hero. CSS valovi ograničeni su na samu Business karticu pa ne prekrivaju tablicu i FAQ.
- Dodane su strukturne i Chromium provjere za sve tri poveznice, prisutnost povezanih kartica i mobilne širine.
- Dva eksperimenta s merchant navigacijom povučena su jer su pogoršala pixel-diff Transakcija/Pregleda; završno stanje vraćeno je na stabilno. Nije promijenjena autorizacija, izolacija korisnika ni naplatna logika.

## Kriterij prihvaćanja i preostali posao

1. Svaki stvarni prikaz mora biti snimljen pri istom viewportu, DPR-u, fontu, sadržaju i statusu prijave; sve reference treba mjeriti pojedinačno.
2. Dodatno usklađivanje hero ilustracija, marketing kartica, cjenika i modala, bez stvaranja lažnih proizvodnih podataka.
3. WordPress plugin sedam odvojenih podstranica: stvarni WordPress admin, Gutenberg i WooCommerce testovi (Classic/Blocks/HPOS), uključujući responzivnost.
4. Android: uređaji/emulatori i zasebni referentni mobilni prikazi; sadašnji konceptualni JPEG-ovi nisu odgovarajuće Android reference.
5. Sandbox PSP integracije ne smiju se predstavljati kao certificirana produkcijska naplata. Live rad zahtijeva odobrenje pružatelja, potpisane pakete, sigurnosni pregled i operativne kontrole.
