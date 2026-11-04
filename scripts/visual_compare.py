#!/usr/bin/env python3
"""Compare an actual screenshot against a user-provided UI reference.

Not a certification of 1:1 parity. No images are generated or altered.
"""
from __future__ import annotations

import argparse
from pathlib import Path
from PIL import Image, ImageChops, ImageStat


def compare(reference: Path, actual: Path) -> tuple[float, float]:
    with Image.open(reference) as source, Image.open(actual) as rendered:
        a = source.convert('RGB')
        b = rendered.convert('RGB')
        if a.size != b.size:
            raise ValueError(f'Viewport mismatch: reference {a.size}, actual {b.size}. Re-render at the same width and height; do not resize.')
        difference = ImageChops.difference(a, b)
        average_error = sum(ImageStat.Stat(difference).mean) / 3
        data = difference.tobytes()
        different_pixels = sum(1 for i in range(0, len(data), 3) if any(data[i:i + 3]))
        return average_error, different_pixels / (a.width * a.height)


def main() -> None:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('reference', type=Path)
    p.add_argument('actual', type=Path)
    args = p.parse_args()
    mae, fraction = compare(args.reference, args.actual)
    print(f'Average absolute RGB error: {mae:.2f}/255')
    print(f'Non-identical pixels: {fraction * 100:.2f}%')
    print('Caution: typography, browser rendering and content differences affect this number.')


if __name__ == '__main__':
    main()
