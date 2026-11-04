#!/usr/bin/env python3
"""Build safe, repeatable development ZIPs without credentials or user data."""
from __future__ import annotations

import argparse
import hashlib
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

VERSION = '0.8.2'
ROOT = Path(__file__).resolve().parent.parent


def eligible(path: Path) -> bool:
    rel = path.relative_to(ROOT)
    parts = rel.parts
    if '__pycache__' in parts or '.git' in parts or '.pytest_cache' in parts:
        return False
    if any(part in {'dist', 'node_modules', 'visual-qa'} for part in parts):
        return False
    if path.name in {'config.php', '.env', '.DS_Store'} or path.suffix in {'.lock', '.pyc'}:
        return False
    if len(parts) >= 4 and parts[:3] == ('platform', 'storage', 'data'):
        return path.name == '.gitkeep'
    return True


def write_zip(dst: Path, src: Path, prefix: Path) -> None:
    with ZipFile(dst, 'w', compression=ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(src.rglob('*')):
            if path.is_file() and eligible(path):
                rel = prefix / path.relative_to(src)
                archive.write(path, str(rel).replace('\\', '/'))
    print(f'{dst.name}: {dst.stat().st_size:,} bytes')


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--output', type=Path, default=ROOT / 'dist')
    args = parser.parse_args()
    args.output.mkdir(parents=True, exist_ok=True)
    names = (
        (f'Brendigo-Pay-source-{VERSION}.zip', ROOT, Path('Brendigo-Pay')),
        (f'Brendigo-Pay-platform-{VERSION}.zip', ROOT / 'platform', Path('platform')),
        (f'brendigo-pay-wordpress-{VERSION}.zip', ROOT / 'wordpress-plugin' / 'brendigo-pay', Path('brendigo-pay')),
    )
    checksums = []
    for filename, src, prefix in names:
        output = args.output / filename
        write_zip(output, src, prefix)
        digest = hashlib.sha256(output.read_bytes()).hexdigest()
        checksums.append(f'{digest}  {filename}')
    (args.output / 'SHA256SUMS.txt').write_text('\n'.join(checksums) + '\n', encoding='utf-8')


if __name__ == '__main__':
    main()
