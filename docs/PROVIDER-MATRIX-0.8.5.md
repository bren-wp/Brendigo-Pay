# Brendigo Pay — službeni načini naplate i status integracija (0.8.5)

Nikada ne prikazuj pružatelja kao aktivnog ako API, webhook, identitet trgovca ili njegov testni račun nisu potvrđeni. Vanjski pružatelji plaćanja izvršavaju transakcije; Brendigo Pay nije financijska institucija i ne posjeduje sredstva kupaca.

| Sustav | Tip | Stanje u kodu 0.8.5 | Kako se potvrđuje uplata |
|---|---|---|---|
| Stripe Checkout | Međunarodni | TEST adapter, bez potvrđenog punog vanjskog E2E | Server-side signature provjera i provjera iznosa/narudžbe |
| PayPal Orders | Međunarodni | Sandbox adapter, bez potvrđenog punog vanjskog E2E | Verificiran PayPal Webhook/Capture |
| Revolut Merchant | Međunarodni | Sandbox adapter, bez potvrđenog punog vanjskog E2E | Potpisani webhook + potvrda naloga |
| Monri WebPay | Hrvatski/regionalni | **Planirana integracija**, nije moguće odabrati na checkoutu | Test merchant, službeni WebPay API, potpis/digest i verifikacija prije `paid` |
| CorvusPay | Hrvatski/regionalni | **Planirana integracija**, nije moguće odabrati na checkoutu | Merchant test račun, potpisani API zahtjevi, merchant certifikat i provjera statusa |
| WooCommerce BACS — direktna uplata | Domaće banke / SEPA | **Zasebna ugrađena WooCommerce metoda**, ne Brendigo Pay adapter | Trgovac ručno potvrđuje priljev prije promjene `On hold` |
| WooCommerce COD — pouzeće | Hrvatska | **Zasebna ugrađena WooCommerce metoda**, ne Brendigo Pay adapter | Trgovac potvrđuje naplatu pri isporuci |
| HUB3/EPC QR nalozi | Domaća / SEPA | Koncept za kasniju implementaciju, bez automatske bankovne potvrde | Ručna provjera ili zasebna bankovna integracija |

**Službena dokumentacija:**

- Stripe Checkout: https://docs.stripe.com/payments/checkout
- PayPal API: https://developer.paypal.com/docs/api/orders/v2/
- Revolut Merchant API (verzija 2026-08-17): https://developer.revolut.com/docs/api/merchant
- Monri WebPay/Redirect: https://ipg.monri.com/en/documentation
- CorvusPay Frame: https://github.com/corvuspay/corvus-frame-integration-doc
- WooCommerce BACS: https://woocommerce.com/document/bacs/
- WooCommerce COD: https://woocommerce.com/document/cash-on-delivery/

**Integracijska pravila:** svaki adapter mora koristiti testni račun i vlastitu trgovčevu vjerodajnicu, bez prikaza kartičnih podataka u Brendigo Pay UI-u. Povratak na success URL **nije** dokaz uplate. Status `paid` potvrđuje se tek nakon verifikacije događaja, referentnog ID-a, iznosa i valute. Za Monri/CorvusPay nisu dobiveni testni merchant račun, certifikati ni ključ, pa u 0.8.5 nema lažne produkcijske aktivacije.

Za WooCommerce trgovce koji žele odmah omogućiti *ručni* hrvatski bankovni transfer, službena ugrađena opcija je **WooCommerce → Postavke → Plaćanja → Bankovna uplata (BACS)**. WooCommerce postavlja narudžbu **On hold** i trgovac ručno potvrđuje novac prije isporuke. Brendigo Pay neće automatski pretvoriti takvu narudžbu u `paid`.
