"""Check tracked publication inputs without printing potential credential values."""
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
tracked = subprocess.check_output(
    ["git", "ls-files", "-z"], cwd=ROOT
).decode("utf-8").split("\0")
forbidden = re.compile(
    r"(^|/)(?:\.tools|\.git|node_modules|vendor|dist|qa|runtime|__pycache__)(/|$)"
    r"|(^|/)(?:\.env(?:\..*)?|auth\.json|\.npmrc|wp-config[^/]*\.php)$"
    r"|\.(?:sqlite3?|db|sql|sql\.gz|pem|key|p12|pfx|zip|log)$",
    re.IGNORECASE,
)
credential_patterns = [
    re.compile(r"\bgh[opusr]_[A-Za-z0-9]{30,}\b"),
    re.compile(r"\bgithub_pat_[A-Za-z0-9_]{30,}\b"),
    re.compile(r"\bAIza[A-Za-z0-9_-]{35}\b"),
    re.compile(r"\bsk-[A-Za-z0-9]{32,}\b"),
    re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    re.compile(r"Local-Test-Only-[0-9a-f]{6,}"),
]
failures = []
checked = 0
for relative in filter(None, tracked):
    if relative == ".env.example":
        pass
    elif forbidden.search(relative) or relative.startswith("assets/"):
        failures.append(f"Forbidden tracked path: {relative}")
        continue
    path = ROOT / relative
    if not path.is_file():
        failures.append(f"Missing tracked file: {relative}")
        continue
    if path.is_symlink():
        failures.append(f"Symbolic link requires explicit publication review: {relative}")
        continue
    data = path.read_bytes()
    if len(data) > 50 * 1024 * 1024:
        failures.append(f"Large tracked file: {relative}")
    try:
        content = data.decode("utf-8")
    except UnicodeDecodeError:
        checked += 1
        continue  # Official translation binaries are separately attributed and packaged.
    for pattern in credential_patterns:
        match = pattern.search(content)
        if match:
            line = content.count("\n", 0, match.start()) + 1
            failures.append(f"Potential credential in {relative}:{line}; value withheld")
            break
    checked += 1

if failures:
    print("\n".join(failures), file=sys.stderr)
    raise SystemExit(1)
print(f"Repository publication audit passed: {checked} tracked files checked.")
