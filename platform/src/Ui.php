<?php
/**
 * Brendigo Pay visual system. Escaped dynamic output, no inline styles or scripts.
 * Screens remain functional against the existing JSON-backed handlers.
 */
declare(strict_types=1);

use function BrendigoPay\e;
use function BrendigoPay\minor_format;

function bp_brand(): string
{
    return '<span class="bp-brand"><img src="/assets/brand-mark.svg" alt="" width="37" height="37"><span>Brendigo <b>Pay</b></span></span>';
}

function bp_site_home(): string
{
    ob_start(); ?>
<section class="bp-home-hero" aria-labelledby="hero-heading">
    <div class="bp-home-copy">
        <span class="bp-kicker">⚡ FINTECH SAAS • POVEŽITE SVOJE PLATNE RAČUNE</span>
        <h1 id="hero-heading">Pametna naplata za <span>WordPress, WooCommerce</span> i web</h1>
        <p>Povežite postojeće Stripe, PayPal i Revolut testne račune. Upravljajte naplatnim poveznicama, QR kodovima i integracijama na jednom mjestu.</p>
        <div class="bp-actions"><a href="/pricing" class="btn">Odaberi paket <span aria-hidden="true">→</span></a><a href="/login" class="btn secondary">Pogledaj nadzornu ploču</a></div>
        <div class="bp-benefits"><span>✓ Jednostavna integracija</span><span>✓ Plaćanja kod vanjskih pružatelja</span><span>✓ WordPress, WooCommerce i Android</span></div>
    </div>
    <div class="bp-hero-art" aria-label="Prikaz sučelja Brendigo Paya">
        <div class="bp-laptop">
            <div class="bp-laptop-bar"><?= bp_brand() ?></div>
            <div class="bp-laptop-body"><div class="bp-mini-sidebar"><b>⌂ Pregled</b><span>▤ Plaćanja</span><span>⇄ Transakcije</span><span>◇ Integracije</span></div><div class="bp-mini-main"><strong>Pregled</strong><small>Sažetak plaćanja</small><div class="bp-mini-metrics"><div><small>Ukupna naplata</small><b>2.860,00 €</b></div><div><small>Testne uplate</small><b>36</b></div></div><svg class="bp-line-chart" viewBox="0 0 300 100" role="img" aria-label="Ilustrativni grafikon"><path d="M0 94 L0 74 Q24 65 44 71 T88 44 T131 58 T171 30 T218 34 T259 17 T300 6 L300 100 L0 100 Z" fill="#dbe7ff"/><path d="M0 74 Q24 65 44 71 T88 44 T131 58 T171 30 T218 34 T259 17 T300 6" fill="none" stroke="#315cff" stroke-width="3"/></svg></div></div>
        </div>
        <div class="bp-floating-payment"><span class="bp-kicker">◈ PLATNI LINK</span><h3>Online plaćanje</h3><p>Usluga / proizvod <b>49,90 €</b></p><div>◉ Stripe</div><div>◯ PayPal</div><div>◯ Revolut</div><span class="bp-fake-button">Plati 49,90 €</span><small>Testni prikaz sučelja</small></div>
    </div>
</section>
<section class="bp-feature-strip" aria-label="Prednosti"><div><span class="bp-feature-icon">⚡</span><div><h3>Postojeći računi</h3><p>Povežite račune platnih pružatelja.</p></div></div><div><span class="bp-feature-icon">◇</span><div><h3>Prihvat plaćanja</h3><p>Web, WordPress i WooCommerce.</p></div></div><div><span class="bp-feature-icon">♢</span><div><h3>Upravljanje računom</h3><p>Jedinstveni pregled vaših povezanih usluga.</p></div></div><div><span class="bp-feature-icon">▥</span><div><h3>Izvještaji</h3><p>Praćenje statusa i transakcija.</p></div></div></section>
<section id="mogucnosti" class="bp-home-grid" aria-label="Integracije i mogućnosti">
    <article class="bp-surface"><h2>Povežite svoje platne račune</h2><p>Konfigurirajte testne integracije iz vlastite nadzorne ploče i upravljajte statusom na Androidu.</p><div class="bp-provider-grid"><span>↗ Stripe</span><span>↗ PayPal</span><span>↗ Revolut</span></div><p><a href="/integrations">Sve integracije i status podrške →</a></p></article>
    <article class="bp-surface"><h2>WordPress i WooCommerce</h2><p>Jedan dodatak za obične WordPress stranice i WooCommerce trgovine.</p><div class="bp-integration-chips"><span>ⓦ WordPress</span><span>▣ WooCommerce</span></div></article>
    <article class="bp-surface bp-home-link-card"><h2>Platni linkovi i QR kodovi</h2><p>Za naplatu ne treba vam vlastita web-trgovina. Podijelite poveznicu ili QR kod.</p><a class="btn secondary" href="/register">Kreiraj platni link →</a><span class="bp-deco-qr" aria-hidden="true">▦</span></article>
    <article class="bp-surface"><h2>Jasna razvojna faza</h2><p>Trenutačno je dostupno sandbox/testno izdanje. Stvarna plaćanja nisu aktivirana.</p><a class="btn secondary" href="/pricing">Pogledaj predložene pakete →</a></article>
</section>
<section class="bp-home-proof" aria-labelledby="bp-home-proof-title">
  <div class="bp-proof-story">
    <h2 id="bp-home-proof-title">Povezano poslovanje na jednom mjestu</h2>
    <p>Web-nadzorna ploča, WordPress dodatak i Android aplikacija dijele isti račun, licence i pregled testnih transakcija.</p>
    <strong>Jedan račun · više povezanih kanala</strong>
  </div>
  <div class="bp-proof-story">
    <h2>Kontrola pristupa i sigurnosti</h2>
    <p>Svaki korisnik vidi samo svoje podatke. Plaćanja se testiraju kod službenih pružatelja, a status potvrđuje poslužitelj.</p>
    <strong>Odvojena korisnička prava</strong>
  </div>
  <div class="bp-proof-fact"><span aria-hidden="true">◇</span><strong>3</strong><small>podržana sandbox pružatelja</small></div>
  <div class="bp-proof-fact"><span aria-hidden="true">▣</span><strong>2</strong><small>WordPress i WooCommerce</small></div>
  <div class="bp-proof-fact"><span aria-hidden="true">✓</span><strong>1</strong><small>zajednički korisnički račun</small></div>
</section>
<section class="bp-bottom-cta bp-bottom-cta-home" aria-labelledby="bp-home-cta-title">
  <div class="bp-cta-copy"><h2 id="bp-home-cta-title">Spremni pojednostaviti naplatu?</h2><p>Povežite svoje Stripe, PayPal i Revolut testne račune, integrirajte Brendigo Pay u WordPress ili WooCommerce i pratite status na jednom mjestu.</p></div>
  <div class="bp-cta-actions"><a href="/register" class="btn btn-white">Započni s paketom <span aria-hidden="true">→</span></a><a href="/docs" class="btn bp-cta-outline">Pogledaj vodič</a></div>
  <div class="bp-cta-visual" aria-hidden="true"><div class="bp-cta-payment-card"><span class="bp-cta-card-brand">Brendigo <b>Pay</b></span><span class="bp-cta-chip">▦ <i>)))</i></span></div></div>
