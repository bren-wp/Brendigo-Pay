#!/usr/bin/env python3
"""Report duplicate CSS selectors for manual review; no destructive auto-fix.

Selectors inside nested @media/@container are not a defect by themselves.
The report is a heuristic triage aid, not a final dead-code determination.
"""
from pathlib import Path
from collections import Counter
import re
ROOT = Path(__file__).resolve().parents[1]
for rel in ('platform/public/assets/app.css',
            'wordpress-plugin/brendigo-pay/assets/public.css',
            'wordpress-plugin/brendigo-pay/assets/admin.css',
            'wordpress-plugin/brendigo-pay/assets/editor.css'):
    source = (ROOT / rel).read_text(encoding='utf-8')
    # Strip comments, then identify left braces at block boundaries.
    clean = re.sub(r'/\*.*?\*/', '', source, flags=re.S)
    selector_counts = Counter()
    for match in re.finditer(r'(?:^|\})\s*([^{}]+)\s*\{', clean, flags=re.M):
        selector = ' '.join(match.group(1).strip().split())
        if selector and not selector.startswith('@'):
            selector_counts[selector] += 1
    repeated = sorted(((name, count) for name, count in selector_counts.items() if count > 1),
                      key=lambda v: (-v[1], v[0]))
    print(f'{rel}: {len(repeated)} repeated top-level selectors (heuristic)')
    for selector, count in repeated[:12]:
        print(f'  {count}x {selector[:130]}')
print('Review duplicates against visual screenshot QA before deleting.')
