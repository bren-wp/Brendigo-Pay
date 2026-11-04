# Brendigo Pay v0.8.0 — dead-code audit

**Datum:** 2026-11-04  
**Opseg:** PHP web i WordPress dodatak, JS/CSS asseti, CLI, CI, dokumentacija i release paketi.  
**Metoda:** pregled poziva/pozivatelja, statičkih ruta, PHP templatinga, CSS duplikata i provjere izlaznih funkcija; lokalni testovi i statički policy test. **Nije tvrđena potpuna formalna analiza dinamičkih WordPress hookova i svih CSS selektora u vanjskim temama.**

## Uklonjeno / ispravljeno

| Nalaz | Izvor | Rezultat |
|---|---|---|
| Neiskorišteno generiranje stare tablice linkova | `platform/public/index.php` ruta `/dashboard` | Uklonjen `$rows` niz HTML-a koji se nije koristio; korisnički panel generira `bp_merchant_ui` |
| Neiskorišteni `$paid` i `$transactionRows` | ista ruta | Uklonjeno nepotrebno računanje/renderiranje; svi transakcijski podaci i dalje se šalju stvarnom dashboardu |
| Parametar `$light` bez implementacije | `platform/src/Ui.php::bp_brand` | Uklonjen parametar i nepotreban argument pozivatelja |
| Izostanak ikonice u Gutenberg naplatnom gumbu | editor i shortcode | Dodan pravi atribut i stvarni server-render, ne inertni editor toggle |
| Nedefiniran tekst uz QR u editoru | editor/shortcode | Dodan atribut `qr_caption` i prikaz na objavljenoj stranici |

## Preostalo za refaktoriranje

- **CSS duplikati:** `assets/public.css`, `admin.css` i `editor.css` imaju povijesne prepisujuće selektore iz v0.4–v0.8; mogu se konsolidirati nakon točne vizualne usporedbe zbog rizika od regresije. Ne brisati ih automatski bez render testa.
- **Dinamički WordPress hookovi:** PHP registracija blokova i WooCommerce gateway callbackovi izgledaju nekorišteno u statičkoj pretrazi, ali WordPress ih poziva dinamički. Ne uklanjati.
- **Datoteke za povrat kompatibilnosti i backup CLI:** nisu dead code iako ih ne poziva glavni HTTP router.
- **Screenshotovi:** stvarne web-snimke i dizajnerske reference pripadaju dokumentaciji, nisu runtime asseti; moraju biti isključene iz WordPress instalacijskog ZIP-a, ali mogu ostati u izvornom projektu.
- **Stari changelog zapisi:** povijest je korisna. Očistiti ponovljene naslove, ne brisati povijest.

## Kontrolni koraci

```bash
bash tests/run-all.sh
python3 scripts/package_release.py --output dist
python3 -m py_compile scripts/visual_audit_wp.py
```

Provjera skenira i osjetljive tokove, ali prisutnost ili odsutnost niza nije dokaz da je kod siguran. Za finalni dead-code rezultat trebaju pokrivenost ruta/hookova, WP+WC E2E i CSS coverage na stvarnim renderima.
