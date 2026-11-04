# Stvarne WordPress/WooCommerce snimke — postupak

U ovom repozitoriju postoje samo **stvarne lokalne snimke web-platforme**. `design/references/02-gutenberg.jpeg`, `03-wp-admin.jpeg` i `01-woocommerce-checkout.jpeg` su **vizualni ciljevi, nisu snimke instaliranog dodatka**.

1. Instalirajte WordPress, WooCommerce i dodatak v0.8.0 u zasebno testno okruženje.
2. Zabilježite točan WordPress/WooCommerce/tema/browser/OS i DPR; koristite 1448×1086 na 100% zoom.
3. Povežite testnog trgovca, izradite testni link i testnu narudžbu. Nikad ne prikazujte live podatke.
4. Snimite `wordpress-admin-1448.png`, `gutenberg-editor-1448.png`, `woocommerce-classic-1448.png` i `woocommerce-blocks-1448.png`, bez skrivenih popupova osim ako je upravo referentni prikaz.
5. Pokrenite `python3 scripts/visual_audit_wp.py SCREEN screenshot.png` pa zasebno usporedite razmake, položaje, boje, ikone i tipografiju.
6. Tek nakon stvarne provjere dodajte snimke u `docs/screenshots/wordpress/` i ažurirajte marketing README.

**Ne prikazivati dizajnerske reference kao stvarne screenshotove dodatka.**
