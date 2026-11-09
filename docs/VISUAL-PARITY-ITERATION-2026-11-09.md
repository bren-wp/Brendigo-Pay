# Brendigo Pay — 8 prioritetnih 1:1 područja (9. 11. 2026.)

**Stanje:** provedena vizualna implementacijska iteracija na aktualnom `main` i ponovljena stvarna Chromium provjera pri 1448 × 1086 px, DPR 1. Ovo **nije** potvrda apsolutnog 1:1 / 0 % odstupanja.

## Mjerenja prije i nakon iteracije

Ista mjerna funkcija: udio piksela s luminantnom razlikom većom od 8 / 255, usporedba izvornog JPEG-a i snimke pravog sučelja. Nema skaliranja i nema ubacivanja referentnog JPEG-a u aplikaciju.

| Prikaz | Prije | Nakon najbolje potvrđene iteracije | Cilj |
|---|---:|---:|---|
| Transakcije (cijeli viewport) | 37,12 % | 35,71 % | 0 % |
| Novi platni link (cijeli viewport) | 70,54 % | 72,16 % — regresija | 0 % |
| Korisnička ploča (cijeli viewport) | 49,63 % | 47,39 % | 0 % |
| Javni checkout (cijeli viewport) | 39,94 % | 39,49 % | 0 % |
| Cjenik (cijeli viewport) | 60,10 % | 59,35 % | 0 % |
| Naslovnica (cijeli viewport) | 51,49 % | 51,05 % | 0 % |

**Regionalni izračun (isti crop prije/nakon, provjeren lokalno iz stvarnih snimki):**

| Regija 1448×1086 | Prije | Nakon | Razlika |
|---|---:|---:|---:|
| Cjenik, CTA (x25–1390, y945–1062) | 89,63 % | 79,52 % | −10,11 postotnih bodova |
| Naslovnica, CTA (x25–1390, y945–1062) | 75,80 % | 70,19 % | −5,61 postotnih bodova |
| Cjenik, hero (x25–1390, y60–244) | 64,45 % | 66,01 % | +1,56 bodova |
| Cjenik, kartice (x70–1360, y300–610) | 65,01 % | 65,33 % | +0,32 boda |

Postotke iz različitih regija ne zbrajati i ne predstavljati kao zamjenu za cjelokupni screen-diff. Nakon eksperimentalnog širenja CTA-a rezultati su se pogoršali; taj CSS je vraćen na prethodno izmjereno bolje stanje.

## Što je implementirano

- Donji CTA bannera naslovnice i cjenika: zasebne sekcije s realnim linkovima, dvije akcije, postojeći brand mark i CSS grafika; oba se prikazuju unutar početnog viewporta.
- Cjenik: dekorativna kartica desno, oblik hero podloge, kompaktnije kartice Start/Business/Pro i tablica.
- Pregled: širina lijeve navigacije prema referenci (236 px); transakcije imaju referentno užu navigaciju (203 px) i istaknut naslov.
- Novi platni link: odvojena kartica pregleda i privremeni blok za URL/QR koji nisu aktivni prije spremanja; **ne generira se lažni QR kod**.
- Checkout: vizualni slijed sigurnosne informacije → sažetak iznosa → stvarna aktivna/onemogućena akcija i simboli pružatelja; kartični podaci ostaju na službenom hosted checkoutu.
- Dodan regresijski test koji provjerava da se oba CTA bannera pojavljuju u viewportu te da postoje stvarne dvije poveznice i da checkout prikazuje oba uvjetna sažetka.

## Preostali rad — bez lažnog 1:1

1. Modal je numerički lošiji, ponajviše zato što referenca prikazuje lažnu popunjenu nadzornu ploču, a stvarni testni račun ne sadrži izmišljene transakcije. Mora se dodatno usporediti modal po regijama, ne puniti račun lažnim poslovnim podacima.
2. Hero i kartice cjenika još imaju velika odstupanja; testna funkcionalnost i plaćeni paketi ne odgovaraju konceptualnom besplatnom Start paketu i ne smiju se lažno predstavljati.
3. Checkout u referenci ima samostalna polja broja kartice/CVC, što nije dopušteno kopirati u ovu arhitekturu; integracija mora ostati sigurna kod platnog pružatelja.
4. WordPress/Gutenberg/WooCommerce tri reference nemaju stvarne host snimke. Windows implementacija nije u repozitoriju. Android nije zasebno obuhvaćen ovim deset konceptima.
5. Nema potvrde certificirane produkcijske naplate, 0 % odstupanja ili odsutnosti sigurnosnih ranjivosti.

Automatski mjerni izvještaji i snimke nalaze se kao GitHub Actions artefakti workflowa **Brendigo Pay 10-reference visual audit**; testove pokreće `tests/visual-qa.py`, a mjerne izvještaje `scripts/visual_audit_10.py`.
