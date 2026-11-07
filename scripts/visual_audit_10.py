#!/usr/bin/env python3
"""Audit all ten Brendigo Pay conceptual references against actual captured UI.

A missing native screenshot is reported as unmeasured, never marked passing.
Reference images are not inserted into application screens or resized.
Pixel error is descriptive and is NOT proof of accessibility or 1:1 parity.
"""
from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path

from PIL import Image, ImageChops, ImageStat

ROOT = Path(__file__).resolve().parents[1]
SCREENS = (
    ("01-woocommerce-checkout.jpeg", None, "WooCommerce Classic/Blocks checkout", "Requires running WooCommerce, real theme, configured test cart"),
    ("02-gutenberg.jpeg", None, "Gutenberg editor", "Requires running WordPress editor and registered Brendigo Pay block"),
    ("03-wp-admin.jpeg", None, "WordPress plugin admin", "Requires real WordPress admin screenshot, not a simulated WP chrome"),
    ("04-transactions.jpeg", "04-transactions.png", "Web transaction list", "Authenticated screenshot; sandbox test dataset differs from concept"),
    ("05-payment-link-modal.jpeg", "05-payment-link-modal.png", "New payment link modal", "Authenticated modal, fields, preview and controls"),
    ("06-merchant-dashboard.jpeg", "06-merchant-dashboard.png", "Merchant dashboard", "Actual sandbox data; illustrative reference values differ"),
    ("07-payment-checkout.jpeg", "07-payment-checkout.png", "Hosted payment selection", "Actual provider flow stays server-hosted; no local card inputs"),
    ("08-pricing.jpeg", "08-pricing.png", "Pricing", "Reference shows free Start, actual product has paid Start at 19,90 EUR"),
    ("09-home.jpeg", "09-home.png", "Landing page", "Reference has example reviews and volume claims; these are not reproduced without evidence"),
    ("10-brand-identity.jpeg", None, "Brand identity sheet", "Reference is a style guide, not a standalone application screen; verify brand token contract separately"),
)


