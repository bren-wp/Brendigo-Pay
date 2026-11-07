<div align="center">

<img src="platform/public/assets/app-icon-512.png" width="110" height="110" alt="Brendigo Pay ikona">

# Brendigo Pay

**Pametna plaćanja za moderan biznis**

Web-platforma, korisnički panel, WordPress, WooCommerce i Android — jedan račun i jedan pregled naplate.

[Web-platforma](https://brendigopay.com) · [Dokumentacija](https://brendigopay.com/docs) · [Cijene](https://brendigopay.com/pricing) · [Autor Brendigo](https://brendigo.com)

[![CI](https://github.com/bren-wp/Brendigo-Pay/actions/workflows/ci.yml/badge.svg)](https://github.com/bren-wp/Brendigo-Pay/actions/workflows/ci.yml)

**Brendigo Pay v0.8.9 — testno izdanje (SANDBOX)**

</div>

> **Status proizvoda:** korisnički panel, licence, WordPress, Android i odabrane integracijske putanje služe za testiranje. **Stvarna online naplata još nije dostupna.** Službeni produkcijski merchant računi, neovisni sigurnosni testovi i završna regulatorna provjera nisu potvrđeni. Ne unosite produkcijske platne ključeve.

## Jednostavnije poslovanje, jedan povezani sustav

- **Jedan račun** — registracija preko weba ili Androida, prijava istom e-adresom i lozinkom u oba smjera.
- **Korisnički panel** — vlastite testne transakcije, naplatni linkovi, povezani pružatelji i licence na jednom mjestu.
- **WordPress bez trgovine** — Gutenberg blokovi, naplatne kartice, poveznice, gumbi i QR kodovi.
- **WooCommerce blagajna** — zasebna testna metoda s podrškom za Classic Checkout i Checkout Blocks.
- **Domenski ograničene licence** — Start, Business i Pro, bez besplatnog paketa.
- **Android aplikacija** — pregled naplate, transakcija i domena, kreiranje i uključivanje platnih linkova te upute za integraciju.
- **Administratorski panel** — pregled računa, potvrđivanje plaćenih planova, status instalacija i nadzor licenci.
- **Odvojeni korisnički podaci** — svaki trgovac vidi samo vlastite transakcije i povezane web-stranice.

### Pogledajte stvarnu web-platformu

Sljedeće fotografije zaslona nastale su pokretanjem lokalnog web sučelja i njegovim snimanjem. **Nisu dizajnerske reference** ni dokaz da je javni servis spreman za produkcijska plaćanja.

| Naslovnica | Korisnička nadzorna ploča |
|:---:|:---:|
| [![Naslovnica web sučelja](docs/screenshots/web-home-desktop.png)](docs/screenshots/web-home-desktop.png) | [![Korisnički panel](docs/screenshots/user-dashboard-desktop.png)](docs/screenshots/user-dashboard-desktop.png) |
| **Naplatna stranica — testno** | **Novi platni link** |
| [![Testni checkout](docs/screenshots/sandbox-checkout-desktop.png)](docs/screenshots/sandbox-checkout-desktop.png) | [![Novi platni link](docs/screenshots/new-payment-link-desktop.png)](docs/screenshots/new-payment-link-desktop.png) |

<details>
<summary><strong>Stvarni prikazi web sučelja na mobilnoj širini</strong></summary>

| Naslovnica | Pregled | Naplata | Novi link |
|:---:|:---:|:---:|:---:|
| [![Mobilna naslovnica](docs/screenshots/web-home-mobile.png)](docs/screenshots/web-home-mobile.png) | [![Mobilni panel](docs/screenshots/user-dashboard-mobile.png)](docs/screenshots/user-dashboard-mobile.png) | [![Mobilna testna naplata](docs/screenshots/sandbox-checkout-mobile.png)](docs/screenshots/sandbox-checkout-mobile.png) | [![Mobilni link](docs/screenshots/new-payment-link-mobile.png)](docs/screenshots/new-payment-link-mobile.png) |

Ovo su prikazi **web sučelja** na mobilnoj širini, a ne screenshotovi nativne Android aplikacije.

</details>

## WordPress i WooCommerce

<div align="center">
<img src="wordpress-org-assets/banner-1544x500.png" alt="Brendigo Pay WordPress vizualni identitet" width="100%">
<p><img src="wordpress-org-assets/icon-256x256.png" alt="Brendigo Pay ikona dodatka" width="80"></p>
</div>

Brendigo Pay dodatak povezuje vašu WordPress instalaciju s vlastitim korisničkim računom i licencom dodijeljenom točnoj HTTPS domeni. Omogućuje naplatne gumbe i kartice te QR prikaz postojećih linkova. Uz WooCommerce povezuje zasebnu testnu blagajnu, a stari način bankovne uplate (BACS) i pouzeće ostaju zasebne WooCommerce opcije.

**Vodiči:** [WordPress korak po korak](https://brendigopay.com/docs/wordpress) · [WooCommerce](https://brendigopay.com/docs/woocommerce) · [Licence](https://brendigopay.com/docs/sites) · [Rješavanje poteškoća](https://brendigopay.com/docs/troubleshooting).

*Priloženi WordPress banner i ikona jesu originalni brendirani resursi, ne screenshotovi aktivne WP administracije. Za fotografije dodatka potreban je zaseban stvarni WordPress/WooCommerce testni okoliš. Ne prikazujemo referentne mockupove kao stvarne snimke.*

## Android — korisnički panel u pokretu

Aplikacija u [mapi /android](android/) prikazuje isti korisnički račun kao web: Pregled, Transakcije, Platne linkove i Web-stranice. Podržava registraciju, prijavu, sinkronizirani status integracija, aktivaciju/deaktivaciju vlastitih linkova i otvaranje uputa unutar aplikacije.

Za testno izdanje na raspolaganju su **instalacijski debug APK** i **nepotpisani release AAB**. AAB se ne može objaviti na Google Playu bez ispravnog izdavateljeva potpisivanja i provjera. Android koristi minSDK 24 i targetSDK 36.

Stvarne Android snimke generiraju se odvojenim emulator testiranjem; u README se uključuju tek nakon potvrde uspješnog snimanja aplikacije.

## Tri paketa, bez besplatnog plana

| Paket | Mjesečno | Broj registriranih web-stranica |
|:---|---:|---:|
| **Start** | **19,90 €** | 1 |
| **Business** | **49,90 €** | 5 |
| **Pro** | **99,90 €** | 25 |

Odabir paketa u webu ili Androidu ne predstavlja naplatu. **Aktivaciju obavlja ovlašteni operater nakon neovisne potvrde uplate.** Kada je paket aktivan, korisnik može registrirati svoje domene i dobiti pripadajuće licence.

## Načini plaćanja i status

| Sustav | Trenutačna podrška |
|:---|:---|
| **Stripe** | TEST integracijska putanja |
| **PayPal** | Sandbox integracijska putanja |
| **Revolut** | Merchant Sandbox integracijska putanja |
| **WooCommerce BACS** | Ugrađena WooCommerce bankovna uplata, **ručna provjera priljeva** |
| **WooCommerce COD** | Ugrađeno plaćanje pouzećem |
| **Monri WebPay** | U planu; nije aktivan platni adapter |
| **CorvusPay** | U planu; nije aktivan platni adapter |

Samo povratak kupca na stranicu za potvrdu nije dokaz stvarne naplate. Konačni status zahtijeva pouzdanu potvrdu platnog pružatelja.

[Pregled integracija](https://brendigopay.com/integrations) · [Stripe](https://brendigopay.com/integrations/stripe) · [PayPal](https://brendigopay.com/integrations/paypal) · [Revolut](https://brendigopay.com/integrations/revolut).

## Instalacija i prvi koraci

1. Preuzmite najnovije izdanje iz [GitHub Releases](https://github.com/bren-wp/Brendigo-Pay/releases).
2. Postavite web-platformu na vlastiti HTTPS hosting prema [uputama za postavljanje](docs/DEPLOYMENT-0.8.9.md). Poslovni identitet i administratorski pristup trebaju biti sigurno konfigurirani prije javnog pristupa.
3. Registrirajte se na webu ili Androidu, odaberite plaćeni plan i pričekajte njegovu aktivaciju.
4. Na webu otvorite **Web-stranice**, dodajte HTTPS domenu i preuzmite odgovarajuću licencu.
5. Prenesite ZIP dodatka kroz **WordPress → Dodaci → Dodaj novi → Prenesi dodatak**.
6. U postavkama Brendigo Paya unesite HTTPS adresu platforme, aktivirajte licencu i pokrenite sinkronizaciju.
7. Konfigurirajte vlastiti testni račun pružatelja i napravite testnu transakciju.

Opširni korisnički vodiči objavljeni su i na web-platformi: [Brzi početak](https://brendigopay.com/docs/quickstart), [WordPress](https://brendigopay.com/docs/wordpress), [WooCommerce](https://brendigopay.com/docs/woocommerce), [Android](https://brendigopay.com/docs/android), [Platni linkovi](https://brendigopay.com/docs/payment-links), [QR kodovi](https://brendigopay.com/docs/qr), [Česta pitanja](https://brendigopay.com/docs/faq).

## Kvaliteta, izgled i sigurnost

Dizajn se razvija prema deset priloženih referenci za web-platformu, korisnički panel, administraciju i WordPress. Prema njima provjeravaju se razmaci, boje, tipografija, pozicije, gumbi, ikone i responzivnost. **Pixel-by-pixel 1:1 podudarnost svih ekrana i uređaja nije još potvrđena stvarnim usporedbama.**

Testni proces uključuje PHP i JavaScript provjere, scenarije prava pristupa, licencija, WooCommerce integracije i Android gradnju. Puni vanjski sigurnosni audit i provjera stvarnih produkcijskih uplata još nisu dovršeni.

[Pravila sigurnosti](docs/SECURITY.md) · [Vizualna provjera](docs/PIXEL-PARITY.md) · [Testovi](tests/) · [Upute za izdanje](docs/RELEASE-CHECKLIST.md).

---

<div align="center">

**Brendigo Pay** · Pametna plaćanja za moderan biznis

**Built with [Brendigo](https://brendigo.com)**

© 2026 Brendigo. Licencije izvornog koda i trećih strana navedene su u repozitoriju.

</div>
