<?php
/** @var bool $tokenSet @var string $status */
if (!defined('ABSPATH')) { exit; }
$url = BP_Pay_Settings::api_base();
$base = $url !== '' ? $url : '';
$linkResponse = ($tokenSet && $url && in_array($screenTab, ['links', 'qr'], true))
    ? BP_Pay_API::request('GET', '/api/v1/links') : null;
$linkItems = is_array($linkResponse) ? ($linkResponse['items'] ?? []) : [];
if (!is_array($linkItems)) { $linkItems = []; }
?>
<div class="wrap bp-admin-wrap">
    <header class="bp-admin-hero">
        <div class="bp-admin-brand"><span class="bp-admin-mark" aria-hidden="true"><img src="<?php echo esc_url(plugins_url('assets/brand-mark.svg', BP_PAY_FILE)); ?>" alt="" width="62" height="62"></span><div><strong>Brendigo <em>Pay</em></strong><small>PAMETNA PLAĆANJA ZA MODERAN BIZNIS</small></div></div>
        <div class="bp-admin-hero-art" aria-hidden="true"><div class="bp-admin-card"><b>Brendigo Pay</b><span>▣　)))</span></div></div>
    </header>
    <h1 class="bp-admin-title"><?php echo esc_html($screenTitle); ?></h1>
    <p class="bp-admin-description">Brendigo Pay · <?php echo esc_html($screenTitle); ?> · SANDBOX</p>
    <?php settings_errors('brendigo_pay_settings'); ?>
    <?php $licenseStatus = isset($_GET['bp_license_status']) ? sanitize_key(wp_unslash($_GET['bp_license_status'])) : ''; ?>
    <?php if ($licenseStatus === 'success'): ?><div class="notice notice-success"><p>Licencija je potvrđena. WordPress je povezan s aktivnim plaćenim paketom na platformi (TEST).</p></div><?php endif; ?>
    <?php if ($licenseStatus === 'partial'): ?><div class="notice notice-warning"><p>Licencija je prihvaćena, ali sinkronizacija nije potvrđena. Provjerite pristup platformi pa odaberite Sinkroniziraj sada.</p></div><?php endif; ?>
    <?php if ($licenseStatus === 'failure'): ?><div class="notice notice-error"><p>Aktivacija nije uspjela. Provjerite URL platforme, licencni ključ, domenu i paket.</p></div><?php endif; ?>
    <?php if ($status === 'success'): ?><div class="notice notice-success"><p>Veza je uspješno provjerena za ovu WordPress domenu i testni račun.</p></div><?php endif; ?>
    <?php if ($status === 'failure'): ?><div class="notice notice-error"><p>Veza nije potvrđena. Provjerite HTTPS adresu, testni API ključ i dopuštenu domenu.</p></div><?php endif; ?>
    <?php $sync = BP_Pay_Settings::sync_status(); $syncActive = BP_Pay_Settings::license_is_current(); ?>
    <?php if ($screenTab === 'general'): ?>
    <section class="bp-admin-panel bp-admin-sync-panel" aria-labelledby="bp-admin-sync-heading">
        <div class="bp-admin-sync-summary">
            <div><h2 id="bp-admin-sync-heading">Sinkronizacija WordPress instalacije</h2>
            <p>Točna domena, licencija i pretplata provjeravaju se na Brendigo Pay platformi. Stanje nije dokaz uplate.</p></div>
            <span class="bp-admin-sync-badge <?php echo $syncActive ? 'bp-admin-sync-ok' : 'bp-admin-sync-off'; ?>">
            <?php echo $syncActive ? '✓ Povezano' : 'Nije potvrđeno'; ?></span>
        </div>
        <dl class="bp-admin-sync-details">
            <div><dt>Domena</dt><dd><?php echo esc_html(BP_Pay_Settings::site_origin()); ?></dd></div>
            <div><dt>Paket</dt><dd><?php echo $syncActive ? esc_html(ucfirst((string) ($sync['plan'] ?? ''))) : 'Nije potvrđeno'; ?></dd></div>
            <div><dt>Web-stranice</dt><dd><?php echo $syncActive ? esc_html((string) ($sync['websites_used'] ?? 0) . ' / ' . (string) ($sync['website_limit'] ?? 0)) : '—'; ?></dd></div>
            <div><dt>Vrijedi do</dt><dd><?php echo $syncActive ? esc_html((string) ($sync['expires_at'] ?? '—')) : '—'; ?></dd></div>
            <div><dt>Zadnja provjera</dt><dd><?php echo !empty($sync['checked_at']) ? esc_html(gmdate('d.m.Y. H:i', (int) $sync['checked_at']) . ' UTC') : 'Nije provjereno'; ?></dd></div>
        </dl>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="brendigo_pay_sync">
            <?php wp_nonce_field('brendigo_pay_sync'); ?>
            <button type="submit" class="button bp-admin-outline">↻ Sinkroniziraj sada</button>
        </form>
    </section>
    <section class="bp-admin-grid bp-admin-status-grid" id="bp-general">
        <article class="bp-admin-panel bp-admin-connection <?php echo $tokenSet && $url ? 'bp-admin-configured' : ''; ?>">
            <span class="bp-admin-round-check"><?php echo $tokenSet && $url ? '✓' : '!'; ?></span>
            <div><h2><?php echo $tokenSet && $url ? 'Konfigurirano — SANDBOX' : 'Potrebno povezivanje'; ?></h2><p><?php echo $tokenSet && $url ? 'API ključ i URL spremljeni su. Za potvrdu veze pokrenite test.' : 'Spremite URL platforme, a zatim aktivirajte licenciju u obrascu niže.'; ?></p></div>
            <span class="bp-admin-status-pill">TESTNO OKRUŽENJE</span>
        </article>
        <article class="bp-admin-panel"><h2>Okruženje</h2><p>Produkcijska naplata nije omogućena u ovom izdanju.</p><div class="bp-admin-environments"><span class="bp-admin-environment-selected">◉ Sandbox (testno)</span><span class="bp-admin-environment-disabled">◯ Produkcija (nije dostupna)</span></div></article>
    </section>
    <section class="bp-admin-grid bp-admin-keys-grid">
        <article class="bp-admin-panel"><h2>Pristup web-platformi</h2><p>Prvo spremite HTTPS URL Brendigo Pay platforme. Zatim aktivirajte ključ licencije vezan uz domenu.</p>
            <form id="bp-admin-settings-form" method="post" action="options.php">
                <?php settings_fields('brendigo_pay_group'); ?>
                <label for="bp-url">URL platforme <span class="bp-required">*</span></label>
                <input class="bp-admin-input" type="url" id="bp-url" name="brendigo_pay_settings[url]" value="<?php echo esc_attr($url); ?>" placeholder="https://pay.tvoja-domena.hr" required>
                <p class="description">API token se automatski izdaje nakon uspješne aktivacije licencije. Ručni unos tokena nije potreban.</p>
                <button type="submit" class="button button-primary bp-admin-button">▣ Spremi postavke</button>
            </form>
            <form class="bp-test-connection" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brendigo_pay_test_connection">
                <?php wp_nonce_field('brendigo_pay_test_connection'); ?>
                <button type="submit" class="button bp-admin-outline">▷ Testiraj povezivanje</button>
            </form>
        </article>
        <article class="bp-admin-panel bp-admin-license-panel"><h2>Aktivacija licencije za WordPress</h2>
            <p>1. Prijavite se u korisnički panel Brendigo Pay. 2. Otvorite <strong>Web-stranice</strong> i registrirajte ovu domenu. 3. Ovdje unesite izdani ključ.</p>
            <p><strong>Adresa ove stranice:</strong> <code><?php echo esc_html(home_url('/')); ?></code></p>
            <?php if ($base !== ''): ?><p><a href="<?php echo esc_url($base . '/sites'); ?>" target="_blank" rel="noopener noreferrer">Moje licence i web-stranice ↗</a></p><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="brendigo_pay_activate_license">
                <?php wp_nonce_field('brendigo_pay_activate_license'); ?>
                <label for="bp-license">Licencni ključ <span class="bp-required">*</span></label>
                <input class="bp-admin-input" type="password" name="bp_license" id="bp-license" required autocomplete="new-password" maxlength="71" pattern="bp_lic_[a-f0-9]{64}" placeholder="bp_lic_...">
                <button class="button button-primary bp-admin-button" type="submit">Aktiviraj licenciju i poveži</button>
            </form><p class="description">Licencija se ne sprema u WordPress. API token je lokalno šifriran, a aktivacija je trenutačno samo za sandbox.</p>
        </article>
        <article class="bp-admin-panel"><h2>Webhook i API</h2><p>Statusi narudžbi provjeravaju se putem zaštićenog Brendigo Pay API-ja.</p><label for="bp-display-webhook">Adresa platforme</label><div class="bp-admin-copy-row"><input readonly class="bp-admin-input" id="bp-display-webhook" value="<?php echo esc_attr($base); ?>"><button type="button" class="button bp-copy-field" data-copy-field="bp-display-webhook">Kopiraj</button></div><p class="description">To nije WordPress webhook endpoint. Provjeru transakcije obavlja dodatak prema platformi.</p><div class="bp-admin-tip">♢ Kartični podaci obrađuju se na stranicama platnog pružatelja, ne unutar ovog dodatka.</div></article>
    </section>
    <?php endif; ?>
    <?php if ($screenTab === 'providers'): ?>
    <section id="bp-providers" class="bp-admin-section"><h2>◇ Povezivanje pružatelja plaćanja</h2><p>Konfiguracija Stripe, PayPal i Revolut Sandbox računa nalazi se na web-platformi Brendigo Pay.</p><div class="bp-admin-provider-grid">
        <?php foreach (['stripe' => ['Stripe TEST','S'], 'paypal' => ['PayPal Sandbox','P'], 'revolut' => ['Revolut Sandbox','R']] as $provider): ?>
        <article class="bp-admin-panel bp-admin-provider"><span class="bp-provider-logo"><?php echo esc_html($provider[1]); ?></span><h3><?php echo esc_html($provider[0]); ?></h3><span class="bp-admin-warn">Status provjerite na platformi</span><p>Provjerite konfiguraciju testnog računa prije prihvata uplata.</p><?php if ($url !== ''): ?><a href="<?php echo esc_url($url . '/settings'); ?>" class="button bp-admin-outline" target="_blank" rel="noopener noreferrer">Upravljaj integracijom ↗</a><?php else: ?><span class="bp-admin-warn">Najprije spremite URL platforme.</span><?php endif; ?></article>
        <?php endforeach; ?>
    </div></section>
    <?php if (class_exists('WooCommerce')): ?>
    <section class="bp-admin-panel bp-admin-offline-methods" aria-labelledby="bp-admin-offline-heading">
        <h2 id="bp-admin-offline-heading">Dodatne opcije u WooCommerceu</h2>
        <p>Za hrvatske i SEPA bankovne uplate WooCommerce ima vlastiti način <strong>Bankovna uplata (BACS)</strong>,
           a za naplatu prilikom dostave vlastitu opciju <strong>Pouzećem (COD)</strong>.
           Te opcije nisu Brendigo Pay kartični adapteri i ne prijavljuju automatski uspješnu uplatu platformi.</p>
        <p><strong>Sigurnosna napomena:</strong> narudžbe plaćene bankovnom uplatom ostaju na čekanju
           dok trgovac ne potvrdi stvarni priljev na svojem računu.</p>
        <a class="button bp-admin-outline"
           href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>">
           Otvori WooCommerce načine plaćanja
        </a>
        <p><a href="https://woocommerce.com/document/bacs/" target="_blank" rel="noopener noreferrer">
           Službena WooCommerce dokumentacija za bankovne uplate</a></p>
    </section>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($screenTab === 'links'): ?>
    <section id="bp-merchant-links" class="bp-admin-panel bp-admin-link-list" aria-labelledby="bp-merchant-links-title">
    <div class="bp-admin-link-header"><div><h2 id="bp-merchant-links-title">Aktivne naplatne poveznice</h2><p>Odaberite poveznicu na Gutenberg bloku ili kopirajte kratki kod na bilo koju WordPress stranicu.</p></div>
    <?php if ($url !== ''): ?><a class="button bp-admin-button" href="<?php echo esc_url($url . '/dashboard?tab=links'); ?>" target="_blank" rel="noopener noreferrer">Upravljaj poveznicama ↗</a><?php endif; ?></div>
    <?php if (is_wp_error($linkResponse)): ?>
        <div class="notice notice-warning inline"><p>Nije moguće dohvatiti popis. Provjerite API ključ, dopuštenu domenu i dostupnost platforme.</p></div>
    <?php elseif ($linkItems === []): ?>
        <p class="bp-admin-empty-state">Još nema aktivnih naplatnih poveznica na povezanom testnom računu.</p>
    <?php else: ?>
        <div class="bp-admin-table-scroller"><table class="widefat striped bp-admin-links-table"><thead><tr><th>Naziv</th><th>Iznos</th><th>Kratki kod</th><th>Naplatna stranica</th></tr></thead><tbody>
        <?php foreach (array_slice($linkItems, 0, 30) as $link): if (!is_array($link) || !preg_match('/^[a-f0-9]{24}$/D', (string) ($link['id'] ?? ''))) { continue; } ?>
        <?php $shortcode = '[brendigo_pay id="' . (string) $link['id'] . '" layout="card" show_qr="yes"]'; ?>
        <tr><td><strong><?php echo esc_html((string) ($link['title'] ?? '')); ?></strong></td><td><?php echo esc_html(number_format((int) ($link['amount_minor'] ?? 0) / 100, 2, ',', '.') . ' €'); ?></td>
        <td><div class="bp-admin-shortcode"><code><?php echo esc_html($shortcode); ?></code><button type="button" class="button bp-copy-text" data-copy-text="<?php echo esc_attr($shortcode); ?>" aria-label="Kopiraj kratki kod">Kopiraj</button></div></td>
        <td><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($url . '/p/' . (string) $link['id']); ?>">Otvori ↗</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</section>
