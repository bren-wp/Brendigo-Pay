# Brendigo Pay v0.8.1 — oporavak od kritične WordPress greške

## Potvrđena nekompatibilnost (kod v0.8.0)

U `wordpress-plugin/brendigo-pay/includes/class-bp-blocks.php` bila je deklaracija:

```php
private array $settings = [];
```

Službeni WooCommerce `Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType` već ima `protected $settings = []` bez eksplicitnog tipa. PHP ne dopušta smanjenje vidljivosti ili dodavanje nekompatibilnog tipa pri redeklaraciji naslijeđenog svojstva; u trenutku registracije integracije Checkout Blocks PHP prijavljuje fatal. Zato raniji sintaksni lint i offline test `tests/woocommerce-gateway.php` nisu uhvatili kvar — taj test nije učitavao stvarnu roditeljsku klasu.

U v0.8.1 uklonjeno je preklapanje svojstva, a `initialize()` normalizira spremljene postavke u polje. Test `tests/woocommerce-plugin-bootstrap.php` učitava dodatak s minimalnom kompatibilnom WooCommerce roditeljskom klasom i izvršava registracijske hookove.

## Sigurna nadogradnja

1. Ako je WordPress administrator dostupan, deaktvirajte **Brendigo Pay** ili upotrijebite **Recovery Mode** iz e-pošte administratora; sigurnosne postavke i stari merchant podaci ostaju netaknuti.
2. Preuzmite **`brendigo-pay-wordpress-0.8.1.zip`** s GitHub izdanja v0.8.1 i u **Dodaci → Dodaj novi → Prenesi dodatak** instalirajte/zamijenite postojeći Brendigo Pay. Ne brisati opcije baze.
3. Ako WordPress administrator nije dostupan, pomoću hosting upravitelja datoteka ili SFTP-a privremeno preimenujte samo mapu `wp-content/plugins/brendigo-pay` (npr. u `brendigo-pay-disabled`) kako bi WordPress prestao učitavati dodatak. Zatim prenesite novu mapu `brendigo-pay` iz ZIP-a, sačuvajte staru mapu za povrat i ponovno aktivirajte dodatak.
4. Otvorite **Brendigo Pay → Postavke** i provjerite konfiguraciju. Nemojte kopirati privatni API ključ u podršku ili javni issue. Nisu potrebne migracije ili unos novog ključa.
5. Testirajte učitavanje javne i administratorske stranice, Gutenberg editora, WooCommerce checkouta i isključivanje/uključivanje gatewaya u testnom okruženju.

## Ako poruka ostane

Bez error loga nije moguće jamčiti da je ovo jedini uzrok. Uključite **WP_DEBUG_LOG** i **WP_DEBUG_DISPLAY=false** u sigurnom, kontroliranom okruženju; pregledajte `wp-content/debug.log` ili PHP error log hostinga. Za dijagnostiku podijelite **samo tekst pogreške, naziv datoteke i broj retka**, bez tokena, lozinki, adresa kupaca ili osobnih podataka. Primjer očekivanog ranijeg fatalna: `Access level to BP_Pay_Blocks::$settings must be protected ...`.

**Napomena:** v0.8.1 ostaje SANDBOX-only. WooCommerce Blocks kompatibilnost sada ima lokalni runtime regression test, ali ne zamjenjuje stvarni E2E test aktiviranog WordPress + WooCommerce sustava.