</section>
<section class="bp-showcase" aria-labelledby="bp-showcase-title">
  <header class="bp-showcase-heading"><span class="bp-kicker">PLATFORMA ZA VAŠE POSLOVANJE</span><h2 id="bp-showcase-title">Jedno mjesto za različite načine naplate</h2><p>Web-stranica, WordPress, WooCommerce i platne poveznice rade s istom platformom.</p></header>
  <div class="bp-showcase-steps"><article><span class="bp-number">01</span><h3>Povežite testni račun</h3><p>Koristite vlastite podatke platnog pružatelja. Dostupnost ovisi o konfiguraciji.</p></article><article><span class="bp-number">02</span><h3>Objavite naplatnu poveznicu</h3><p>Postavite gumb ili QR kod na WordPress, web ili u dokument za kupca.</p></article><article><span class="bp-number">03</span><h3>Pratite status plaćanja</h3><p>Server provjerava odgovor pružatelja prije označavanja testne transakcije plaćenom.</p></article></div>
</section>
<?php return (string) ob_get_clean();
}

function bp_site_pricing(): string
{
    ob_start(); ?>
<section class="bp-pricing-intro">
    <span class="bp-kicker">⚡ TRI PLAĆENA PAKETA • LICENCIJE PO DOMENI</span>
    <h1>Jednostavne i transparentne <span>cijene</span></h1>
    <p>Odaberite plan prema broju WordPress stranica koje povezujete s Brendigo Payom. Nema besplatnog paketa. Cijene su prijedlog cjenika; online naplata pretplate još nije omogućena.</p>
    <div class="bp-benefits"><span>✓ Tri jasna paketa</span><span>✓ Zaseban ključ za svaku stranicu</span><span>✓ Ograničenja na poslužitelju</span><span>✓ Bez automatske naplate</span></div>
    <div class="bp-pricing-illustration" aria-hidden="true"><span class="bp-pricing-illustration-brand">Brendigo <b>Pay</b></span><span class="bp-pricing-card-chip">▦　)))</span></div>
</section>
<div class="bp-price-cards">
    <article class="bp-surface">
        <span class="bp-feature-icon">⚡</span><h2>Start</h2>
        <p>Za jednu web-stranicu ili manju trgovinu.</p>
        <p class="bp-price-value">19,90 € <small>/ mj</small></p>
        <strong class="bp-tier-sites">1 web-stranica</strong>
        <ul><li>1 aktivna licencija za WordPress</li><li>Platni linkovi i QR kodovi (TEST)</li><li>WordPress dodatak</li><li>Osnovni izvještaji</li></ul>
        <a href="/register?plan=start" class="btn secondary">Odaberi Start →</a>
    </article>
    <article class="bp-surface bp-price-featured">
        <span class="bp-price-ribbon">NAJPOPULARNIJE · PRIJEDLOG</span>
        <span class="bp-feature-icon">◇</span><h2>Business</h2>
        <p>Za trgovine koje upravljaju s više domena.</p>
        <p class="bp-price-value">49,90 € <small>/ mj</small></p>
        <strong class="bp-tier-sites">5 web-stranica</strong>
        <ul><li>Sve iz Start paketa</li><li>5 zasebnih WordPress licenci</li><li>WooCommerce integracija (TEST)</li><li>Stripe / PayPal / Revolut sandbox</li><li>Detaljniji izvještaji</li></ul>
        <a href="/register?plan=business" class="btn btn-white">Odaberi Business →</a>
    </article>
    <article class="bp-surface">
        <span class="bp-feature-icon">♛</span><h2>Pro</h2>
        <p>Za agencije i sustave s mnogo web-stranica.</p>
        <p class="bp-price-value">99,90 € <small>/ mj</small></p>
        <strong class="bp-tier-sites">25 web-stranica</strong>
        <ul><li>Sve iz Business paketa</li><li>25 zasebnih WordPress licenci</li><li>Upravljanje web-stranicama</li><li>API pristup po instalaciji (TEST)</li><li>Administracija licencija</li></ul>
        <a href="/register?plan=pro" class="btn secondary">Odaberi Pro →</a>
    </article>
