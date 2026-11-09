#!/usr/bin/env python3
"""Non-destructive, heuristic dead-code and source-security inventory.

This scans only checked-in code. WordPress hooks, PHP reflection and runtime
CSS class composition prevent automatic proof that a candidate is unused.
Do not delete candidates solely on this report.
"""
from __future__ import annotations

import argparse
import json
import re
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOTS = ("platform/src", "platform/public", "platform/bin",
                "wordpress-plugin/brendigo-pay", "android/app/src/main")
SOURCE_EXTENSIONS = {".php", ".js", ".css", ".java", ".xml", ".html"}
EXCLUDED = {".git", "build", "gradle", "node_modules", "storage", "tests"}
PHP_DEF = re.compile(r"\bfunction\s+([a-zA-Z_]\w*)\s*\(")
JS_DEF = re.compile(r"\bfunction\s+([a-zA-Z_]\w*)\s*\(")
CSS_CLASS = re.compile(r"\.((?:bp|brendigo|wc)-[A-Za-z_][A-Za-z0-9_-]*)")
RISKY_PHP = re.compile(r"\b(?:eval|shell_exec|passthru|unserialize|system)\s*\(")
RISKY_JS = re.compile(r"\b(?:eval|new\s+Function)\s*\(")
# Required callbacks invoked by PHP interfaces or WordPress/WooCommerce runtime hooks:
# absence of an explicit source call is not dead-code evidence.
FRAMEWORK_CALLBACKS = {
    "platform/src/JsonSessionHandler.php": {"destroy", "validateId", "updateTimestamp", "gc"},
    "wordpress-plugin/brendigo-pay/includes/class-bp-blocks.php": {
        "is_active", "get_payment_method_script_handles", "get_payment_method_data"
    },
    "wordpress-plugin/brendigo-pay/includes/class-bp-gateway.php": {
        "payment_fields", "process_payment"
    },
}
DYNAMIC_CLASSES = {"bp-wc-provider-monogram-paypal", "bp-wc-provider-monogram-revolut"}
PACKAGING_ASSETS = {"app-icon-512.png"}



def get_files() -> list[Path]:
    out = []
    for base in SOURCE_ROOTS:
        for path in (ROOT / base).rglob("*"):
            if path.is_file() and path.suffix.lower() in SOURCE_EXTENSIONS and not EXCLUDED.intersection(path.parts):
                out.append(path)
    return sorted(set(out))


