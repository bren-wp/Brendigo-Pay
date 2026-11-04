#!/usr/bin/env python3
"""Measure per-region RGB differences to Brendigo Pay's 1448x1086 references.

No images are edited, resized or generated. This is diagnostic, not a 1:1 proof.
Install Pillow locally: python3 -m pip install Pillow
"""
from __future__ import annotations

import argparse
from pathlib import Path

try:
    from PIL import Image, ImageChops, ImageStat
except ImportError as exc:
    raise SystemExit("Pillow required: python3 -m pip install Pillow") from exc

ROOT = Path(__file__).resolve().parents[1]

SCREENS = {
    "woocommerce": (
        "01-woocommerce-checkout.jpeg",
        {
            "header": (0, 0, 1448, 60),
            "steps": (385, 60, 1040, 111),
            "billing": (50, 185, 797, 574),
            "payment-methods": (50, 582, 798, 1034),
            "summary": (819, 185, 1366, 756),
            "checkout-action": (819, 760, 1365, 1016),
        },
    ),
    "gutenberg": (
        "02-gutenberg.jpeg",
        {
            "wp-toolbar": (0, 0, 1448, 102),
            "block-inserter": (147, 102, 446, 1086),
            "page-canvas": (446, 102, 1082, 1086),
            "payment-card": (475, 432, 1057, 878),
            "inspector": (1082, 41, 1448, 1086),
        },
    ),
    "wp-admin": (
        "03-wp-admin.jpeg",
        {
            "wp-sidebar": (0, 0, 166, 1086),
            "brand-header": (193, 39, 1409, 171),
            "tabs": (193, 229, 1394, 284),
            "connection": (193, 297, 905, 410),
            "environment": (914, 297, 1395, 410),
            "api": (193, 423, 1395, 550),
            "providers": (193, 611, 1395, 749),
            "features": (193, 757, 1395, 982),
        },
    ),
}


def stats(reference: Image.Image, actual: Image.Image, box: tuple[int, int, int, int]) -> tuple[float, float]:
    a = reference.crop(box)
    b = actual.crop(box)
    d = ImageChops.difference(a, b)
    mean = sum(ImageStat.Stat(d).mean) / 3.0
    px = d.tobytes()
    # Count only visible differences to reduce the effect of JPEG artifacts.
    changed = sum(1 for i in range(0, len(px), 3) if max(px[i:i + 3]) > 16)
    return mean, changed / (a.width * a.height) * 100.0


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("screen", choices=sorted(SCREENS))
    parser.add_argument("actual", type=Path, help="Real screenshot at 1448x1086")
    args = parser.parse_args()
    filename, regions = SCREENS[args.screen]
    reference_path = ROOT / "design" / "references" / filename
    with Image.open(reference_path) as raw_ref, Image.open(args.actual) as raw_actual:
        ref, actual = raw_ref.convert("RGB"), raw_actual.convert("RGB")
        if ref.size != (1448, 1086) or actual.size != ref.size:
            parser.error(
                f"Viewport mismatch: reference {ref.size}, actual {actual.size}. "
                "Do not resize; capture the same viewport."
            )
        print(f"Screen: {args.screen} · {ref.width}x{ref.height}")
        print(f"{'Area':<22} {'MAE / 255':>11} {'Pixels Δ>16':>13}")
        for name, region in regions.items():
            if not (0 <= region[0] < region[2] <= ref.width and 0 <= region[1] < region[3] <= ref.height):
                parser.error(f"Invalid region: {name}: {region}")
            mae, changed = stats(ref, actual, region)
            print(f"{name:<22} {mae:>11.2f} {changed:>12.2f}%")
    print("Diagnostics only; Woo theme, actual data, browser rendering and JPEG compression affect differences.")


if __name__ == "__main__":
    main()