</div>
<div class="bp-pricing-details">
    <section class="bp-surface"><h2>▥ Usporedba paketa</h2>
        <div class="table-wrap"><table><thead><tr><th>Značajka</th><th>Start</th><th>Business</th><th>Pro</th></tr></thead><tbody>
            <tr><td>Mjesečna cijena (prijedlog)</td><td>19,90 €</td><td>49,90 €</td><td>99,90 €</td></tr>
            <tr><td>Najveći broj web-stranica</td><td><strong>1</strong></td><td><strong>5</strong></td><td><strong>25</strong></td></tr>
            <tr><td>Licenca za svaku WordPress stranicu</td><td>✓</td><td>✓</td><td>✓</td></tr>
            <tr><td>QR kodovi i naplatne poveznice (TEST)</td><td>✓</td><td>✓</td><td>✓</td></tr>
            <tr><td>WordPress dodatak</td><td>✓</td><td>✓</td><td>✓</td></tr>
            <tr><td>WooCommerce</td><td>✓</td><td>✓</td><td>✓</td></tr>
            <tr><td>Administracija i opoziv licenci</td><td>✓</td><td>✓</td><td>✓</td></tr>
            <tr><td>Stvarna kartična naplata</td><td>U pripremi</td><td>U pripremi</td><td>U pripremi</td></tr>
        </tbody></table></div>
    </section>
    <section class="bp-surface bp-faq"><h2>Često postavljana pitanja</h2>
        <details open><summary>Postoji li besplatan paket?</summary><p>Ne. Start, Business i Pro su plaćeni paketi. Otvaranje računa ne aktivira paket, naplatu ni licence.</p></details>
        <details><summary>Koliko WordPress stranica mogu povezati?</summary><p>Start: 1, Business: 5, Pro: 25. Sustav provjerava limit prilikom izdavanja svake nove licence.</p></details>
        <details><summary>Kako aktiviram WordPress dodatak?</summary><p>U korisničkom panelu otvorite Web-stranice, dodajte domenu i prepišite jednokratni licencni ključ u dodatak. Povezivanje je moguće samo uz aktivan plaćeni paket.</p></details>
        <details><summary>Mogu li promijeniti paket?</summary><p>Administrator može potvrditi nadogradnju. Prije prelaska na paket s manjim limitom potrebno je ukloniti višak domena.</p></details>
        <details><summary>Je li automatska naplata pretplate dostupna?</summary><p>Ne. Trenutačno se paket potvrđuje ručno nakon provjerene uplate. Prikazane cijene još su prijedlog, a stvarne transakcije nisu omogućene.</p></details>
    </section>
</div>
<section class="bp-bottom-cta bp-bottom-cta-pricing" aria-labelledby="bp-pricing-cta-title">
  <div class="bp-cta-copy"><h2 id="bp-pricing-cta-title">Spremni početi primati uplate?</h2><p>Odaberite plaćeni paket, povežite WordPress ili WooCommerce i prvo sigurno isprobajte testne platne integracije.</p></div>
  <div class="bp-cta-actions"><a href="/register?plan=business" class="btn btn-white">Odaberi Business <span aria-hidden="true">→</span></a><a href="/docs" class="btn bp-cta-outline">Pogledaj vodič</a></div>
  <div class="bp-cta-visual" aria-hidden="true"><div class="bp-cta-payment-card"><span class="bp-cta-card-brand">Brendigo <b>Pay</b></span><span class="bp-cta-chip">▦ <i>)))</i></span></div></div>
</section>
<?php return (string) ob_get_clean();
}

/** Render a truthful, server-built 30-day sandbox chart from the merchant's JSON transactions. */
function bp_test_chart(array $transactions): string
{
    $end = strtotime(gmdate('Y-m-d') . ' 00:00:00 UTC');
    $days = [];
    for ($i = 29; $i >= 0; $i--) {
        $days[gmdate('Y-m-d', $end - $i * 86400)] = 0;
    }
    foreach ($transactions as $txn) {
        if (($txn['status'] ?? '') !== 'paid' || !isset($txn['created_at'], $txn['amount_minor'])) {
            continue;
        }
        $timestamp = strtotime((string) ($txn['paid_at'] ?? $txn['created_at']));
        if ($timestamp === false) {
            continue;
        }
        $day = gmdate('Y-m-d', $timestamp);
        if (array_key_exists($day, $days)) {
            $days[$day] += (int) $txn['amount_minor'];
        }
    }
    $maximum = max(100, max($days));
    $points = [];
    $values = array_values($days);
    foreach ($values as $i => $value) {
        $x = 50 + (int) round(660 * $i / 29);
        $y = 216 - (int) round(178 * $value / $maximum);
        $points[] = [$x, $y];
    }
    $line = 'M ' . implode(' L ', array_map(static fn(array $xy): string => $xy[0] . ' ' . $xy[1], $points));
    $area = $line . ' L 710 216 L 50 216 Z';
    $out = '<figure class="bp-real-chart"><svg viewBox="0 0 760 250" role="img" aria-label="Dnevni iznosi potvrđenih testnih uplata tijekom posljednjih 30 dana, u eurima"><defs><linearGradient id="bp-chart-gradient" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#93b5ff" stop-opacity=".58"/><stop offset="100%" stop-color="#d8e9ff" stop-opacity=".08"/></linearGradient></defs>';
    for ($i = 0; $i <= 4; $i++) {
        $y = 216 - (int) round(178 * $i / 4);
        $out .= '<line x1="50" y1="' . $y . '" x2="710" y2="' . $y . '" stroke="#e3eaf9" stroke-width="1"/>';
        $label = number_format($maximum * $i / 400, 0, ',', '.') . ' €';
        $out .= '<text x="42" y="' . ($y + 4) . '" text-anchor="end" font-size="12" fill="#64748b">' . e($label) . '</text>';
    }
    $out .= '<path d="' . e($area) . '" fill="url(#bp-chart-gradient)"/>';
    $out .= '<path d="' . e($line) . '" fill="none" stroke="#2b54ed" stroke-width="3.2" stroke-linejoin="round" stroke-linecap="round"/>';
    $keys = array_keys($days);
    foreach ([0, 5, 10, 15, 20, 25, 29] as $i) {
        $out .= '<text x="' . $points[$i][0] . '" y="244" text-anchor="middle" font-size="12" fill="#64748b">' . e(substr($keys[$i], 5)) . '</text>';
    }
    $out .= '</svg><figcaption>Potvrđene sandbox uplate po danu. Prikazane su isključivo potvrđene testne uplate, a ne stvarni prihod.</figcaption></figure>';
    return $out;
}

