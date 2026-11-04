#!/usr/bin/env python3
"""Offline Android source contract. Does not replace an emulator, signed Play or penetration test."""
from pathlib import Path
r = Path(__file__).resolve().parents[1]
android = r/'android'
src = (android/'app/src/main/java/com/brendigo/pay/MainActivity.java').read_text()
api = (android/'app/src/main/java/com/brendigo/pay/ApiClient.java').read_text()
secrets = (android/'app/src/main/java/com/brendigo/pay/SecureStore.java').read_text()
manifest = (android/'app/src/main/AndroidManifest.xml').read_text()
build = (android/'app/build.gradle').read_text()
server = (r/'platform/src/MobileApi.php').read_text()
routing = (r/'platform/public/index.php').read_text()
assert (android/'app/src/main/res/drawable-nodpi/brand_icon.png').is_file()
assert 'minSdk 24' in build and 'targetSdk 36' in build and "versionName '0.8.4'" in build
assert 'usesCleartextTraffic="false"' in manifest and 'allowBackup="false"' in manifest
assert 'AndroidKeyStore' in secrets and 'AES/GCM/NoPadding' in secrets
assert 'setInstanceFollowRedirects(false)' in api and 'HttpsURLConnection' in api
assert 'MAX_RESPONSE' in api and 'https' in api
assert 'WebView' not in src and 'card_number' not in src and 'cvc' not in src
for title in ['Pregled','Transakcije','Linkovi','Web-stranice']:
    assert title in src
for endpoint in ['login', 'overview', 'links', 'logout']:
    assert f"'/api/v1/mobile/{endpoint}'" in routing
assert "password_verify(" in server and "mobile_tokens" in server
assert "activeOrFail" in server and "user_id" in server
print('PASS: Android secure source, version, design screens and merchant API contracts')