<section id="bp-links" class="bp-admin-panel bp-admin-section-view">
    <h2>Platni linkovi i kratki kodovi</h2>
    <p>Naplatne linkove kreirajte na web-platformi. Na WordPress stranice možete dodati njihove Gutenberg blokove ili kratke kodove.</p>
    <?php if ($url !== ''): ?><p><a class="button bp-admin-button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($url . '/dashboard?tab=links'); ?>">Upravljaj platnim linkovima ↗</a></p><?php endif; ?>
</section>
<?php endif; ?>
<?php if ($screenTab === 'qr'): ?>
<section id="bp-qr" class="bp-admin-panel bp-admin-section-view">
    <h2>QR kodovi za naplatu</h2>
    <p>Svaka aktivna naplatna poveznica može se prikazati kao QR kod pomoću Brendigo Pay Gutenberg bloka ili kratkog koda. QR usmjerava na stvarnu HTTPS naplatnu stranicu.</p>
    <?php if (is_wp_error($linkResponse)): ?>
        <div class="notice notice-warning inline"><p>Naplatne poveznice trenutačno nisu dostupne. Provjerite povezivanje s web-platformom.</p></div>
    <?php elseif ($linkItems === []): ?>
        <p class="bp-admin-empty-state">Nema dostupnih poveznica. Kreirajte ih u korisničkom panelu pa otvorite ovaj odjeljak.</p>
    <?php else: ?>
        <div class="bp-admin-table-scroller"><table class="widefat striped bp-admin-links-table">
            <thead><tr><th>Naziv</th><th>QR kratki kod</th><th>Naplatna stranica</th></tr></thead><tbody>
            <?php foreach (array_slice($linkItems, 0, 30) as $link): if (!is_array($link) || !preg_match('/^[a-f0-9]{24}$/D', (string) ($link['id'] ?? ''))) { continue; } ?>
            <?php $qrCode = '[brendigo_pay id="' . (string) $link['id'] . '" layout="card" show_qr="yes"]'; ?>
            <tr><td><?php echo esc_html((string) ($link['title'] ?? '')); ?></td>
                <td><div class="bp-admin-shortcode"><code><?php echo esc_html($qrCode); ?></code><button type="button" class="button bp-copy-text" data-copy-text="<?php echo esc_attr($qrCode); ?>">Kopiraj</button></div></td>
                <td><a href="<?php echo esc_url($url . '/p/' . (string) $link['id']); ?>" target="_blank" rel="noopener noreferrer">Otvori ↗</a></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($screenTab === 'security'): ?>