/** @param array<string,mixed> $user @param array<int,array<string,mixed>> $links @param array<int,array<string,mixed>> $transactions */
function bp_merchant_ui(array $user, array $links, array $transactions, string $base, string $tab, array $providers = []): string
{
    $tab = in_array($tab, ['overview','links','transactions','integrations'], true) ? $tab : 'overview';
    $paid = array_values(array_filter($transactions, static fn(array $t): bool => ($t['status'] ?? '') === 'paid'));
    $total = array_sum(array_map(static fn(array $t): int => (int) $t['amount_minor'], $paid));
    $active = count(array_filter($links, static fn(array $l): bool => \BrendigoPay\payment_link_is_current($l)));
    $configuredProviderCount = count(array_filter($providers, static fn(mixed $enabled): bool => $enabled === true));
    $sort = static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    usort($links, $sort);
    usort($transactions, $sort);
    ob_start(); ?>
<div class="bp-merchant-heading"><div><span class="bp-kicker">BRENDIGO PAY · TESTNO OKRUŽENJE · <?= e($user['name']) ?></span><h1><?= e(['overview'=>'Pregled','links'=>'Platni linkovi','transactions'=>'Transakcije','integrations'=>'Integracije'][$tab]) ?></h1><p><?= e($tab === 'overview' ? 'Sažetak plaćanja, aktivnosti i povezanih pružatelja.' : 'Pregled i upravljanje podacima vašeg testnog računa.') ?></p></div><div class="bp-heading-actions"><span class="bp-date-chip">▦ Zadnjih 30 dana</span><button class="btn" type="button" data-open-dialog="bp-new-link">↗ Kreiraj link za plaćanje</button></div></div>
<div class="bp-dashboard-metrics"><article class="bp-surface"><span class="bp-metric-icon">▣</span><div><span>Ukupna testna naplata</span><strong><?= minor_format($total) ?></strong><small>Potvrđeni testni zapisi</small></div></article><article class="bp-surface"><span class="bp-metric-icon bp-green">✓</span><div><span>Uspješna plaćanja</span><strong><?= count($paid) ?></strong><small>Potvrđeni sandbox događaji</small></div></article><article class="bp-surface"><span class="bp-metric-icon">↗</span><div><span>Aktivni linkovi</span><strong><?= $active ?></strong><small>Ukupno <?= count($links) ?> poveznica</small></div></article><article class="bp-surface"><span class="bp-metric-icon">◇</span><div><span>Konfigurirani testni pružatelji</span><strong><?= $configuredProviderCount ?></strong><small>Od 3 podržana sandbox pružatelja</small></div></article></div>
<?php if ($tab === 'overview'): ?>
<div class="bp-dashboard-layout"><div class="bp-dashboard-maincol"><section class="bp-surface bp-chart-card"><div class="bp-section-top"><h2>▥ Prihod</h2><span class="bp-date-chip">Testno okruženje</span></div><strong class="bp-chart-total"><?= minor_format($total) ?></strong><p>Ukupno potvrđenih testnih uplata</p><?= bp_test_chart($transactions) ?></section>

<?php endif; ?>
<?php if ($tab !== 'integrations' && $tab !== 'transactions'): ?>
<section class="bp-surface bp-data-card bp-links-card"><div class="bp-section-top"><h2>Platni linkovi</h2><span class="bp-muted">Upravljajte aktivnim i isključenim poveznicama</span></div><div class="table-wrap"><table><thead><tr><th>Naziv</th><th>Iznos</th><th>Status</th><th>Adresa i QR</th><th>Upravljanje</th></tr></thead><tbody>
<?php foreach ($links as $link):
    $enabled = \BrendigoPay\payment_link_is_current($link);
    $expired = !\BrendigoPay\payment_link_is_current(['active' => true, 'expires_at' => $link['expires_at'] ?? null]);
    $url = $base . '/p/' . $link['id'];
?>
<tr><td><b><?= e($link['title']) ?></b></td><td><?= minor_format((int)$link['amount_minor']) ?></td><td><span class="pill <?= $enabled ? 'on' : 'off' ?>"><?= $expired ? 'Istekla' : ($enabled ? '● Aktivna' : 'Isključena') ?></span></td><td><a href="<?= e($url) ?>" target="_blank" rel="noopener">Otvori link ↗</a> · <a href="/dashboard/qr/<?= e($link['id']) ?>">QR kod</a></td><td><?php if ($expired): ?><span class="bp-muted" aria-label="Istekla poveznica">Istekla — kreirajte novu</span><?php else: ?><form method="post" action="/dashboard/links/<?= e($link['id']) ?>/toggle"><?= csrf() ?><button type="submit" class="btn secondary small"><?= $enabled ? 'Isključi' : 'Uključi' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; if ($links === []): ?><tr><td colspan="5">Još nemate platnih linkova. Kreirajte prvi putem gumba iznad.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endif; ?>
<?php if ($tab === 'overview' || $tab === 'transactions'): ?>
<?php if ($tab === 'transactions'): ?><div class="bp-transaction-layout"><?php endif; ?>
<section class="bp-surface bp-data-card bp-transactions-card" id="bp-transaction-list"><div class="bp-section-top"><h2>Transakcije (TEST)</h2><a class="btn secondary small" href="/dashboard/transactions.csv">↓ Izvoz CSV</a></div>
<?php if ($tab === 'transactions'): ?><div class="bp-table-filters" aria-label="Filtri prikazanih transakcija">
<label>Pretraživanje<input type="search" data-txn-filter="query" placeholder="Pretraži opis ili ID..." aria-label="Pretraži prikazane transakcije"></label>
<label>Pružatelj<select data-txn-filter="provider" aria-label="Filtriraj prema pružatelju"><option value="">Svi pružatelji</option><option value="stripe">Stripe</option><option value="paypal">PayPal</option><option value="revolut">Revolut</option></select></label>
<label>Status<select data-txn-filter="status" aria-label="Filtriraj prema statusu"><option value="">Svi statusi</option><option value="paid">Uspješno</option><option value="pending">Na čekanju</option><option value="failed">Neuspješno</option></select></label><button type="button" class="btn secondary small" data-txn-clear>Očisti filtre</button></div>
<p class="bp-muted bp-filter-note" role="status"><span data-txn-visible>0</span> prikazanih redaka (od najviše 40 posljednjih testnih transakcija). CSV izvoz uključuje sve transakcije vašeg računa.</p><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>Datum</th><th>Opis</th><th>Pružatelj</th><th>Iznos</th><th>Status</th></tr></thead><tbody>
<?php foreach (array_slice($transactions,0,40) as $txn): ?><tr data-txn-row data-txn-provider="<?= e((string)($txn['provider'] ?? 'stripe')) ?>" data-txn-status="<?= e((string)($txn['status'] ?? '')) ?>" data-txn-search="<?= e(strtolower((string)(($txn['description'] ?? '') . ' ' . ($txn['id'] ?? '')))) ?>"><td><?= e(substr((string)$txn['created_at'],0,16)) ?></td><td><b><?= e($txn['description']) ?></b></td><td><?= e(ucfirst((string)($txn['provider'] ?? 'stripe'))) ?></td><td><?= minor_format((int)$txn['amount_minor']) ?></td><td><span class="pill <?= ($txn['status'] === 'paid') ? 'on' : 'off' ?>"><?= $txn['status'] === 'paid' ? '✓ Uspješno' : e($txn['status']) ?></span><button type="button" class="bp-icon-button" data-transaction-detail data-txn-id="<?= e((string)($txn['id'] ?? '')) ?>" data-txn-date="<?= e((string)($txn['created_at'] ?? '')) ?>" data-txn-description="<?= e((string)$txn['description']) ?>" data-txn-provider="<?= e((string)($txn['provider'] ?? '')) ?>" data-txn-amount="<?= e(minor_format((int)$txn['amount_minor'])) ?>" data-txn-status="<?= e((string)$txn['status']) ?>" aria-label="Prikaži detalje transakcije">⋯</button></td></tr><?php endforeach; if ($transactions===[]): ?><tr><td colspan="5">Nema testnih transakcija.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php if ($tab === 'transactions'): ?>
<?php $selectedTxn = $transactions[0] ?? null; ?>
<aside class="bp-surface bp-transaction-side" id="bp-transaction-details-side" aria-label="Detalji odabrane testne transakcije" aria-live="polite">
    <div class="bp-section-top"><h2>Detalji transakcije</h2><span class="bp-kicker">TEST</span></div>
    <?php if ($selectedTxn === null): ?>
       <p class="bp-empty-details">Još nema transakcija. Kada se pojave testne uplate, odaberite redak za detalje.</p>
    <?php else: ?>
       <div class="bp-side-status"><span class="bp-round-check"><?= ($selectedTxn['status'] ?? '') === 'paid' ? '✓' : '◷' ?></span><div><strong data-txn-output="id"><?= e((string) ($selectedTxn['id'] ?? '')) ?></strong><small data-txn-output="status"><?= e((string) ($selectedTxn['status'] ?? 'pending')) ?></small></div></div>
       <strong class="bp-side-amount" data-txn-output="amount"><?= minor_format((int) ($selectedTxn['amount_minor'] ?? 0)) ?></strong>
       <dl class="bp-side-details">
         <div><dt>Datum (UTC)</dt><dd data-txn-output="date"><?= e((string) ($selectedTxn['created_at'] ?? '—')) ?></dd></div>
         <div><dt>Opis</dt><dd data-txn-output="description"><?= e((string) ($selectedTxn['description'] ?? '—')) ?></dd></div>
         <div><dt>Pružatelj</dt><dd data-txn-output="provider"><?= e(ucfirst((string) ($selectedTxn['provider'] ?? '—'))) ?></dd></div>
         <div><dt>Referenca</dt><dd><?= e((string) ($selectedTxn['reference'] ?? '—')) ?></dd></div>
       </dl>
       <p class="bp-side-disclaimer">Prikazani su isključivo postojeći podaci vašeg testnog računa. Podaci kupca koji nisu prikupljeni ne prikazuju se niti se izmišljaju.</p>
    <?php endif; ?>
</aside></div>
<?php endif; ?>
<?php endif; ?>
<?php if ($tab === 'overview'): ?></div><aside class="bp-dashboard-side"><section class="bp-surface bp-activity"><div class="bp-section-top"><h2>Aktivnost</h2><a href="/dashboard/audit">Prikaži sve →</a></div><ul><li><span class="bp-round-check">✓</span><div><b><?= count($paid) ?> uspješnih testnih uplata</b><small>Potvrđeni statusi</small></div></li><li><span class="bp-round-check bp-blue">↗</span><div><b><?= count($links) ?> kreiranih linkova</b><small>U vašem računu</small></div></li><li><span class="bp-round-check bp-blue">◇</span><div><b>Integracije</b><small>Konfiguracija u postavkama</small></div></li></ul></section><section class="bp-surface bp-provider-panel"><div class="bp-section-top"><h2>Testni pružatelji</h2><a href="/settings">Postavke →</a></div><div class="bp-provider-list"><?php foreach (['stripe' => 'Stripe', 'paypal' => 'PayPal', 'revolut' => 'Revolut'] as $providerId => $providerName): ?><span><?= e($providerName) ?> <small><?= !empty($providers[$providerId]) ? 'Konfiguriran · Sandbox' : 'Nije povezan' ?></small></span><?php endforeach; ?></div></section></aside></div><?php endif; ?>
<?php if ($tab === 'integrations'): ?><section class="bp-surface"><h2>Povezivanje pružatelja plaćanja</h2><p>Integracije se konfiguriraju i šifrirano čuvaju na platformi. Navedeni servisi rade u testnom načinu.</p><div class="bp-provider-grid"><?php foreach (['stripe' => 'Stripe', 'paypal' => 'PayPal', 'revolut' => 'Revolut'] as $providerId => $providerName): ?><span><?= e($providerName) ?><small class="bp-provider-state"><?= !empty($providers[$providerId]) ? '✓ Konfiguriran · Sandbox' : 'Nije povezan' ?></small></span><?php endforeach; ?></div><p><a href="/settings" class="btn">Otvori postavke integracija →</a></p></section><?php endif; ?>
<dialog id="bp-new-link" class="bp-link-dialog" aria-labelledby="bp-new-link-title"><div class="bp-dialog-heading"><div><?= bp_brand() ?><h2 id="bp-new-link-title">Novi platni link</h2><p>Kreirajte naplatnu poveznicu za proizvod ili uslugu.</p></div><button type="button" class="bp-dialog-close" data-close-dialog aria-label="Zatvori">×</button></div><div class="bp-dialog-inner"><form method="post" id="bp-create-form" action="/dashboard/links" class="bp-link-form"><?= csrf() ?><label>Naziv proizvoda / usluge <span>*</span><input name="title" maxlength="160" required placeholder="Online tečaj – WordPress" data-preview-title></label><label>Opis <small>(neobvezno)</small><textarea name="description" maxlength="500" rows="4" placeholder="Detaljnije opišite uslugu ili proizvod..." data-preview-description></textarea></label><?php
    $merchantSites = array_values(array_filter($GLOBALS['store']->all('sites'), static fn(array $site): bool =>
        ($site['user_id'] ?? '') === $user['id'] && ($site['status'] ?? '') === 'active'));
    ?>
    <label>WordPress web-stranica <span>*</span><select name="site_id" required><option value="">Odaberite povezanu domenu</option><?php foreach ($merchantSites as $site): ?><option value="<?= e($site['id']) ?>"><?= e($site['origin']) ?></option><?php endforeach; ?></select></label>
    <div class="bp-form-two"><label>Iznos (EUR) <span>*</span><input name="amount" required inputmode="decimal" placeholder="99,90" data-preview-amount></label><label>Valuta<input value="EUR (€)" readonly aria-label="Valuta EUR"></label></div>
    <fieldset class="bp-link-providers"><legend>Pružatelj testnog plaćanja</legend><div class="bp-link-provider-options">
      <label class="bp-link-provider-choice"><input type="radio" name="preferred_provider" value="auto" checked><span>Automatski odabir</span></label>
      <?php foreach (['stripe' => 'Stripe', 'paypal' => 'PayPal', 'revolut' => 'Revolut'] as $providerId => $providerName): ?>
      <label class="bp-link-provider-choice"><input type="radio" name="preferred_provider" value="<?= e($providerId) ?>" <?= empty($providers[$providerId]) ? 'disabled' : '' ?>><span><?= e($providerName) ?><?php if (empty($providers[$providerId])): ?><small>Nije povezano</small><?php endif; ?></span></label>
      <?php endforeach; ?>
    </div><small>Prikazuju se samo pružatelji koje ste povezali. Kupac može promijeniti izbor na naplatnoj stranici.</small></fieldset>
    <div class="bp-form-two"><label>Rok valjanosti linka <span>*</span><select name="valid_days" required><option value="7" selected>7 dana</option><option value="14">14 dana</option><option value="30">30 dana</option></select></label><div class="bp-link-policy"><strong>Sigurna testna naplata</strong><small>Istekom roka link više nije dostupan za novu uplatu.</small></div></div>
    <p class="bp-muted">Plaćanje se izvršava kod povezanog testnog pružatelja. Kartični podaci nikad se ne unose u Brendigo Pay.</p><div class="bp-dialog-actions"><button class="btn secondary" type="button" data-close-dialog>Odustani</button><button type="submit" class="btn">Kreiraj link →</button></div></form><aside class="bp-link-preview"><span class="bp-kicker">◉ PREGLED UŽIVO</span><p class="bp-preview-help">Ovako izgleda poveznica prije spremanja.</p><div class="bp-preview-card"><div class="bp-preview-header"><?= bp_brand() ?></div><b data-live-title>Vaš novi platni link</b><p data-live-description>Unesite podatke u obrazac kako biste vidjeli pregled.</p><strong data-live-amount class="bp-preview-amount">0,00 €</strong><span class="bp-fake-button">Testni naplatni link</span><p class="bp-muted">Pregled gumba — nije aktivan prije spremanja.</p></div>
<div class="bp-preview-link-placeholder" aria-label="Buduća adresa poveznice"><span class="bp-preview-link-icon" aria-hidden="true">↗</span><div><strong>Vaš platni link (primjer)</strong><small>Sigurna adresa generira se nakon spremanja.</small></div></div>
<div class="bp-preview-qr-placeholder"><span aria-hidden="true">▦</span><div><strong>QR kod naplate</strong><small>Stvarni kod bit će dostupan nakon spremanja poveznice.</small></div></div></aside></div></dialog>
<dialog id="bp-transaction-dialog" class="bp-transaction-dialog" aria-labelledby="bp-txn-heading"><div class="bp-dialog-heading"><div><span class="bp-kicker">TESTNA TRANSAKCIJA</span><h2 id="bp-txn-heading">Detalji transakcije</h2></div><button type="button" class="bp-dialog-close" data-close-dialog aria-label="Zatvori detalje">×</button></div><dl class="bp-transaction-details"><div><dt>ID transakcije</dt><dd data-txn-output="id"></dd></div><div><dt>Datum</dt><dd data-txn-output="date"></dd></div><div><dt>Opis</dt><dd data-txn-output="description"></dd></div><div><dt>Pružatelj</dt><dd data-txn-output="provider"></dd></div><div><dt>Iznos</dt><dd data-txn-output="amount"></dd></div><div><dt>Status</dt><dd data-txn-output="status"></dd></div></dl><p class="bp-muted">Prikazani su podaci vašeg testnog računa. Povrat uplate nije dostupan u ovom izdanju.</p><div class="bp-dialog-actions"><button type="button" class="btn secondary" data-close-dialog>Zatvori</button></div></dialog>
<?php return (string) ob_get_clean();
}