def scan() -> dict:
    files = get_files()
    texts = {str(p.relative_to(ROOT)): p.read_text(encoding="utf-8", errors="replace") for p in files}
    php_source = "\n".join(s for path, s in texts.items() if path.endswith(".php"))
    js_source = "\n".join(s for path, s in texts.items() if path.endswith(".js"))
    runtime_not_css = "\n".join(s for path, s in texts.items() if not path.endswith(".css"))
    css_source = "\n".join(s for path, s in texts.items() if path.endswith(".css"))
    dynamic_hooks = ["add_action(", "add_filter(", "register_rest_route(", "register_block_type(",
                     "add_submenu_page(", "add_shortcode(", "register_payment_method("]
    functions = []
    required_callbacks = []
    for path, source in texts.items():
        if not path.endswith((".php", ".js")):
            continue
        pattern = PHP_DEF if path.endswith(".php") else JS_DEF
        merged = php_source if path.endswith(".php") else js_source
        for match in pattern.finditer(source):
            name = match.group(1)
            if name in FRAMEWORK_CALLBACKS.get(path, set()):
                required_callbacks.append({"path": path, "symbol": name, "reason": "framework_interface"})
                continue
            if name.startswith("__") or name in {"main", "open", "close", "read", "write",
                                                  "register", "render", "initialize", "request"}:
                continue
            occurrences = len(re.findall(r"\b" + re.escape(name) + r"\b", merged))
            if occurrences <= 1:
                functions.append({"path": path, "symbol": name,
                                  "occurrences": occurrences, "classification": "manual-review"})
    class_names = sorted(set(CSS_CLASS.findall(css_source)))
    css_candidates = [name for name in class_names if name not in DYNAMIC_CLASSES and re.search(
        r"\b" + re.escape(name) + r"\b", runtime_not_css) is None]
    assets = []
    for base in ("platform/public/assets", "wordpress-plugin/brendigo-pay/assets"):
        for path in (ROOT / base).rglob("*"):
            if not path.is_file() or path.suffix.lower() not in {".svg", ".png", ".jpg", ".jpeg", ".webp"}:
                continue
            if path.name not in PACKAGING_ASSETS and path.name not in runtime_not_css and path.name not in css_source:
                assets.append(str(path.relative_to(ROOT)))

    warnings = []
    for path, source in texts.items():
        if path.endswith(".php"):
            for match in RISKY_PHP.finditer(source):
                warnings.append({"path": path, "primitive": match.group(0),
                                 "severity": "manual-review", "offset": match.start()})
        elif path.endswith(".js"):
            for match in RISKY_JS.finditer(source):
                warnings.append({"path": path, "primitive": match.group(0),
                                 "severity": "manual-review", "offset": match.start()})
    windows = any(path.startswith(("windows/", "win/", "desktop/")) for path in texts)
    return {
        "generated_utc": datetime.now(timezone.utc).isoformat(),
        "scope": SOURCE_ROOTS,
        "source_files_scanned": len(files),
        "windows_project_found": windows,
        "php_function_candidates": functions,
        "required_framework_callbacks": required_callbacks,
        "packaging_images_excluded": sorted(PACKAGING_ASSETS),
        "dynamic_css_classes_excluded": sorted(DYNAMIC_CLASSES),
        "css_class_candidates": css_candidates,
        "image_asset_candidates": assets,
        "dynamic_hook_patterns_found": [h for h in dynamic_hooks if h in php_source],
        "risky_primitives": warnings,
        "limits": [
            "WordPress and WooCommerce invoke callbacks dynamically",
            "PHP, JS and CSS can generate identifiers at runtime",
            "Screenshots and third-party theme assets are not checked by this report",
            "Static review cannot prove exploitability or absence of vulnerabilities",
            "No deletion occurs automatically",
        ],
    }


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--output-dir", type=Path, help="Optional report output directory")
    args = ap.parse_args()
    report = scan()
    print("SOURCE FILES:", report["source_files_scanned"])
    for key in ("php_function_candidates", "css_class_candidates", "image_asset_candidates", "risky_primitives",
                "required_framework_callbacks"):
        print(key + ":", len(report[key]))
    if args.output_dir:
        args.output_dir.mkdir(parents=True, exist_ok=True)
        (args.output_dir / "dead-code-audit.json").write_text(
            json.dumps(report, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        lines = ["# Brendigo Pay — static code inventory",
                 "", f"Files scanned: **{report['source_files_scanned']}**.",
                 "", "This is an **inventory of candidates**, not deletion approval.",
                 "WordPress dynamic hooks and generated CSS classes require human verification.",
                 "",
                 "| Category | Review candidates |",
                 "|---|---:|"]
        for key in ("php_function_candidates", "css_class_candidates", "image_asset_candidates",
                    "risky_primitives", "required_framework_callbacks"):
            lines.append(f"| {key} | {len(report[key])} |")
        lines += ["", "## Required framework callbacks — protected from deletion", ""]
        lines += [f"- `{e['path']}`: `{e['symbol']}`"
                  for e in report["required_framework_callbacks"]]
        lines += ["", "## Potentially unused PHP/JS definitions", ""]
        lines += [f"- `{e['path']}`: `{e['symbol']}` (review only)"
                  for e in report["php_function_candidates"][:120]]
        lines += ["", "## Potentially unreferenced CSS classes", ""]
        lines += [f"- `.{name}`" for name in report["css_class_candidates"][:120]]
        lines += ["", "## Potentially unreferenced image assets", ""]
        lines += [f"- `{name}`" for name in report["image_asset_candidates"][:120]]
        lines += ["", "## Suspicious runtime primitives (manual triage)", ""]
        lines += [f"- `{e['path']}`: `{e['primitive']}`" for e in report["risky_primitives"]]
        lines += ["", "Run real end-to-end route, WordPress hooks, Android UI, accessibility and API security tests before removing code.", ""]
        (args.output_dir / "dead-code-audit.md").write_text("\n".join(lines), encoding="utf-8")


if __name__ == "__main__":
    main()
