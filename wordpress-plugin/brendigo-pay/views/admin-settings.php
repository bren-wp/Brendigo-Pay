<?php
/** @var bool $tokenSet @var string $status */
if (!defined('ABSPATH')) { exit; }
$url = BP_Pay_Settings::api_base();
$base = $url !== '' ? $url : '';
$linkResponse = ($tokenSet && $url) ? BP_Pay_API::request('GET', '/api/v1/links') : null;
$linkItems = is_array($linkResponse) ? ($linkResponse['items'] ?? []) : [];
if (!is_array($linkItems)) { $linkItems = []; }
?>
<div class="wrap bp-admin-wrap">
    <header class="bp-admin-hero">
        <div class="bp-admin-brand"><span class="bp-admin-mark" aria-hidden="true"><img src="<?php echo esc_url(plugins_url('assets/brand-mark.svg', BP_PAY_FILE)); ?>" alt="" width="62" height="62"></span><div><strong>Brendigo <em>Pay</em></strong><small>PAMETNA PLAĆANJA ZA MODERAN BIZNIS</small></div></div>
        <div class="bp-admin-hero-art" aria-hidden="true"><div class="bp-admin-card"><b>Brendigo Pay</b><span>▣　)))</span></div></div>
    </header>
    <h1 class="bp-admin-title"><?php esc_html_e('Postavke Brendigo Pay', 'brendigo-pay'); ?></h1>
    <p class="bp-admin-description"><?php esc_html_e('Povežite testni račun, pregledajte pružatelje plaćanja i upravljajte naplatom na svojoj WordPress stranici.', 'brendigo-pay'); ?></p>
    <nav class="bp-admin-tabs" aria-label="Brendigo Pay sekcije">
        <a class="bp-admin-tab bp-admin-tab-active" href="#bp-general" aria-current="location">⚙ Opće postavke</a>
        <a class="bp-admin-tab" href="#bp-providers">◇ Pružatelji plaćanja</a>
        <a class="bp-admin-tab" href="#bp-links">↗ Platni linkovi</a>
        <a class="bp-admin-tab" href="#bp-qr">▦ QR kodovi</a>
        <a class="bp-admin-tab" href="#bp-security">♢ Sigurnost</a>
        <a class="bp-admin-tab" href="#bp-docs">▤ Dokumentacija</a>
    </nav>
    <?php settings_errors('brendigo_pay_settings'); ?>
    <?php $licenseStatus = isset($_GET['bp_license_status']) ? sanitize_key(wp_unslash($_GET['bp_license_status'])) : ''; ?>
    <?php if ($licenseStatus === 'success'): ?><div class="notice notice-success"><p>Licencija je potvrđena. WordPress je povezan s aktivnim plaćenim paketom na platformi (TEST).</p></div><?php endif; ?>
    <?php if ($licenseStatus === 'failure'): ?><div class="notice notice-error"><p>Aktivacija nije uspjela. Provjerite URL platforme, licencni ključ, domenu i paket.</p></div><?php endif; ?>
    <?php if ($status === 'success'): ?><div class="notice notice-success"><p>Veza je uspješno provjerena za ovu WordPress domenu i testni račun.</p></div><?php endif; ?>
    <?php if ($status === 'failure'): ?><div class="notice notice-error"><p>Veza nije potvrđena. Provjerite HTTPS adresu, testni API ključ i dopuštenu domenu.</p></div><?php endif; ?>
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
    <section id="bp-providers" class="bp-admin-section"><h2>◇ Povezivanje pružatelja plaćanja</h2><p>Konfiguracija Stripe, PayPal i Revolut Sandbox računa nalazi se na web-platformi Brendigo Pay.</p><div class="bp-admin-provider-grid">
        <?php foreach (['stripe' => ['Stripe TEST','S'], 'paypal' => ['PayPal Sandbox','P'], 'revolut' => ['Revolut Sandbox','R']] as $provider): ?>
        <article class="bp-admin-panel bp-admin-provider"><span class="bp-provider-logo"><?php echo esc_html($provider[1]); ?></span><h3><?php echo esc_html($provider[0]); ?></h3><span class="bp-admin-warn">Status provjerite na platformi</span><p>Provjerite konfiguraciju testnog računa prije prihvata uplata.</p><?php if ($url !== ''): ?><a href="<?php echo esc_url($url . '/settings'); ?>" class="button bp-admin-outline" target="_blank" rel="noopener noreferrer">Upravljaj integracijom ↗</a><?php else: ?><span class="bp-admin-warn">Najprije spremite URL platforme.</span><?php endif; ?></article>
        <?php endforeach; ?>
    </div></section>
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
<section id="bp-links" class="bp-admin-feature-grid"><article class="bp-admin-panel"><span class="bp-admin-feature-icon">↗</span><h2>Platni linkovi</h2><p>Kreirajte poveznice na platformi i umetnite ih u WordPress pomoću bloka ili shortcoda.</p><code>[brendigo_pay id="ID_LINKA"]</code><p><?php if ($url !== ''): ?><a class="button bp-admin-outline" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url($url . '/dashboard?tab=links'); ?>">Upravljaj linkovima ↗</a><?php endif; ?></p></article><article class="bp-admin-panel" id="bp-qr"><span class="bp-admin-feature-icon">▦</span><h2>QR kodovi</h2><p>Poveznicu možete prikazati kao QR kod unutar Brendigo Pay bloka.</p><p>Koristite isti ID naplatne poveznice.</p></article><article class="bp-admin-panel" id="bp-security"><span class="bp-admin-feature-icon">♢</span><h2>Sigurnost</h2><p>API tajne šifriraju se uz WordPress salts. Obvezni su HTTPS i sigurni pristupni računi.</p><ul><li>CSRF / nonce provjere</li><li>Server-side provjera statusa</li><li>Bez lokalne pohrane kartica</li></ul></article></section>
    <section class="bp-admin-panel" id="bp-docs"><h2>Dokumentacija i upute</h2><p>WooCommerce: nakon povezivanja otvorite <strong>WooCommerce → Postavke → Plaćanja</strong> i uključite Brendigo Pay Sandbox.</p><p>WordPress: u Gutenberg editoru odaberite blok <strong>Brendigo Pay – Naplata</strong>. Dodatak funkcionira i bez WooCommercea.</p></section>
    <div class="bp-admin-bottom-note"><a href="https://brendigopay.com" target="_blank" rel="noopener noreferrer">BrendigoPay.com</a> · <a href="https://brendigo.com" target="_blank" rel="noopener noreferrer">Autor Brendigo</a> · Brendigo Pay v<?php echo esc_html(BP_PAY_VERSION); ?> · SANDBOX · Bez stvarne naplate</div>
</div>