function bp_public_payment(array $link, array $available, string $base, string $notice, string $merchantName = 'Trgovac'): string
{
    $labels = ['stripe' => 'Stripe TEST', 'paypal' => 'PayPal Sandbox', 'revolut' => 'Revolut Sandbox'];
    $descriptions = ['stripe' => 'Kartično testno plaćanje putem Stripea', 'paypal' => 'Testno plaćanje putem PayPala', 'revolut' => 'Testna uplata putem Revoluta'];
    $supported = array_keys(array_filter($available));
    $preferred = (string) ($link['preferred_provider'] ?? 'auto');
    $selectedProvider = in_array($preferred, $supported, true) ? $preferred : ($supported[0] ?? '');
    ob_start(); ?>
<div class="bp-checkout-heading"><span class="bp-kicker">↗ PLAĆANJE PUTEM PLATNOG LINKA · SANDBOX</span><h1>Dovršite svoju narudžbu</h1><p>Odaberite pružatelja plaćanja. Iznos i naziv određuje trgovac, a plaćanje se dovršava na službenoj testnoj stranici pružatelja.</p><div class="bp-benefits"><span>✓ Bez pohrane kartičnih podataka</span><span>✓ Siguran vanjski checkout</span><span>✓ Testno okruženje</span></div></div>
<div class="bp-checkout-layout bp-ref-checkout">
<section class="bp-checkout-left">
  <article class="bp-surface bp-order-description">
    <div class="bp-merchant-name"><span class="bp-square-icon">▣</span><div><h2><?= e($merchantName) ?></h2><p>Trgovac na Brendigo Pay platformi · SANDBOX</p></div></div>
    <div class="bp-order-product"><div class="bp-order-product-icon" aria-hidden="true">▥</div><div><h3><?= e($link['title']) ?></h3><p><?= e((string) ($link['description'] ?? 'Naplatna poveznica za uslugu ili proizvod.')) ?></p></div><strong><?= minor_format((int) $link['amount_minor']) ?></strong></div>
    <h2>Detalji narudžbe</h2>
    <dl class="bp-order-metadata"><div><dt>Referenca poveznice</dt><dd><?= e(substr($link['id'], 0, 12)) ?></dd></div><div><dt>Vrsta plaćanja</dt><dd>Jednokratna testna uplata</dd></div><div><dt>Valuta</dt><dd>EUR (€)</dd></div></dl>
  </article>
  <div class="bp-checkout-trust bp-surface"><div><span>♢</span><b>Podaci kupca</b><small>Ne spremamo podatke kartica</small></div><div><span>⚡</span><b>Brzo testiranje</b><small>Odabir pružatelja jednim klikom</small></div><div><span>✓</span><b>Server potvrđuje</b><small>Status uplate provjerava sustav</small></div></div>
</section>
<section class="bp-surface bp-checkout-payment">
  <h2><span class="bp-step">1</span> Odaberite pružatelja plaćanja</h2>
  <p>Odaberite konfiguriranog testnog pružatelja. Dostupni su samo servisi koje je trgovac povezao.</p>
  <?= $notice ?>
  <?php if ($supported !== []): ?>
  <form method="post" action="/p/<?= e($link['id']) ?>/checkout" class="bp-provider-checkout-form">
    <?= csrf() ?>
    <fieldset class="bp-checkout-provider-fieldset"><legend class="bp-visually-hidden">Pružatelj plaćanja</legend>
        <?php foreach ($labels as $provider => $label): if (empty($available[$provider])) { continue; } ?>
          <label class="bp-provider-tile"><input type="radio" name="provider" value="<?= e($provider) ?>" <?= $provider === $selectedProvider ? 'checked' : '' ?> required><span><b><?= e($label) ?></b><small><?= e($descriptions[$provider]) ?></small></span></label>
        <?php endforeach; ?>
    </fieldset>
    <div class="bp-choice-step"><span class="bp-step">2</span><div><h2>Nastavite na sigurnu testnu naplatu</h2><p>Podatke o kartici, ako su potrebni, unosite isključivo na službenoj stranici odabranog pružatelja.</p></div></div>
    <div class="bp-hosted-payment"><div class="bp-hosted-payment-header"><span>▣</span><strong>Hosted checkout</strong><span class="bp-hosted-badge">SANDBOX</span></div><p>Brendigo Pay ne prikazuje vlastiti obrazac za broj kartice, datum isteka ni CVC. Nakon preusmjeravanja koristite isključivo testne podatke pružatelja.</p></div>
    <button type="submit" class="btn bp-checkout-submit">🔒 Nastavi na testno plaćanje <span aria-hidden="true">→</span></button>
  </form>
  <?php else: ?>
   <div class="message bp-checkout-not-connected" role="status">Trgovac još nije konfigurirao nijedan testni platni račun. Odabir plaćanja bit će dostupan nakon povezivanja.</div>
   <fieldset class="bp-checkout-provider-fieldset bp-checkout-provider-inactive" disabled><legend class="bp-visually-hidden">Nepovezani pružatelji</legend>
     <label class="bp-provider-tile"><input type="radio" disabled><span><b>Stripe</b><small>Nije povezano</small></span></label>
     <label class="bp-provider-tile"><input type="radio" disabled><span><b>PayPal</b><small>Nije povezano</small></span></label>
     <label class="bp-provider-tile"><input type="radio" disabled><span><b>Revolut</b><small>Nije povezano</small></span></label>
   </fieldset>
   <div class="bp-choice-step"><span class="bp-step">2</span><div><h2>Način plaćanja</h2><p>Sigurni checkout aktivira se tek nakon što trgovac poveže testnog pružatelja.</p></div></div>
   <div class="bp-hosted-payment bp-hosted-disabled"><div class="bp-hosted-payment-header"><span>♢</span><strong>Sigurna naplata kod pružatelja</strong><span class="bp-hosted-badge">NEDOSTUPNO</span></div><p>Brendigo Pay ne prikuplja brojeve kartica ni CVC. Naplatni obrazac otvara se na službenoj stranici platnog pružatelja.</p></div>
   <button type="button" class="btn bp-checkout-submit" disabled aria-disabled="true">Naplata trenutačno nije dostupna</button>
  <?php endif; ?>
  <div class="bp-payment-total"><span>Ukupno za plaćanje</span><b><?= minor_format((int) $link['amount_minor']) ?></b></div>
  <div class="bp-checkout-security">✓ Sigurno preusmjeravanje na platnog pružatelja · poslužiteljska provjera statusa.</div>
  <div class="bp-checkout-note">SANDBOX · Nema stvarnog terećenja. Povratak s platne stranice sam po sebi nije dokaz plaćanja; server provjerava status pružatelja.</div>
</section></div>
<?php return (string) ob_get_clean();
}