<section id="bp-security" class="bp-admin-panel bp-admin-section-view">
    <h2>Sigurnost i povezivanje</h2>
    <p>Brendigo Pay koristi autentikaciju poslužitelja, nonce provjere i provjeru domenskih licenci. Kartični podaci obrađuju se na stranicama platnih pružatelja.</p>
    <dl class="bp-admin-sync-details">
        <div><dt>Licencija</dt><dd><?php echo $syncActive ? '✓ Potvrđena' : 'Nije potvrđena'; ?></dd></div>
        <div><dt>Okruženje</dt><dd>Sandbox (testno)</dd></div>
        <div><dt>Pristupni token</dt><dd><?php echo $tokenSet ? 'Pohranjen u šifriranom obliku' : 'Nije konfiguriran'; ?></dd></div>
        <div><dt>Produkcijska naplata</dt><dd>Nije dostupna</dd></div>
    </dl>
    <p><strong>Sigurnosne kontrole:</strong> odvojene korisničke ovlasti, validacija HTTPS domene, nonce zaštita administratorskih radnji, provjera server-side statusa i bez lokalne pohrane kartičnih podataka.</p>
    <p><?php if ($base !== ''): ?><a class="button bp-admin-outline" href="<?php echo esc_url($base . '/security'); ?>" target="_blank" rel="noopener noreferrer">Sigurnosna dokumentacija ↗</a><?php endif; ?></p>
