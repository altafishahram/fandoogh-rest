#!/usr/bin/env python3
"""ساخت بستهٔ انتشار افزونهٔ منو آنلاین فندق.

این اسکریپت فقط مسیرهای مجاز را داخل ZIP می‌گذارد تا فایل‌های توسعه،
مستندات داخلی و مهارت‌ها وارد بستهٔ نصب نشوند.

استفاده:
    python tools/build-release.py
"""

from __future__ import annotations

import os
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SLUG = "fondogh-online-menu"
ZIP_PATH = os.path.join(ROOT, SLUG + ".zip")

# فایل‌هایی که در ریشهٔ افزونه در بستهٔ نصب قرار می‌گیرند.
ROOT_FILES = (
    "fondogh-online-menu.php",
    "uninstall.php",
    "readme.txt",
)

# پوشه‌هایی که کامل و بدون استثنا در بسته قرار می‌گیرند.
INCLUDE_DIRS = (
    "includes",
    os.path.join("assets", "css"),
    os.path.join("assets", "js"),
    os.path.join("assets", "templates-json"),
)

# پسوندهای غیرمجاز، حتی داخل پوشه‌های مجاز.
BLOCKED_SUFFIXES = (".pyc", ".pyo", ".md", ".zip", ".psd", ".ai")
BLOCKED_NAMES = {".DS_Store", "Thumbs.db", ".gitkeep"}


def collect() -> list[str]:
    files: list[str] = []

    for name in ROOT_FILES:
        path = os.path.join(ROOT, name)
        if not os.path.isfile(path):
            sys.exit(f"فایل الزامی پیدا نشد: {name}")

    for directory in INCLUDE_DIRS:
        base = os.path.join(ROOT, directory)
        if not os.path.isdir(base):
            sys.exit(f"پوشهٔ الزامی پیدا نشد: {directory}")
        for dirpath, dirnames, filenames in os.walk(base):
            dirnames[:] = sorted(d for d in dirnames if not d.startswith("."))
            for filename in sorted(filenames):
                if filename in BLOCKED_NAMES:
                    continue
                if filename.lower().endswith(BLOCKED_SUFFIXES):
                    continue
                files.append(os.path.relpath(os.path.join(dirpath, filename), ROOT))

    files.extend(ROOT_FILES)
    return sorted(files)


def main() -> int:
    files = collect()

    with zipfile.ZipFile(ZIP_PATH, "w", zipfile.ZIP_DEFLATED) as archive:
        for relative in files:
            archive.write(os.path.join(ROOT, relative), f"{SLUG}/{relative.replace(os.sep, '/')}")

    size = os.path.getsize(ZIP_PATH) / 1024
    print(f"OK: {len(files)} files -> {os.path.basename(ZIP_PATH)} ({size:.0f} KB)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
