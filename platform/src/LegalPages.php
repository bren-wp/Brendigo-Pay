<?php
declare(strict_types=1);

namespace BrendigoPay;

/**
 * Informational Croatian legal/service pages. Legal identity must be configured before public launch.
 * Do not imply PCI, financial-institution, PSD2 or PSP-production certification.
 *
 * @return array{0:string,1:string}|null
 */
function legal_page(string $path, array $config): ?array
{
    $operator = trim((string) ($config['legal_operator_name'] ?? ''));
    $address = trim((string) ($config['legal_address'] ?? ''));
    $contact = trim((string) ($config['legal_contact_email'] ?? ''));
    $operatorLabel = $operator !== '' ? e($operator) : 'Identitet operatera nije objavljen';
    $addressLabel = $address !== '' ? e($address) : 'Poslovna adresa nije unesena';
    $emailLabel = filter_var($contact, FILTER_VALIDATE_EMAIL)
        ? '<a href="mailto:' . e($contact) . '">' . e($contact) . '</a>'
        : '<a href="https://brendigo.com" rel="noopener noreferrer">Brendigo — službena web-stranica</a>';
    $notice = $operator === '' || $address === '' || !filter_var($contact, FILTER_VALIDATE_EMAIL)
        ? '<div class="bp-legal-notice" role="note"><strong>Podaci operatera još nisu potpuni.</strong> '
            . 'Prije javnog komercijalnog puštanja odgovorna osoba treba popuniti podatke o pružatelju usluge '
            . 'u privatnoj konfiguraciji i pravno potvrditi sadržaj ovih stranica.</div>'
        : '';
    $identity = '<dl class="bp-legal-identity"><div><dt>Operater platforme</dt><dd>' . $operatorLabel
        . '</dd></div><div><dt>Adresa</dt><dd>' . $addressLabel
        . '</dd></div><div><dt>Kontakt</dt><dd>' . $emailLabel . '</dd></div></dl>';

    $pages = [
        '/about' => ['O platformi',
            '<p>Brendigo Pay povezuje trgovca, vlastitu WordPress stranicu ili WooCommerce trgovinu i '
            . 'postojeće račune vanjskih platnih pružatelja u jedinstvenom korisničkom panelu.</p>'
            . '<h2>Što sustav pruža</h2><p>Upravljanje testnim platnim poveznicama, QR prikazima, licencama '
            . 'po domeni i pregledom transakcijskih statusa. Mobilna aplikacija i web-panel koriste isti račun '
            . 'i isti poslužiteljski izvor podataka.</p><h2>Važna ograničenja</h2><p>Trenutačno izdanje radi '
            . 'u testnom (sandbox) načinu rada; stvarna naplata nije uključena. Brendigo Pay nije banka, '
            . 'institucija za elektronički novac ni samostalna licencirana platna institucija i ne drži sredstva kupaca.</p>'],
        '/privacy' => ['Politika privatnosti',
            '<h2>Tko upravlja podacima?</h2>' . $identity
            . '<h2>Koje podatke obrađujemo?</h2><p>Registracijom nastaju naziv trgovca, e-adresa, '
            . 'hash lozinke, traženi paket i opcionalna web-domena. U korisničkom panelu pohranjuju se '
            . 'licence, domene, platne poveznice, statusi testnih uplata i revizijski događaji. '
            . 'Prilikom prijave ograničavamo broj pokušaja prema IP adresi i računu.</p>'
            . '<h2>Zašto i koliko dugo?</h2><p>Podaci su potrebni za autentikaciju, sigurnost, '
            . 'izvršavanje ugovorene usluge i evidenciju promjena. Rokovi čuvanja pojedinih zapisa '
            . 'moraju biti utvrđeni u pravilima operatera prije produkcijskog rada. '
            . 'Za zahtjev pristupa, ispravka ili brisanja obratite se kontaktu navedenom iznad.</p>'
            . '<h2>Vanjski pružatelji</h2><p>Trgovac zasebno povezuje račune Stripe, PayPal '
            . 'ili Revolut u testnom načinu rada. Njihova pravila privatnosti primjenjuju se '
            . 'na korisnike njihovih usluga. Brendigo Pay ne pohranjuje podatke platnih kartica.</p>'
            . '<h2>Sigurnost</h2><p>Lozinke se čuvaju kao Argon2id sažeci, mobilni tokeni '
            . 'kao sažeci na poslužitelju, a Android lokalno šifrira token Android Keystoreom. '
            . 'Ni jedna mjera ne može jamčiti apsolutnu sigurnost.</p>'],
        '/terms' => ['Uvjeti korištenja',
            '<h2>Opseg usluge</h2><p>Brendigo Pay je softversko rješenje za povezivanje trgovaca '
            . 's vanjskim platnim pružateljima i njihove WordPress/WooCommerce instalacije. '
            . 'Nije depozitna ili bankarska usluga i ne prima sredstva za račun kupaca.</p>'
            . '<h2>Računi i licencije</h2><p>Svaki korisnik vidi vlastite zapise. '
            . 'Paketi Start (1 domena), Business (5 domena) i Pro (25 domena) plaćeni su. '
            . 'Izbor paketa ili registracija ne aktiviraju pravo korištenja: operater mora '
            . 'neovisno potvrditi uplatu. Ključevi se smiju aktivirati samo na dodijeljenoj domeni.</p>'
            . '<h2>Ograničenja odgovornosti</h2><p>Testno izdanje nije dostupno za stvarne uplate. '
            . 'Prije produkcijske upotrebe potrebno je potvrditi identitet operatera, zaseban poslovni '
            . 'odnos s platnim pružateljima, pravnu usklađenost te uvjete podrške i otkaza.</p>'
            . '<h2>Kontakt</h2>' . $identity],
        '/cookies' => ['Kolačići i lokalna pohrana',
            '<p>Web prijava koristi funkcionalni sesijski kolačić (HttpOnly, SameSite=Lax; Secure uz HTTPS) '
            . 'za održavanje prijave i zaštitu obrazaca. Ovaj projekt ne uključuje vanjski '
            . 'analitički/marketinški SDK ni kolačiće za ciljano oglašavanje.</p>'
            . '<p>Android aplikacija pohranjuje šifrirani pristupni token u privatnoj pohrani uređaja. '
            . 'Ako se naknadno dodaju alati trećih strana, ova obavijest mora biti ažurirana '
            . 'i pravna osnova provjerena prije njihove aktivacije.</p>'],
        '/refund-policy' => ['Otkazivanje i povrati',
            '<p>Na platformi trenutno nije uključena automatska naplata pretplata ili '
            . 'obrada stvarnih kartičnih uplata. Pakete operater ručno aktivira nakon '
            . 'neovisne provjere uplate.</p><p>Pravila otkazivanja, zakonska prava potrošača, '
            . 'način traženja povrata i rokovi obrade zahtijevaju ugovornu potvrdu prije '
            . 'produkcijske prodaje. Za upit koristite <a href="/contact">kontakt operatera</a>. '
            . 'Ova informativna stranica ne mijenja zakonska prava korisnika.</p>'],
        '/contact' => ['Kontakt i podrška', '<p>Za upite o računima, domenama, '
            . 'licencama ili eventualnoj sigurnosnoj prijavi koristite službeni kontakt '
            . 'operatera naveden u nastavku.</p>' . $identity
            . '<p>Nikada ne šaljite lozinku, privatni API ključ ili podatke platne kartice u poruci podršci.</p>'],
        '/security' => ['Sigurnost platforme',
            '<p>Brendigo Pay odvaja pristup trgovca i WordPress instalacije. Android pristupa '
            . 'zasebnim mobilnim tokenom, a WordPress aktivacijom licence vezane uz domenu '
            . 'i instalaciju. Podaci su filtrirani prema vlasniku računa na poslužitelju.</p>'
            . '<p>Koristimo ograničavanje pokušaja prijave, CSRF zaštitu web obrazaca, '
            . 'HTTPS provjere i potpisane webhooks za podržane sandbox pružatelje. '
            . 'Trenutačno nema završene neovisne produkcijske certifikacije ili '
            . 'potpunog testiranja svih pružatelja.</p>'],
        '/providers' => ['Načini plaćanja i integracije',
            '<p>Trenutačno testirane integracijske putanje uključuju Stripe TEST, PayPal Sandbox '
            . 'i Revolut Sandbox. Svaki trgovac mora imati vlastiti račun i pripadajuće vjerodajnice. '
            . 'Nisu dostupne produkcijske uplate u ovoj verziji.</p>'
            . '<h2>Dodatni domaći pružatelji u razmatranju</h2><p>Monri WebPay i CorvusPay '
            . 'imaju službene tehničke upute i zasebno testno odobrenje. '
            . 'Nisu aktivni platni adapteri Brendigo Paya, dok ne prođu '
            . 'potpisivanje zahtjeva, provjeru odgovora, autorizaciju trgovca '
            . 'i vanjske end-to-end testove.</p>'
            . '<p><a href="https://ipg.monri.com/en/documentation" rel="noopener noreferrer">Monri službena dokumentacija</a>'
            . ' · <a href="https://github.com/corvuspay/corvus-frame-integration-doc" rel="noopener noreferrer">'
            . 'CorvusPay integracijska dokumentacija</a></p>'
            . '<h2>Bankovne uplate</h2><p>Hrvatski nalog za uplatu/HUB3 i EPC SEPA QR kodovi '
            . 'za bankovne transfere nisu automatski potvrđene transakcije. '
            . 'Takvu uplatu nije dopušteno prikazati kao završenu bez provjere priljeva.</p>'],
    ];

    if (!isset($pages[$path])) {
        return null;
    }
    [$title, $body] = $pages[$path];
    return [$title, '<section class="bp-legal-page"><div class="bp-kicker">BRENDIGO PAY · INFORMACIJE</div>'
        . '<h1>' . e($title) . '</h1>' . $notice
        . '<article class="bp-surface bp-legal-article">' . $body . '</article>'
        . '<p class="bp-legal-updated">Ažurirano: 4. studenoga 2026. · '
        . 'Testni proizvod, uvjeti i pružatelji zahtijevaju završnu provjeru prije stvarnih uplata.</p></section>'];
}
