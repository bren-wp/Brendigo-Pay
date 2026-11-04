#!/usr/bin/env python3
"""Local HTTP smoke tests. Uses an isolated temporary copy and never calls Stripe."""
import contextlib
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
    with tempfile.TemporaryDirectory(prefix='bp-v03-smoke-') as td:
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
                if token:
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

            status, body, _ = req('/register')
            assert status == 200
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            status, body, _ = req('/register', {
                'name': 'Smoke Merchant', 'email': 'smoke@example.com',
                'password': 'this-is-a-long-test-password', 'csrf': csrf,
            })
            assert status == 200 and 'Smoke Merchant' in body
            print('PASS: account registration and authenticated dashboard')
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            status, body, _ = req('/dashboard/links', {'title': 'Test service', 'amount': '15,90', 'csrf': csrf})
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
                'password': 'this-is-another-long-password', 'csrf': attacker_csrf,
            }, client=attacker)
            assert status == 200 and 'Other Merchant' in html
            status, html, _ = req('/dashboard/links/' + link_id + '/toggle', {'csrf': attacker_csrf}, client=attacker)
            assert status == 404 and req('/p/' + link_id)[0] == 200
            print('PASS: cross-merchant link mutation rejected')
            status, body, _ = req('/settings/stripe-connect/callback?state=' + '0'*48 + '&code=ac_test')
            assert status == 419 and 'Nevaljana ili istekla' in body
            assert req('/webhooks/stripe-connect', data=b'{}', method='POST')[0] == 404
            print('PASS: Stripe Connect rejects unsolicited callback and disabled webhook')
            status, body, _ = req('/settings')
            assert status == 200
            csrf = re.search('name="csrf" value="([a-f0-9]+)"', body).group(1)
            status, body, _ = req('/settings/token', {'origin': base, 'csrf': csrf})
            assert status == 200
            token = re.search(r'bp_test_[a-f0-9]{64}', body).group(0)
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
            other_status, other_page, _ = req('/settings', client=attacker)
            assert other_status == 200
            other_csrf = re.search('name="csrf" value="([a-f0-9]+)"', other_page).group(1)
            other_status, other_page, _ = req('/settings/token', {'origin': base, 'csrf': other_csrf}, client=attacker)
            assert other_status == 200
            other_token = re.search(r'bp_test_[a-f0-9]{64}', other_page).group(0)
            other_status, other_listing, _ = req('/api/v1/links', token=other_token)
            assert other_status == 200 and json.loads(other_listing)['items'] == []
            print('PASS: Gutenberg API link list requires token and isolates merchants')
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