def assess(reference: Path, actual: Path, diff_path: Path | None) -> dict:
    with Image.open(reference) as source, Image.open(actual) as captured:
        ref = source.convert("RGB")
        render = captured.convert("RGB")
        if ref.size != render.size:
            return {"status": "viewport-mismatch", "reference_px": list(ref.size), "captured_px": list(render.size)}
        diff = ImageChops.difference(ref, render)
        mae = round(sum(ImageStat.Stat(diff).mean) / 3.0, 3)
        # Threshold uses weighted luma: avoids flagging small JPEG compression noise as a major delta.
        histogram = diff.convert("L").histogram()
        changed = sum(histogram[9:]) / (ref.width * ref.height)
        if diff_path is not None:
            diff_path.parent.mkdir(parents=True, exist_ok=True)
            diff.save(diff_path)
        return {"status": "measured-not-certified", "reference_px": list(ref.size),
                "captured_px": list(render.size), "rgb_mae_0_255": mae,
                "pixels_luma_difference_gt8_percent": round(changed * 100, 2)}


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--screenshots", type=Path, required=True,
                        help="Directory with viewport screenshots captured from running software")
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--diffs", action="store_true", help="Save diagnostic difference images as audit artifacts")
    parser.add_argument("--target", type=float, default=10.0, help="Strict maximum percent changed pixels, per measured screen")
    parser.add_argument("--require-target", action="store_true",
                        help="Return failure unless every measurable screen is below target")
    args = parser.parse_args()
    if not 0 <= args.target <= 100:
        parser.error("--target must be between 0 and 100")
    args.output.mkdir(parents=True, exist_ok=True)
    results = []
    for reference_name, actual_name, title, caveat in SCREENS:
        reference = ROOT / "design" / "references" / reference_name
        if not reference.is_file():
            raise FileNotFoundError(reference)
        result = {"reference": reference_name, "screen": title, "actual": actual_name, "notes": caveat}
        actual = args.screenshots / actual_name if actual_name else None
        if actual is None:
            result["status"] = "requires-real-host-capture" if reference_name[:2] != "10" else "style-guide-manual-check"
        elif not actual.is_file():
            result["status"] = "screenshot-missing"
        else:
            result.update(assess(reference, actual,
                                 args.output / "diffs" / (reference.stem + ".png") if args.diffs else None))
        results.append(result)

    measured = [rec for rec in results if "pixels_luma_difference_gt8_percent" in rec]
    unmeasured_screens = [rec for rec in results
                          if "pixels_luma_difference_gt8_percent" not in rec
                          and rec["status"] != "style-guide-manual-check"]
    for rec in measured:
        rec["below_target"] = rec["pixels_luma_difference_gt8_percent"] < args.target
    passed = sum(bool(rec["below_target"]) for rec in measured)
    target_met = len(measured) == 9 and passed == 9 and not unmeasured_screens
    report = {"generated_utc": datetime.now(timezone.utc).isoformat(),
              "source": "design/references", "viewport_expected": [1448, 1086],
              "target_percent_strict": args.target,
              "measured_screens": len(measured), "screens_below_target": passed,
              "unmeasured_screens": len(unmeasured_screens),
              "all_nine_screens_below_target": target_met,
              "brand_identity_requires_separate_manual_review": True,
              "certified_pixel_parity": False, "records": results}
    (args.output / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    lines = [
        "# Brendigo Pay — usporedba svih deset referenci",
        "",
        "Ovo je **dijagnostičko mjerenje**, ne potvrda 1:1. JPEG referentne slike su koncepti.",
        "Stvarni rezultat može odstupati zbog sadržaja, tema WordPressa i platformskih fontova.",
        "",
        f"**Cilj: svi pojedinačni zasloni strogo ispod {args.target:g} %.**",
        f"**Prolazi: {passed}/{len(measured)} izmjerenih zaslona; bez snimke: {len(unmeasured_screens)}.**",
        "**Ukupni kriterij zaslona:** " + ("ISPUNJEN" if target_met else "NIJE ISPUNJEN") + ".",
        "Brand identity (referenca 10) je vodič, ne zaslon; zahtijeva odvojenu ručnu provjeru.",
        "",
        "| Referenca | Zaslon | Status | MAE RGB | Pikseli s razlikom >8 |",
        "|---|---|---|---:|---:|",
    ]
    for rec in results:
        lines.append(f"| {rec['reference']} | {rec['screen']} | {rec['status']} | "
                     f"{rec.get('rgb_mae_0_255', '—')} | "
                     f"{str(rec.get('pixels_luma_difference_gt8_percent', '—')) + '%' if 'pixels_luma_difference_gt8_percent' in rec else '—'} |")
    lines += ["", "## Ograničenja i sljedeće provjere", ""]
    for rec in results:
        lines.append(f"- **{rec['screen']}**: {rec['notes']}; status: `{rec['status']}`.")
    lines += ["", "Mjerene snimke moraju biti točno 1448×1086, DPR 1, zoom 100%. Bez automatskog skaliranja.",
              "WordPress/WooCommerce snimke zahtijevaju stvarnu instalaciju, kompatibilnu temu i testne podatke.",
              "Boje i grafike moraju biti implementirane u komponentama; referentna slika ne smije biti podloga UI-ja.",
              "Nikada ne koristiti brojčani MAE kao jedini kriterij dostupnosti, funkcionalnosti ili certifikacije.", ""]
    (args.output / "report.md").write_text("\n".join(lines), encoding="utf-8")
    for rec in results:
        print(rec["reference"], rec["status"], rec.get("rgb_mae_0_255", ""))
    print("Report:", args.output / "report.md")
    print(f"Target strictly <{args.target:g}%: {passed}/{len(measured)} measured; {len(unmeasured_screens)} missing")
    if args.require_target and not target_met:
        raise SystemExit("FAIL: screens not all captured and strictly below visual threshold")


if __name__ == "__main__":
    main()