</section>
<?php endif; ?>
<?php if ($screenTab === 'logs'): ?>
<section id="bp-logs" class="bp-admin-panel bp-admin-section-view">
    <h2>Dnevnici sinkronizacije</h2>
    <p>Prikazuju se samo vrijeme i rezultat posljednjih 20 provjera ove WordPress instalacije. Tajni ključevi, osobni podaci i podaci kartica ne zapisuju se u ovaj dnevnik.</p>
    <?php $syncEvents = BP_Pay_Settings::sync_history(); ?>
    <?php if ($syncEvents === []): ?>
        <p class="bp-admin-empty-state">Još nema zapisanih događaja sinkronizacije.</p>
    <?php else: ?>
        <div class="bp-admin-table-scroller"><table class="widefat striped">
            <thead><tr><th>Vrijeme (UTC)</th><th>Rezultat</th></tr></thead><tbody>
            <?php foreach ($syncEvents as $event): if (!is_array($event)) { continue; } ?>
            <?php $eventStatus = (string) ($event['status'] ?? ''); ?>
            <tr><td><?php echo esc_html(gmdate('d.m.Y. H:i:s', max(0, (int) ($event['timestamp'] ?? 0)))); ?></td>
                <td><?php echo esc_html(['active' => 'Uspješno', 'unavailable' => 'Veza nije potvrđena', 'not_configured' => 'Nije konfigurirano'][$eventStatus] ?? 'Nepoznato'); ?></td></tr>
            <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($screenTab === 'docs'): ?>
    <section class="bp-admin-panel" id="bp-docs"><h2>Dokumentacija i upute</h2><p>Korak po korak: povežite račun, instalirajte dodatak, aktivirajte licencu, provjerite pružatelje i testirajte naplatu.</p>
    <?php $guideBase = $base !== '' ? $base : 'https://brendigopay.com'; ?>
    <div class="bp-admin-guide-links">
        <a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($guideBase . '/docs/quickstart'); ?>">Brzi početak</a>
        <a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($guideBase . '/docs/wordpress'); ?>">WordPress</a>
        <a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($guideBase . '/docs/woocommerce'); ?>">WooCommerce</a>
        <a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($guideBase . '/docs/qr'); ?>">QR kodovi</a>
        <a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($guideBase . '/docs/troubleshooting'); ?>">Rješavanje poteškoća</a>
    </div><p>WooCommerce: nakon povezivanja otvorite <strong>WooCommerce → Postavke → Plaćanja</strong> i uključite Brendigo Pay Sandbox.</p><p>WordPress: u Gutenberg editoru odaberite blok <strong>Brendigo Pay – Naplata</strong>. Dodatak funkcionira i bez WooCommercea.</p></section>
    <?php endif; ?>
    <div class="bp-admin-bottom-note"><a href="https://brendigopay.com" target="_blank" rel="noopener noreferrer">BrendigoPay.com</a> · <a href="https://brendigo.com" target="_blank" rel="noopener noreferrer">Autor Brendigo</a> · Brendigo Pay v<?php echo esc_html(BP_PAY_VERSION); ?> · SANDBOX · Bez stvarne naplate</div>
</div>