function bp_site_plugin(): string
{
    ob_start(); ?>
<section class="bp-pricing-intro"><span class="bp-kicker">◇ BRENDIGO PAY DODATAK</span><h1>Jedan dodatak za <span>WordPress i WooCommerce</span></h1><p>Povežite svoju web-stranicu s postojećim Brendigo Pay testnim računom. Podržava Gutenberg blokove i WooCommerce checkout.</p><div class="bp-actions"><a class="btn" href="/register">Otvori testni račun →</a><a class="btn secondary" href="/docs">Pogledaj upute</a></div></section>
<div class="bp-home-grid"><article class="bp-surface"><span class="bp-feature-icon">ⓦ</span><h2>Za WordPress</h2><p>Umetnite platne gumbe, poveznice, kartice i lokalno generirane QR kodove. WooCommerce nije potreban.</p><div class="bp-integration-chips"><span>Gutenberg</span><span>Shortcode</span></div></article><article class="bp-surface"><span class="bp-feature-icon">▣</span><h2>Za WooCommerce</h2><p>Kupac bira Stripe TEST, PayPal Sandbox ili Revolut Sandbox. Podržani su klasični checkout i Blocks integracija.</p><p>Provjera statusa narudžbe obavlja se preko Brendigo Pay API-ja.</p></article><article class="bp-surface"><h2>Instalacija</h2><p>Instalacijski ZIP nalazi se u razvojnom paketu projekta. U WordPressu otvorite Dodaci → Dodaj novi → Prenesi dodatak.</p><p>Potom unesite HTTPS URL platforme i domenski licencni ključ dobiven iz svojeg korisničkog panela.</p><p><a href="/docs/wordpress">Detaljne upute za WordPress instalaciju →</a></p></article><article class="bp-surface"><h2>Sigurnost</h2><p>Administratorski API ključ je šifriran, a iznosi se čitaju s platforme. U dodatku se ne čuvaju brojevi kartica.</p><span class="bp-kicker">SANDBOX — BEZ STVARNOG TEREĆENJA</span></article></div>
<?php return (string) ob_get_clean();
}


