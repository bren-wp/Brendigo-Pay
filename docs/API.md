# Brendigo Pay API — v0.8.4 (SANDBOX)

**WordPress zaštićeni API** zahtijeva `Authorization: Bearer bp_test_...` s ključem izdanim za jedan HTTPS origin. **Android korisnički API** koristi zaseban `bp_mobile_...` token, koji ne može aktivirati WordPress licencije ni pristupiti administratorskim endpointima. Platforma nema live način rada.

## Endpointi

| Metoda | Ruta | Autorizacija | Namjena |
|---|---|---|---|
| GET | `/api/v1/health` | javno | Stanje servisa (`starter-test`) |
| GET | `/api/v1/me` | token | Trgovac, origin, mode i `providers` boolean mapa |
| GET | `/api/v1/links/{id}` | javno | Aktivna javna poveznica; bez osjetljivih podataka |
| POST | `/api/v1/links` | token | Kreiranje poveznice |
| POST | `/api/v1/checkout` | token | Kreiranje testnog checkouta |
| GET | `/api/v1/payments/{id}` | token | Status WooCommerce transakcije trgovca |
| POST | `/webhooks/stripe/{user_id}` | Stripe-Signature | Potvrda izravne Stripe TEST sesije |
| POST | `/webhooks/stripe-connect` | Stripe-Signature + Connect account mapping | Potvrda Stripe Connect TEST sesije |
| POST | `/webhooks/paypal/{user_id}` | PayPal signed event + remote verify | Potvrda PayPal Sandbox capturea |
| POST | `/webhooks/revolut/{user_id}` | Revolut-Signature + remote order GET | Potvrda Revolut Sandbox narudžbe |

## Primjeri

### Naplatna poveznica

```json
{"title":"Izrada web-stranice","amount":"150.00"}
```

`POST /api/v1/links` vraća `201`, `id`, `url`. Javni URL `/p/{id}` prikazuje samo pružatelje koje je trgovac konfigurirao. Trgovac može uključiti/isključiti poveznicu u web nadzornoj ploči.

### Checkout u WooCommerceu

```json
{
  "provider":"paypal",
  "amount":"49.90",
  "description":"WooCommerce narudžba 101",
  "reference":"wp_sha256_referenca",
  "success_url":"https://shop.example/checkout/order-received/101/?key=...",
  "cancel_url":"https://shop.example/checkout/order-pay/101/"
}
```

`provider` može biti `stripe`, `paypal`, `revolut` (zadana vrijednost `stripe`). Odgovor sadrži `transaction_id`, `status`, `checkout_url` i `provider`. Checkout URL-ovi i osjetljive API vjerodajnice ne smiju se tretirati kao dokaz uplate.

### Autorizirana provjera statusa

`GET /api/v1/payments/{id}` za transakcije s `wp_` referencom vraća:

```json
{"id":"64_ili_32_hex","status":"pending","amount_minor":4990,"currency":"EUR","reference":"wp_...","provider":"paypal"}
```

Status se može promijeniti u `paid` samo kroz provjeren odgovor pružatelja. WooCommerce provjerava **vlastitu referencu, iznos, valutu i pružatelja** prije `payment_complete`. Web-platforma ne prima kartične podatke.

## Ograničenja

Ovaj API nije konačni javni ugovor. Postoji opcionalni Stripe Connect Standard OAuth **samo u TEST načinu rada**. Ne postoje live OAuth onboarding, PayPal/Revolut partnerski OAuth, refund, subscription niti billing endpointi. Nisu testirani stvarni sandbox API pozivi ni end-to-end webhooks kod pružatelja. Javna poveznica ne omogućuje promjenu iznosa.


### `GET /api/v1/links` (v0.6.0)

Zaštićeno istim trgovčevim Bearer tokenom kao i `GET /api/v1/me`. Vraća do 100 najnovijih **aktivnih** naplatnih poveznica isključivo vlasnika tokena (`items`, `limit`, `total`). Svaki zapis sadržava `id`, `title`, `description`, `amount_minor`, `currency`, `url`. Namijenjeno je WordPress Gutenberg odabiru poveznica. Odbija neautorizirane zahtjeve i ne dopušta uvid u tuđe poveznice.

WordPress REST proxy `/wp-json/brendigo-pay/v1/links` zahtijeva korisničku ovlast `edit_posts` te nikada ne vraća API token ili tajne platnih pružatelja.


## Android merchant API (v0.8.4)

Odvojen prostor `/api/v1/mobile/*`, HTTPS only. API nikada ne prima kartične podatke. Svi zahtjevi koriste `Accept: application/json`; POST zahtjevi `Content-Type: application/json`.

| Metoda | Ruta | Autorizacija | Namjena |
|---|---|---|---|
| POST | `/api/v1/mobile/login` | javno uz ograničenje pokušaja | Izdaj zaseban mobilni token nakon provjere web lozinke (7 dana) |
| GET | `/api/v1/mobile/overview` | `bp_mobile_...` | Stvarni sandbox prihodi, transakcije, linkovi, licence, status paketa i domena samo vlastitog računa |
| POST | `/api/v1/mobile/links` | `bp_mobile_...` | Kreiraj platni link na aktiviranoj domeni iz aktivnog plaćenog plana |
| POST | `/api/v1/mobile/links/{id}/toggle` | `bp_mobile_...` | Aktiviraj/deaktiviraj vlastiti link, uz provjeru paketa i zaključavanje zapisa |
| POST | `/api/v1/mobile/logout` | `bp_mobile_...` | Opozovi mobilni token bez odgode |

`POST /api/v1/mobile/login` šalje JSON s `email` i `password`. Mobilni token se vraća samo jednom, u `token`, s `expires_in=604800` i `token_type=Bearer`. Server sprema SHA-256 hash tokena, ograničava pokušaje po računu i IP-u istim rate-bucketom kao web prijava, a Android token čuva preko Android Keystorea. Nakon isteka ili odjave vraća HTTP 401. Nije podržan refresh token.

`GET /api/v1/mobile/overview` sadrži `merchant`, `subscription`, `metrics`, `sites`, `links` i `transactions`. Brojke su iz istih korisničkih JSON zapisa kao web i WP, uz limit zadnjih 50 linkova i transakcija. Nije predviđeno vraćanje drugih trgovaca niti lozinki ili API tajni.

`POST /api/v1/mobile/links` prihvaća `{"site_id":"24hex","title":"Naziv usluge","amount":"49.90","description":"Opis"}`, nakon čega link postaje vidljiv web-panelu i WP Gutenberg prikazu. Plaćanje ostaje SANDBOX.

**Sigurnosne granice:** aplikacija nije administrator cijele platforme; isključivo merchant token. Izvod, idempotency i transakcijska povijest po vremenskom rasponu dostupni su na webu; nativno su zasad dostupni osnovni pregledi. Produkcijski audit, mobilni pen-test i device E2E još nisu završeni.
