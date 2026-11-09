# Brendigo Pay — nastavak pixel-parity audita, 9. studenoga 2026.

## Potvrđeni rezultati pri 1448×1086, DPR 1

Mjerenje: `scripts/visual_audit_10.py`, isti izvorni JPEG, Chromium snimke stvarnog sandbox sučelja, luminantna razlika >8/255. Nije apsolutna pixel-perfect certifikacija i ne jamči jednak prikaz na svim uređajima.

| Prikaz | Prethodno (v0.8.12) | Novo | Promjena |
|---|---:|---:|---:|
| Novi platni link (modal) | 72,16 % | 43,48 % | −28,68 p. b. |
| Cjenik | 59,35 % | 56,65 % | −2,70 p. b. |
| Transakcije | 35,71 % | 35,71 % | Bez promjene |
| Korisnička ploča | 47,39 % | 47,39 % | Bez promjene |
| Javni checkout | 39,49 % | 39,49 % | Bez bitne promjene |
| Naslovnica | 51,05 % | 51,05 % | Bez promjene |

**Status:** 0/6 izmjerenih zaslona ispod 10 % i nijedan potvrđen na 0 %. WooCommerce checkout, Gutenberg i stvarni WordPress admin trebaju snimke iz stvarnog WordPress/WooCommerce okruženja; brand sheet je zasebni dizajnerski vodič.

## Konkretne izmjene

1. Uklonjen konflikt dvaju CSS pravila modalne pozadine. Staro, kasnije pravilo s `rgba(19,34,83,.57)` nadjačavalo je prvotnu željenu vrijednost. Puni pregled i realna snimka potvrdili su smanjenje razlike modala.
2. Modal na desktopu poravnat prema gabaritima iz reference, bez promjene mobilnog pozicioniranja.
3. Ugrađen pristupačan mjesečni/godišnji **informativni prikaz** cijena i u kartice i u tablicu. Godišnji iznos je 12 × mjesečni cjenik, **bez neodobrenog godišnjeg popusta**; odabir nije kupnja ni ugovorena pretplata. Ograničenja i dalje proizlaze iz `Plans::TIERS` na serveru.
4. Cjenik je dobio kompaktniji hero, a mjesečni/godišnji kontrolni element smješten je iznad paketa, kao na referenci.
5. Dodane provjere funkcionalnog prebacivanja, pristupačnog `aria-pressed`, upozorenja za godišnji izračun i sprječavanja ponovnog CSS sukoba.
6. Neuspješan pokušaj dodavanja valova u zaglavlje modala **vraćen je** jer je povećao mjereni broj različitih piksela (43,48 % → 43,95 %). Bez lažiranja napretka.
7. Stabiliziran izolirani PHP sigurnosni smoke-test: nakon promjene testne legalne konfiguracije privremeni poslužitelj se ponovno pokreće kako test ne bi ovisio o predmemoriji.

## Preostali prioriteti

- Modal: precizne proporcije desnog pregleda, istinito prikazivanje neaktivnih pružatelja, učitavanje stvarnih licenciranih domena, QR kod tek nakon stvarnog spremanja.
- Cjenik: dominantne hero ilustracije i kartice te dizajnerski tokeni na svim viewportima; nije dopušteno glumiti besplatni Start paket, budući da je Start stvarno plaćen.
- Naslovnica: hero grafika i drugi sadržaj značajno se razlikuju od konceptualnih marketinških primjera, ali ne treba izmišljati promet, recenzije i postotke.
- WordPress, Gutenberg i WooCommerce: pravi host screenshot i stvarna funkcionalnost na svim podržanim tipovima checkouta.
- Android: stvarne snimke/emulator i usporedbe s odobrenim mobilnim dizajnom; trenutnih deset referenci nisu specifične Android snimke.
- Windows aplikacija nije prisutna u repozitoriju.
- **Sustav ostaje SANDBOX.** Nema odobrenja za produkcijsku platnu naplatu, AAB je nepotpisan i APK je debug.

Kontrolirani vizualni artefakti i izvještaji su u GitHub Actions workflowu `Brendigo Pay 10-reference visual audit`.
