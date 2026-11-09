#!/usr/bin/env python3
"""Local HTTP smoke tests. Uses an isolated temporary copy and never calls Stripe."""
import contextlib
from datetime import datetime, timedelta, timezone
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

SOURCE = Path(__file__).resolve().parents[1]


def find_port():
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        return sock.getsockname()[1]


def main():
    with tempfile.TemporaryDirectory(prefix='bp-v08-smoke-') as td:
        project = Path(td) / 'platform'
        shutil.copytree(SOURCE, project, ignore=shutil.ignore_patterns('config.php', '*.json', '*.lock'))
        port = find_port()
        base = f'http://127.0.0.1:{port}'
        subprocess.run(['php', 'bin/setup.php', base], cwd=project, check=True, capture_output=True, text=True)
        server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=project,
                                  stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        jar = http.cookiejar.CookieJar()
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

        def req(path, data=None, method=None, token=None, client=None):
            headers = {}
            if token:
                headers['Authorization'] = 'Bearer ' + token
            if isinstance(data, dict):
                if token or path == '/api/v1/license/activate' or path.startswith('/api/v1/mobile/'):
                    headers['Content-Type'] = 'application/json'
                    data = json.dumps(data).encode()
                else:
                    headers['Content-Type'] = 'application/x-www-form-urlencoded'
                    data = urllib.parse.urlencode(data).encode()
            request = urllib.request.Request(base + path, data=data, method=method, headers=headers)
            try:
                with (client or opener).open(request, timeout=6) as response:
                    return response.status, response.read().decode(), dict(response.headers)
            except urllib.error.HTTPError as error:
                return error.code, error.read().decode(), dict(error.headers)

        try:
            for attempt in range(30):
                try:
                    if req('/api/v1/health')[0] == 200:
                        break
                except urllib.error.URLError:
                    time.sleep(0.1)
            else:
                raise RuntimeError('Server did not start')

            for legal_path, expected in [
                ('/privacy', 'Politika privatnosti'),
                ('/terms', 'Uvjeti korištenja'),
                ('/cookies', 'Kolačići'),
                ('/refund-policy', 'Otkazivanje i povrati'),
                ('/contact', 'Kontakt i podrška'),
                ('/about', 'O platformi'),
                ('/security', 'Sigurnost platforme'),
                ('/providers', 'Načini plaćanja'),
            ]:
                legal_status, legal_html, _ = req(legal_path)
                assert legal_status == 200 and expected in legal_html
                assert 'https://brendigo.com' in legal_html
                assert 'Built with' in legal_html and 'nije banka' in legal_html
            assert req('/no-such-legal-page')[0] == 404
            print('PASS: all public legal pages, responsive brand footer and explicit payment disclaimer')
            guide_routes = {
                '/features': ['payment-links', 'qr-codes', 'woocommerce', 'wordpress',
                              'analytics', 'android', 'licensing'],
                '/integrations': ['stripe', 'paypal', 'revolut', 'wordpress',
                                  'woocommerce', 'bank-transfer', 'monri', 'corvuspay'],
                '/docs': ['quickstart', 'account', 'plans', 'sites', 'wordpress',
                          'woocommerce', 'payment-links', 'qr', 'android', 'providers',
                          'stripe', 'paypal', 'revolut', 'transactions', 'webhooks',
                          'security', 'troubleshooting', 'faq', 'release-checklist'],
            }
            guide_count = 0
            for root, subpages in guide_routes.items():
                for page in [root, *(root + '/' + slug for slug in subpages)]:
                    route_status, guide_html, _ = req(page)
                    assert route_status == 200, (page, route_status)
                    assert 'bp-guide-layout' in guide_html and '<h1>' in guide_html, page
                    assert 'Built with' in guide_html and 'brendigo.com' in guide_html
                    assert 'storage/data' not in guide_html and 'JSON pohrana' not in guide_html
                    assert 'Politika privatnosti' in guide_html
                    guide_count += 1
            assert guide_count == 37, guide_count
            assert req('/docs/nepostojeca')[0] == 404
            assert req('/integrations/not-supported')[0] == 404
            assert (project / 'public/assets/screenshots/user-dashboard-desktop.png').is_file()
            print(f'PASS: {guide_count} customer-facing guide URLs and no internal storage disclosures')
            status, body, _ = req('/register?plan=business')
            assert status == 200 and '49,90' in body and '19,90' in body, f"register GET: {status}, body={body[:420]!r}"
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            status, body, _ = req('/register', {
                'name': 'Smoke Merchant', 'email': 'smoke@example.com',
                'requested_tier': 'business', 'website': base,
                'password': 'this-is-a-long-test-password', 'csrf': csrf,
            })
            assert status == 200 and 'Moje web-stranice' in body and 'čeka provjeru uplate' in body
            print('PASS: new merchant selects paid tier but receives no free license')
            # Android registration and web login must use the identical user/email/Argon2id store.
            assert req('/api/v1/mobile/register', {
                'name': 'Bad Plan', 'email': 'badplan@example.com',
                'password': 'this-is-a-long-test-password', 'requested_tier': 'free',
            })[0] == 422
            registration_status, registration_body, _ = req('/api/v1/mobile/register', {
                'name': 'Android Registered Merchant', 'email': 'android-user@example.com',
                'password': 'android-registration-password-15',
                'requested_tier': 'pro', 'website': base,
            })
            assert registration_status == 201, (registration_status, registration_body)
            registration = json.loads(registration_body)
            assert registration['token'].startswith('bp_mobile_')
            assert registration['plan_status'] == 'awaiting_manual_payment_verification'
            assert req('/api/v1/mobile/register', {
                'name': 'Android Registered Merchant', 'email': 'android-user@example.com',
                'password': 'android-registration-password-15',
                'requested_tier': 'business',
            })[0] == 409
            android_overview = json.loads(req('/api/v1/mobile/overview', token=registration['token'])[1])
            assert android_overview['merchant']['email'] == 'android-user@example.com'
            assert android_overview['subscription']['status'] == 'inactive'
            assert android_overview['subscription']['requested_tier'] == 'pro'
            assert android_overview['subscription']['site_limit'] == 0
            assert android_overview['sites'] == [] and android_overview['transactions'] == []
            assert android_overview['integrations'] == {
                'stripe': False, 'paypal': False, 'revolut': False,
            }
            assert req('/api/v1/mobile/links', {
                'site_id': 'b' * 24, 'title': 'Should not work', 'amount': '12.00'
            }, token=registration['token'])[0] == 422
            android_web_client = urllib.request.build_opener(
                urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            login_page_status, login_page, _ = req('/login', client=android_web_client)
            assert login_page_status == 200
            android_web_csrf = re.search('name="csrf" value="([a-f0-9]+)"', login_page).group(1)
            mobile_to_web, site_page, _ = req('/login', {
                'email': 'android-user@example.com',
                'password': 'android-registration-password-15',
                'csrf': android_web_csrf,
            }, client=android_web_client)
            assert mobile_to_web == 200 and 'Moje web-stranice' not in site_page
            site_status_code, site_page, _ = req('/sites', client=android_web_client)
            assert site_status_code == 200 and '0 / 0' in site_page
            assert req('/admin', client=android_web_client)[0] == 403
            assert req('/api/v1/me', token=registration['token'])[0] == 401
            print('PASS: Android registration -> web login, same user and no free entitlements')

            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            assert req('/sites', {'origin': base, 'csrf': csrf})[0] == 200
            assert req('/sites')[1].find('0 / 0') != -1
            # Configure operator ID ONLY in this throwaway local smoke-test instance.
            user_files = list((project / 'storage/data/users').glob('*.json'))
            user_id = next(json.loads(file.read_text())['id'] for file in user_files
                           if json.loads(file.read_text())['email'] == 'smoke@example.com')
            config_file = project / 'config.php'
            config_text = config_file.read_text()
            config_text, replacements = re.subn(
                r"('admin_user_ids'\s*=>\s*array\s*\()\s*(\))",
                lambda m: m.group(1) + repr(user_id) + ', ' + m.group(2),
                config_text, count=1,
            )
            assert replacements == 1, 'missing operator configuration'
            # Operator-provided legal identity is escaped even when config has markup.
            previous_config = config_text
            config_text = config_text.replace(
                "'legal_operator_name' => ''",
                "'legal_operator_name' => '<script>alert(1)</script>'",
            )
            assert config_text != previous_config, 'legal operator test fixture was not injected'
            config_file.write_text(config_text)
            # Reboot the disposable PHP test server so the modified config is
            # always reloaded. Otherwise fast CI machines may retain a cached
            # require() from the same second and falsely fail this XSS check.
            server.terminate()
            server.wait(timeout=10)
            server = subprocess.Popen(
                ['php', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=project,
                stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            )
            for attempt in range(30):
                try:
                    if req('/api/v1/health')[0] == 200:
                        break
                except urllib.error.URLError:
                    time.sleep(0.1)
            else:
                raise RuntimeError('Reloaded smoke-test server did not start')
            public_contact_status, public_contact, _ = req('/contact')
            assert public_contact_status == 200
            assert '&lt;script&gt;alert(1)&lt;/script&gt;' in public_contact
            assert '<script>alert(1)</script>' not in public_contact
            status, admin_page, _ = req('/admin')
            assert status == 200 and 'Admin Panel' not in admin_page and 'Paketi i licence' in admin_page
            admin_csrf = re.search('name="csrf" value="([a-f0-9]+)"', admin_page).group(1)
            expires = (datetime.now(timezone.utc) + timedelta(days=35)).date().isoformat()
            status, admin_page, _ = req('/admin/plans', {
                'merchant_id': user_id, 'tier': 'business',
                'expires': expires, 'payment_verified': '1', 'csrf': admin_csrf,
            })
            assert status == 200 and 'Plaćeni paket aktiviran' in admin_page
            print('PASS: only operator can confirm paid entitlement after manual payment check')
            assert req('/api/v1/mobile/overview')[0] == 401
            assert req('/api/v1/mobile/login', {
                'email': 'smoke@example.com', 'password': 'incorrect',
            })[0] == 401
            mobile_login_status, mobile_login, _ = req('/api/v1/mobile/login', {
                'email': 'smoke@example.com', 'password': 'this-is-a-long-test-password',
            })
            assert mobile_login_status == 200, (mobile_login_status, mobile_login)
            mobile_token = json.loads(mobile_login)['token']
            assert mobile_token.startswith('bp_mobile_')
            assert req('/api/v1/me', token=mobile_token)[0] == 401
            mobile_status, mobile_body, _ = req('/api/v1/mobile/overview', token=mobile_token)
            assert mobile_status == 200
            assert json.loads(mobile_body)['subscription']['tier'] == 'business'
            assert json.loads(mobile_body)['merchant']['email'] == 'smoke@example.com'
            print('PASS: Android signs into the same merchant, never a WordPress API token')
            status, body, _ = req('/sites')
            assert status == 200 and '5 web-stranica' not in body and '0 / 5' in body
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            status, body, _ = req('/sites', {'origin': base, 'csrf': csrf})
            assert status == 200 and 'bp_lic_' in body
            license_key = re.search(r'bp_lic_[a-f0-9]{64}', body).group(0)
            site_id = re.search(r'/sites/([a-f0-9]{24})/rotate', body).group(1)
            # License cannot be used for a different domain or WordPress installation.
            assert req('/api/v1/license/activate', {
                'license_key': license_key, 'site_url': 'https://wrong.example',
                'installation_id': 'a' * 32,
            })[0] == 403
            status, activated, _ = req('/api/v1/license/activate', {
                'license_key': license_key, 'site_url': base, 'installation_id': 'a' * 32,
            })
            token = json.loads(activated)['api_key']
            assert status == 200 and json.loads(activated)['site_id'] == site_id
            assert json.loads(req('/api/v1/me', token=token)[1])['plan'] == 'business'
            status, snapshot, _ = req('/api/v1/sites/status', token=token)
            assert status == 200
            assert json.loads(snapshot)['website_limit'] == 5
            assert json.loads(snapshot)['websites_used'] == 1
            assert json.loads(snapshot)['last_seen_at'] is None
            payload = {
                'site_url': base, 'installation_id': 'a' * 32,
                'plugin_version': '0.8.14', 'wordpress_version': '6.9.1',
                'woocommerce_version': '10.3.0',
            }
            assert req('/api/v1/sites/sync', {**payload, 'installation_id': 'b' * 32}, token=token)[0] == 403
            assert req('/api/v1/sites/sync', {**payload, 'site_url': 'https://wrong.example'}, token=token)[0] == 403
            assert req('/api/v1/sites/sync', {**payload, 'plugin_version': '<script>'}, token=token)[0] == 422
            assert req('/api/v1/sites/sync', payload, token=token)[0] == 200
            site_status = json.loads(req('/api/v1/sites/status', token=token)[1])
            assert site_status['plugin_version'] == '0.8.14' and site_status['last_seen_at']
            assert '0.8.14' in req('/admin')[1] and site_status['origin'] in req('/sites')[1]
            print('PASS: authenticated, version-bounded WordPress heartbeat and synchronized admin/user views')
            mobile_status, mobile_body, _ = req('/api/v1/mobile/overview', token=mobile_token)
            assert mobile_status == 200
            mobile_data = json.loads(mobile_body)
            assert mobile_data['subscription']['sites_used'] == 1
            assert mobile_data['sites'][0]['id'] == site_id
            assert mobile_data['sites'][0]['plugin_version'] == '0.8.14'
            mobile_create_status, created, _ = req('/api/v1/mobile/links', {
                'site_id': site_id, 'title': 'Created on Android',
                'amount': '24.90', 'description': 'Synced from user panel',
            }, token=mobile_token)
            assert mobile_create_status == 201, (mobile_create_status, created)
            mobile_link_id = json.loads(created)['id']
            assert req('/p/' + mobile_link_id)[0] == 200
            assert mobile_link_id in req('/api/v1/links', token=token)[1]
            switch_status, switched, _ = req('/api/v1/mobile/links/' + mobile_link_id + '/toggle',
                {}, token=mobile_token)
            assert switch_status == 200 and json.loads(switched)['active'] is False
            assert req('/p/' + mobile_link_id)[0] == 404
            switch_status, switched, _ = req('/api/v1/mobile/links/' + mobile_link_id + '/toggle',
                {}, token=mobile_token)
            assert switch_status == 200 and json.loads(switched)['active'] is True
            assert req('/p/' + mobile_link_id)[0] == 200
            assert req('/api/v1/mobile/links', {
                'site_id': 'f' * 24, 'title': 'Illegal link', 'amount': '99.00',
            }, token=mobile_token)[0] == 422
            print('PASS: Android overview and payment link creation share the WP/web merchant data store')
            assert req('/api/v1/license/activate', {
                'license_key': license_key, 'site_url': base, 'installation_id': 'b' * 32,
            })[0] == 403
            print('PASS: domain and installation-bound WP license activation and API sync')
            status, body, _ = req('/dashboard/links', {
                'site_id': site_id, 'title': 'Test service', 'amount': '15,90', 'csrf': csrf,
            })
            assert status == 200
            link_id = re.search('/p/([a-f0-9]{24})', body).group(1)
            assert req('/p/' + link_id)[0] == 200
            print('PASS: payment link creation and public access')
            status, body, _ = req('/dashboard/links/' + link_id + '/toggle', {'csrf': csrf})
            assert status == 200 and 'Isključena' in body and req('/p/' + link_id)[0] == 404
            assert req('/api/v1/links/' + link_id)[0] == 404
            status, body, _ = req('/dashboard/links/' + link_id + '/toggle', {'csrf': csrf})
            assert status == 200 and req('/p/' + link_id)[0] == 200
            print('PASS: disable/enable public links (HTML and REST)')
            attacker = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            status, html, _ = req('/register', client=attacker)
            attacker_csrf = re.search('name="csrf" value="([a-f0-9]+)"', html).group(1)
            status, html, _ = req('/register', {
                'name': 'Other Merchant', 'email': 'other@example.com',
                'requested_tier': 'start', 'password': 'this-is-another-long-password', 'csrf': attacker_csrf,
            }, client=attacker)
            assert status == 200 and 'Moje web-stranice' in html
            assert req('/admin', client=attacker)[0] == 403
            assert req('/admin/plans/suspend', {
                'merchant_id': user_id, 'csrf': attacker_csrf,
            }, client=attacker)[0] == 403
            attacker_id = next(json.loads(file.read_text())['id']
                for file in (project / 'storage/data/users').glob('*.json')
                if json.loads(file.read_text())['email'] == 'other@example.com')
            status, admin_page, _ = req('/admin/plans', {
                'merchant_id': attacker_id, 'tier': 'start',
                'expires': expires, 'payment_verified': '1', 'csrf': admin_csrf,
            })
            assert status == 200 and 'Plaćeni paket aktiviran' in admin_page
            status, html, _ = req('/dashboard/links/' + link_id + '/toggle', {'csrf': attacker_csrf}, client=attacker)
            assert status == 404 and req('/p/' + link_id)[0] == 200
            print('PASS: cross-merchant link mutation rejected')
            attacker_mobile_status, attacker_mobile_body, _ = req('/api/v1/mobile/login', {
                'email': 'other@example.com', 'password': 'this-is-another-long-password',
            })
            assert attacker_mobile_status == 200
            attacker_mobile_token = json.loads(attacker_mobile_body)['token']
            attacker_overview = json.loads(req('/api/v1/mobile/overview', token=attacker_mobile_token)[1])
            assert all(item['id'] != site_id for item in attacker_overview['sites'])
            assert all(item['id'] != mobile_link_id for item in attacker_overview['links'])
            assert req('/api/v1/mobile/links', {
                'site_id': site_id, 'title': 'Cross-owner attempt', 'amount': '50.00',
            }, token=attacker_mobile_token)[0] == 422
            assert req('/api/v1/mobile/links/' + mobile_link_id + '/toggle',
                {}, token=attacker_mobile_token)[0] == 404
            assert req('/api/v1/mobile/logout', {}, token=attacker_mobile_token)[0] == 200
            assert req('/api/v1/mobile/overview', token=attacker_mobile_token)[0] == 401
            print('PASS: merchant isolation and immediate Android token logout revocation')
            status, body, _ = req('/settings/stripe-connect/callback?state=' + '0'*48 + '&code=ac_test')
            assert status == 419 and 'Nevaljana ili istekla' in body
            assert req('/webhooks/stripe-connect', data=b'{}', method='POST')[0] == 404
            print('PASS: Stripe Connect rejects unsolicited callback and disabled webhook')
            status, body, _ = req('/settings')
            assert status == 200
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            assert req('/settings/token', {'origin': base, 'csrf': csrf})[0] == 410
            assert req('/api/v1/me', token=token)[0] == 200
            status, result, _ = req('/api/v1/links', {'title': '=DANGEROUS', 'amount': '25.01', 'description': '<script>alert(1)</script>'}, token=token)
            assert status == 201
            api_id = json.loads(result)['id']
            assert req('/p/' + api_id)[0] == 200
            status, rendered, _ = req('/p/' + api_id)
            assert '&lt;script&gt;alert(1)&lt;/script&gt;' in rendered
            assert '<script>alert(1)</script>' not in rendered
            status, link_json, _ = req('/api/v1/links/' + api_id, token=token)
            assert status == 200 and json.loads(link_json)['description'] == '<script>alert(1)</script>'
            print('PASS: safe JSON link description persistence and escaped checkout HTML')
            # A signed, merchant-scoped list powers the WordPress Gutenberg selector.
            status, listing, _ = req('/api/v1/links', token=token)
            assert status == 200 and api_id in {item['id'] for item in json.loads(listing)['items']}
            assert link_id in {item['id'] for item in json.loads(listing)['items']}
            assert req('/api/v1/links')[0] == 401
            other_status, other_page, _ = req('/sites', client=attacker)
            assert other_status == 200 and site_id not in other_page
            other_csrf = re.search('name="csrf" value="([a-f0-9]+)"', other_page).group(1)
            other_status, other_page, _ = req('/sites', {
                'origin': f'http://localhost:{port}', 'csrf': other_csrf,
            }, client=attacker)
            assert other_status == 200 and 'bp_lic_' in other_page
            other_license = re.search(r'bp_lic_[a-f0-9]{64}', other_page).group(0)
            other_status, other_activated, _ = req('/api/v1/license/activate', {
                'license_key': other_license,
                'site_url': f'http://localhost:{port}',
                'installation_id': 'c' * 32,
            })
            assert other_status == 200
            other_token = json.loads(other_activated)['api_key']
            other_status, other_listing, _ = req('/api/v1/links', token=other_token)
            assert other_status == 200 and json.loads(other_listing)['items'] == []
            print('PASS: Gutenberg API link list requires token and isolates merchants')
            status, admin_html, _ = req('/admin')
            assert status == 200
            admin_csrf2 = re.search('name="csrf" value="([a-f0-9]+)"', admin_html).group(1)
            status, admin_html, _ = req('/admin/plans/suspend', {
                'merchant_id': attacker_id, 'csrf': admin_csrf2,
            })
            assert status == 200 and 'Paket suspendiran' in admin_html
            assert req('/api/v1/sites/status', token=other_token)[0] == 403
            assert req('/api/v1/sites/sync', payload, token=other_token)[0] == 403
            print('PASS: admin-only plan suspension immediately rejects the plugin API')
            # This product does not store or render raw card inputs.
            assert 'name="card_number"' not in rendered and 'name="cvc"' not in rendered
            status, raw, _ = req('/api/v1/checkout', {
                'provider': 'untrusted', 'amount': '1.00', 'reference': 'wp_test',
                'description': 'Mock payment', 'success_url': base + '/success', 'cancel_url': base + '/cancel',
            }, token=token)
            assert status == 422
            print('PASS: unsupported API provider is rejected')
            print('PASS: authenticated merchant identity and API-created links')
            status, body, _ = req('/settings')
            token_id = re.search(r'/settings/tokens/([a-f0-9]{64})/revoke', body).group(1)
            status, body, _ = req('/settings/tokens/' + token_id + '/revoke', {'csrf': csrf})
            assert status == 200 and 'Opozvan' in body
            assert req('/api/v1/me', token=token)[0] == 401
            print('PASS: API token revocation')
            status, body, _ = req('/dashboard/audit')
            assert status == 200 and 'api_key_revoked' in body and 'payment_link_disabled' in body
            user_files = list((project / 'storage/data/users').glob('*.json'))
            user_id = next(json.loads(p.read_text())['id'] for p in user_files
                           if json.loads(p.read_text())['email'] == 'smoke@example.com')
            txn_dir = project / 'storage/data/transactions'
            txn_dir.mkdir(exist_ok=True)
            example_txn = {'id': 'f' * 32, 'user_id': user_id, 'reference': '+REF',
                           'description': '=SUM(1,1)', 'amount_minor': 1500, 'currency': 'EUR',
                           'status': 'pending', 'created_at': '2026-01-01T00:00:00+00:00'}
            (txn_dir / ('f' * 32 + '.json')).write_text(json.dumps(example_txn))
            status, csv, headers = req('/dashboard/transactions.csv')
            assert status == 200 and 'Transaction ID' in csv and "'=SUM(1,1)" in csv and "'+REF" in csv
            # Register sandbox credentials only in isolated temporary storage; no provider network calls.
            status, settings_html, _ = req('/settings')
            assert status == 200 and 'PayPal Sandbox' in settings_html and 'Revolut Sandbox' in settings_html
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', settings_html).group(1)
            status, settings_html, _ = req('/settings/paypal', {
                'csrf': csrf, 'client_id': 'sandboxclient1234567',
                'secret': 'sandboxsecret1234567', 'webhook_id': 'TESTWEBHOOK01',
            })
            assert status == 200 and 'Konfiguriran' in settings_html
            status, settings_html, _ = req('/settings/revolut', {
                'csrf': csrf, 'secret': 'sk_sandbox_dont_use_as_real_123456789',
                'webhook_secret': 'wsk_fake_sandbox_secret_12345',
            })
            assert status == 200 and 'Konfiguriran' in settings_html
            status, payment_html, _ = req('/p/' + link_id)
            assert status == 200 and 'PayPal Sandbox' in payment_html and 'Revolut Sandbox' in payment_html
            assert 'sandboxsecret1234567' not in payment_html and 'wsk_fake_sandbox_secret_12345' not in payment_html
            assert req('/webhooks/revolut/' + user_id, data=b'{}', method='POST')[0] == 400
            assert req('/webhooks/paypal/' + user_id, data=b'{}', method='POST')[0] == 400
            print('PASS: sandbox provider setup, payment choice and unsigned webhook rejection')
            print('PASS: account audit and CSV export')
            # One client/IP rotating email addresses must still be throttled globally.
            limited = urllib.request.build_opener(
                urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            status, login_page, _ = req('/login', client=limited)
            login_csrf = re.search('name="csrf" value="([a-f0-9]+)"', login_page).group(1)
            for attempt in range(30):
                status, login_page, _ = req('/login', {
                    'email': f'unknown-{attempt}@example.test',
                    'password': 'wrong-password-123456789',
                    'csrf': login_csrf,
                }, client=limited)
                assert status == 200 and 'Neuspješna prijava' in login_page
            status, login_page, _ = req('/login', {
                'email': 'smoke@example.com',
                'password': 'this-is-a-long-test-password',
                'csrf': login_csrf,
            }, client=limited)
            assert status == 200 and 'Neuspješna prijava' in login_page
            print('PASS: IP-wide login throttle rejects email rotation')
            print('All HTTP smoke tests passed.')
        finally:
            server.terminate()
            try:
                server.wait(timeout=3)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait(timeout=3)


if __name__ == '__main__':
    main()
