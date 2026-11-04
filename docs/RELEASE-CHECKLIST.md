# Brendigo Pay v0.6.0 — kontrola izdanja

**Status:** razvojni SANDBOX; stvarna plaćanja nisu podržana.

## Potvrđeno lokalno

- [x] PHP syntax lint za sve datoteke.
- [x] JSON/store/CSRF/web-sesije, prijava, tokeni, naplatne poveznice, odvajanje korisničkih računa, API i CSV.
- [x] Stripe, Revolut HMAC unit testovi i negativni testovi; Stripe Connect OAuth state/URL i odbijanje neautoriziranog callbacka.
- [x] PayPal validacija capture amount/currency/reference/order ID (pure unit test).
- [x] Revolut potvrda amount/currency/state/order ID (pure unit test).
- [x] HTTP smoke: PayPal/Revolut sandbox forms, javni izbor pružatelja, odbijanje nepotpisanog webhooka.
- [x] WooCommerce classic mock: Stripe, PayPal, Revolut provider routing, payment completion, cancelled state.
- [x] Šifrirani JSON backup/restore, odbijanje pogrešne lozinke i prepisivanja.

## Nije potvrđeno — obvezno prije live

- [ ] Stvarni sandbox integracijski Stripe/PayPal/Revolut pozivi, kreiranje i plaćanje s testnim karticama, webhookovi, reversali i failover.
- [ ] Test WooCommerce classic + Checkout Block + HPOS na stvarnoj instalaciji i raznim verzijama.
- [x] Stripe Connect Standard OAuth sandbox adapter i negativni offline testovi (bez stvarnog spajanja).
- [ ] Stripe Connect stvarni OAuth E2E + Connect webhook u sandboxu; PayPal partner odobrenje i Revolut dopuštene partnerske ovlasti.
- [ ] TOTP/passkeys, verifikacija e-pošte, password recovery i GDPR self-service.
- [ ] Refunds, disputes, subscriptions, entitlements, fakturiranje vlastitih Brendigo paketa.
- [ ] Persisted queue/retry, eksplicitni idempotentni event log, webhook redrive, reconciliation i sustavni cron.
- [ ] Regulatorna ocjena (PSD2/SCA, PCI, GDPR, hrvatski porezni/fiskalni uvjeti), uvjeti usluge, podrška i prigovori.
- [ ] Produkcijski sigurnosni audit, penetracijski test i test opterećenja.
- [ ] Backup/restore test na stvarnoj konfiguraciji s originalnim `app_key`.

**Zabrana live ključeva ostaje na snazi.**

## Vizualne provjere v0.6.0

- [x] HTML/PHP stranice izvedene u pregledniku na 1448, 768 i 390 px (samostalna platforma).
- [x] Provjera vodoravnog prelijevanja u pregledniku, provjera JavaScript pogrešaka i funkcionalnog modalnog prozora.
- [x] Stvarni screenshotovi samostalnog weba, korisničkog panela i sandbox checkouta (desktop/mobitel).
- [ ] Pixel-by-pixel potvrda svakog ekrana u odnosu na 10 dizajnerskih referenci (nije postignuta).
- [ ] Stvarni WordPress Gutenberg i WooCommerce Classic/Blocks snimci za vizualnu i funkcionalnu usporedbu.
- [ ] WordPress Plugin Check i WordPress Coding Standards provjera na službenom okruženju.
