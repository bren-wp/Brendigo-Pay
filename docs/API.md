# Brendigo Pay API — v0.3.0 (SANDBOX)

**Svi /api/v1 zaštićeni endpointi** zahtijevaju `Authorization: Bearer bp_test_...`; ključ je izdan za jedan HTTPS origin, a platforma nema live način rada.

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
