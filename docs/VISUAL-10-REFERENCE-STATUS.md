# Brendigo Pay — vizualni prag prihvaćanja

Cilj: strogo **manje od 10 %** piksela s luminantnom razlikom većom od 8/255 u **svakoj** pojedinačnoj referenci koja predstavlja zaslon.

- `01` WooCommerce, `02` Gutenberg i `03` WordPress admin: potrebne stvarne snimke instaliranog WordPressa u istim uvjetima
- `04–09`: automatske snimke weba, panela, dijaloga i checkouta
- `10`: identitetski vodič, ne aplikacijski ekran; provjeriti logo, ikone, token paletu, fontove, kontrole zasebno

Skripta `scripts/visual_audit_10.py` prikazuje koliko je izmjerenih zaslona ispod 10 % i koliko nema izmjerenu snimku. Povratni kod ostaje nula u dijagnostičkom načinu, ali je moguće zatražiti strogo prihvaćanje:

```bash
python scripts/visual_audit_10.py --screenshots visual-qa --output visual-qa/assessment --diffs --target 10 --require-target
```

Workflow `.github/workflows/visual-parity.yml` može se pokrenuti ručno s `enforce_target=true`. Taj korak neće proći dok svih devet zaslona nema rezultat ispod 10 %. Referenca 10 ostaje zaseban ručni audit i ne prikazuje se lažno kao izmjeren pixel-diff.

**Važno:** čak ni rezultat ispod 10 % nije dokaz potpune 1:1 funkcionalnosti, pristupačnosti ili financijske produkcijske certifikacije.
