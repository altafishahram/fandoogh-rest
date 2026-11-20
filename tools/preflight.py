#!/usr/bin/env python3
"""Pre-release verification for the Fondogh Online Menu plugin.

Steps:
    1) PHP syntax check on every *.php file
    2) Build the release package
    3) Validate the package contents

Usage:
    python tools/preflight.py                 # full check
    python tools/preflight.py --package-only  # validate an existing package
"""

from __future__ import annotations

import hashlib
import os
import shutil
import subprocess
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SLUG = "fondogh-online-menu"
ZIP_PATH = os.path.join(ROOT, SLUG + ".zip")

REQUIRED_ENTRIES = (
    f"{SLUG}/fondogh-online-menu.php",
    f"{SLUG}/uninstall.php",
    f"{SLUG}/readme.txt",
    f"{SLUG}/includes/Plugin.php",
    f"{SLUG}/includes/Autoloader.php",
)

FORBIDDEN_SUFFIXES = (".py", ".pyc", ".pyo", ".md", ".zip", ".psd", ".ai", ".log")
FORBIDDEN_PARTS = (
    "__pycache__",
    ".git/",
    ".agents/",
    ".claude/",
    "bot-operations/",
    "design-source/",
    ".github/",
)


def fail(message: str) -> None:
    print(f"FAIL: {message}")
    raise SystemExit(1)


def php_files() -> list[str]:
    found: list[str] = []
    skip = {".git", ".agents", ".claude", "__pycache__", "node_modules"}
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in skip]
        for filename in filenames:
            if filename.endswith(".php"):
                found.append(os.path.join(dirpath, filename))
    return sorted(found)


def run(cmd: list[str]) -> subprocess.CompletedProcess:
    return subprocess.run(cmd, capture_output=True, encoding="utf-8", errors="replace")


def lint_php() -> None:
    php = shutil.which("php")
    if not php:
        fail("PHP executable not found; syntax check skipped")

    files = php_files()
    if not files:
        fail("no PHP files found")

    errors: list[str] = []
    for path in files:
        result = run([php, "-l", path])
        if result.returncode != 0:
            output = (result.stdout or "") + (result.stderr or "")
            errors.append(f"{os.path.relpath(path, ROOT)}: {output.strip()}")

    if errors:
        for error in errors:
            print(error)
        fail(f"{len(errors)} PHP file(s) have syntax errors")

    version = (run([php, "-r", "echo PHP_VERSION;"]).stdout or "").strip()
    print(f"OK: {len(files)} PHP files passed syntax check (PHP {version})")


def build() -> None:
    script = os.path.join(ROOT, "tools", "build-release.py")
    result = subprocess.run([sys.executable, script], cwd=ROOT)
    if result.returncode != 0:
        fail("package build failed")


def verify_package() -> None:
    if not os.path.isfile(ZIP_PATH):
        fail("package not found; run the build first")

    with zipfile.ZipFile(ZIP_PATH) as archive:
        names = archive.namelist()
        if not names:
            fail("package is empty")

        bad = [
            name
            for name in names
            if not name.startswith(SLUG + "/")
            or name.lower().endswith(FORBIDDEN_SUFFIXES)
            or any(part in name for part in FORBIDDEN_PARTS)
        ]
        if bad:
            for name in bad:
                print(f"FORBIDDEN: {name}")
            fail("package contains forbidden files")

        missing = [entry for entry in REQUIRED_ENTRIES if entry not in names]
        if missing:
            for entry in missing:
                print(f"MISSING: {entry}")
            fail("package is missing required files")

        corrupt = archive.testzip()
        if corrupt:
            fail(f"corrupt entry in package: {corrupt}")

    with open(ZIP_PATH, "rb") as handle:
        digest = hashlib.sha256(handle.read()).hexdigest()
    with open(ZIP_PATH + ".sha256", "w", encoding="utf-8", newline="\n") as handle:
        handle.write(f"{digest}  {SLUG}.zip\n")

    size = os.path.getsize(ZIP_PATH) / 1024
    print(f"OK: package valid - {len(names)} files, {size:.0f} KB, sha256={digest[:16]}...")


def main() -> int:
    package_only = "--package-only" in sys.argv[1:]

    if not package_only:
        lint_php()
        build()

    verify_package()
    print("PREFLIGHT PASSED")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
