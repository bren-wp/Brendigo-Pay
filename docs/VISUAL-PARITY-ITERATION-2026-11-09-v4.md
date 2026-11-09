# Brendigo Pay — šest referentnih web zaslona, v0.8.15

Datum: 9. studenoga 2026.  |  Izvor: stvarni Chromium prikazi v0.8.15, 1448 × 1086, DPR 1.  
Metoda: `scripts/visual_audit_10.py`, udio piksela s luminantnom razlikom >8/255. Ne smatra se dokazom 1:1 identičnosti niti potvrdom produkcijske spremnosti.

## Sažetak

| Referentni zaslon | v0.8.14 | v0.8.15 | Rezultat |
|---|---:|---:|---|
| 04 — Transakcije | 35,71 % | 35,70 % | −0,01 p.b. |
| 05 — Novi platni link | 43,48 % | 43,37 % | −0,11 p.b. |
| 06 — Korisnička ploča | 47,39 % | 47,39 % | bez promjene |
| 07 — Checkout | 39,49 % | 39,47 % | −0,02 p.b. |
| 08 — Cjenik | 56,28 % | 55,97 % | −0,31 p.b. |
| 09 — Naslovnica | 48,21 % | 48,28 % | +0,07 p.b. (regresija u sirovoj metrici) |

**0/6 web zaslona** ispod ciljnih 10 %. Nijedan nije 0 %. Tri WordPress/Gutenberg/WooCommerce host snimke još nedostaju, a deseta referenca je brand identity vodič, ne samostalan zaslon. Android ima zasebno snimanje UI-a; za šest web zaslona dodatno su odrađene tablet i mobile browser provjere.

## Izmjene koje ostaju

1. Cjenik: tablica paketa prikazuje Start / Business / Pro s mjesečnim iznosima u zaglavljima. Godišnji prikaz ažurira i zaglavlje i retke, uz jasnu obavijest da je to samo informativnih 12 × mjesečna cijena. Dodano je šesto FAQ pitanje za Stripe, PayPal i Revolut. Dodatni CSS eksperiment s tablicom i FAQ-om uklonjen je nakon pogoršanja mjerenja 55,97 → 57,53 %.
2. Korisnička ploča: sažetak zadnjih 30 dana sada uključuje samo zapise iz 30 UTC kalendarskih dana, jednako kao grafikon. Kartica transakcija i dalje je potpuni pregled, jasno označen `Svi datumi`. Nije promijenjena autorizacija ili filtriranje zapisa prema korisniku.
3. Novi platni link: u desktop modalu tri pružatelja stoje u jednom redu, dok automatski odabir ostaje zasebna, funkcionalna opcija. Nepovezani pružatelji ostaju onemogućeni.
4. Checkout: desktop ploča opisa narudžbe i ploča naplate visinom bolje odgovaraju referenci. Kartični podaci i dalje se smiju unositi samo na hosted checkoutu službenog pružatelja.
5. Naslovnica: dvoredni naslov po uzoru na referencu i kraće, provjerljive pogodnosti u jednom retku. Sirovi pixel-diff se pogoršao za 0,07 p.b., ali oblik i redoslijed naslova humanim pregledom bliži je referenci.
6. Regresija: automatizirani testovi provjeravaju svih šest ruta, godišnje cijene, raspored pružatelja, stvarni vremenski opseg i nepostojanje lokalnog unosa broja kartice. Visual workflow izvršava Chromium desktop (1448×1086), tablet (768×1024) i mobilni (390×844) pregled, s provjerom horizontalnog prelijevanja.

## Nedovršeno

- Sve šest slikovnih usporedbi još znatno odstupaju. Točan 1:1 rezultat zahtijeva daljnju detaljnu usporedbu komponente po komponente, izvorne dizajnerske resurse, stvarni sadržaj i dosljedne testne uvjete.
- Konceptualne reference sadrže izmišljene transakcije, prikaz besplatnog Start paketa i kartični obrazac u vlastitom sučelju. Sustav **ne** umeće izmišljene poslovne podatke, **ne** nudi besplatan Start i **ne** prihvaća kartične podatke lokalno.
- WordPress admin, Gutenberg i WooCommerce zahtijevaju end-to-end testove u stvarnim instalacijama. Nema zasebne Windows implementacije u repozitoriju.
- Nema potvrđene certifikacije za live PSP ni Google Play potpisivanja. Izdanje ostaje SANDBOX s debug APK-om i nepotpisanim AAB-om.

## Dokazi

GitHub workflow `Brendigo Pay 10-reference visual audit` za commit `c185e7e7214306393ff4662e2a50c8fe754be520`:
https://github.com/bren-wp/Brendigo-Pay/actions/runs/42718207947

Snimke i izvještaj: artefakt `brendigo-pay-ten-reference-visual-audit`, datoteka `assessment/report.json`.
