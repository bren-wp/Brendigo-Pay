# Brendigo Pay — arhitektura v0.3.0

```text
Klijent / WordPress / WooCommerce
              │ HTTPS + domain-bound bp_test token
              ▼
     Brendigo Pay PHP API
              │
              ├── JSON storage/data (users, links, tokens, transactions)
              ├── Credentials (libsodium secretbox encrypted)
              │
              ├── Stripe TEST       ── signed Stripe webhook ──┐
              ├── PayPal Sandbox    ── capture/webhook verify ─┤
              └── Revolut Sandbox   ── signed webhook + GET ───┤
                                                               ▼
                                                JSON transaction=paid
                                                               │
                                   WooCommerce authenticated status poll
                                                               │
                                   Verify reference+amount+EUR+provider
                                                               ▼
                                                 Woo order complete
```

Domena klijentskog API ključa mora se podudarati s originom povratnih URL-ova. Podatke platnih kartica prikuplja sam pružatelj na hosted checkoutu. Kupčev redirect **nikada nije potvrda transakcije**. Provjereni provider događaji rade s idempotentnim `flock` ažuriranjem. Nema naplate platformskih provizija ni zadržavanja sredstava.

PayPal potvrda temelji se na `COMPLETED` order+capture, `reference_id`, EUR i točnom iznosu. Revolut HMAC provjera zahtijeva payload i timestamp u granici 5 minuta; zatim se preuzima originalna narudžba od sandbox API-ja. Stripe zahtijeva valjani HMAC i jednak session ID, iznos, EUR, `payment_status=paid`.

Zašto JSON: jednostavna instalacija na jednom hostu bez vlastitog SQL servisa. JSON nije prikladan za višeserversko transakcijsko knjiženje i operativni rad financijske institucije. Za buduću produkciju potrebni su pouzdani redovi webhook zadataka, rezervacijsko i reconciliation knjigovodstvo i kontrola oporavka; trenutačna implementacija to nema.

WordPress/WooCommerce zadržavaju **svoju postojeću bazu podataka**. U Brendigo Payu nema kopije narudžbi ni kartičnih podataka.
