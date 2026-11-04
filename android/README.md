# Brendigo Pay Android — 0.8.4 (SANDBOX)

Nativna Java Android aplikacija za korisnike Brendigo Pay web-platforme. **Ne obrađuje stvarne uplate**. Izvorni kod je u \`/android\`; Android aplikacija koristi isti merchant račun, podatke, transakcije, licencije i platne poveznice kao web i WordPress. Ne koristi WebView, vanjski CDN ni analytics SDK.

## Zasloni

- **Prijava**: HTTPS adresa vlastite instalacije platforme i postojeći korisnički račun. Lozinka se ne sprema.
- **Pregled**: stvarni sandbox iznosi/izvještaji, 14-dnevni grafikon iz zapisa i stanje paketa.
- **Transakcije**: zadnjih 50 transakcija sa statusom i iznosom.
- **Linkovi**: zadnjih 50 linkova; kreiranje novog vezano uz odabranu, već aktiviranu licenciranu web-stranicu.
- **Web-stranice**: domene, statusi WordPress instalacija, verzija dodatka, zadnji heartbeat i iskorištenost licence.
- **Račun**: otvaranje zaštićenog web panela i odjava koja opoziva mobilni bearer token.

Sve informacije dolaze s istog \`platform/storage/data\` JSON backend-a kao web i WordPress. Nakon aktivacije domene u WordPressu novi status dostupan je po osvježenju Android prikaza. Nisu uključene demonstracijske/u izmišljene transakcije.

## API i sigurnost

- \`POST /api/v1/mobile/login\` — postojeća e-mail/lozinka, Argon2id i IP/account rate-limit; odgovor je jednokratan bearer token s rokom 7 dana.
- \`GET /api/v1/mobile/overview\` — trgovcu izoliran status pretplate, metrike, linkovi, transakcije i njegove web-stranice.
- \`POST /api/v1/mobile/links\` — nova naplatna poveznica (samo aktivan plaćeni plan i vlastita aktivirana domena).
- \`POST /api/v1/mobile/logout\` — trenutačna revokacija mobilnog tokena na serveru.

Mobilni token je različit od WordPress instalacijskog tokena i **ne daje** administratorske ovlasti. Hash se sprema na server; mobilna aplikacija koristi Android Keystore AES-256-GCM + lokalni SharedPreferences bez backup-a. HTTPS je obavezan, nema cleartext prometa, preusmjeravanja, pohrane kartica ni ugrađenih API tajni. Lozinka nije trajno pohranjena.

**Napomena:** lokalna odjava bez dostupnog servera briše uređajni token, ali udaljena revokacija možda nije završila. Server automatski odbacuje tokene nakon 7 dana.

## Gradnja

Potrebno: Android SDK 36, Build Tools 36.0.0, JDK 17, Gradle 9.4.1, Android Gradle Plugin 9.2.0.

\`\`\`bash
cd android
gradle :app:assembleDebug :app:bundleRelease
\`\`\`

Output:
- \`app/build/outputs/apk/debug/app-debug.apk\` — **instalacijski APK**, potpisan automatskim **razvojnim** ključem, appId \`com.brendigo.pay.debug\`.
- \`app/build/outputs/bundle/release/app-release.aab\` — **NEPOTPISANI AAB**, služi za testiranje gradnje; ne može na Play bez vlastitog release/upload keystorea.

GitHub Actions gradi oba formata. Tek nakon privatnog potpisivanja, vanjskih E2E i sigurnosnih provjera, API migracije i potvrđenih PSP integracija može se pripremiti produkcijski AAB. **Ne prenosi razvojni/unsigned paket u produkciju Google Playa.**

## Vizualni sustav i otvorene provjere

UI koristi Brendigo brand asset iz \`platform/public/assets/app-icon-512.png\`, plavo/tirkiznu paletu, tamnoplavu navigaciju, plave gradijente, bijele kartice i hrvatski tekst kao web dizajn. Layout je prilagodljiv mobilnim širinama. Nema generiranih/fiktivnih screenshotova Android aplikacije. 1:1 vizualni QA na fizičkim uređajima i emulatorima još je potreban.

© Brendigo · https://brendigo.com
