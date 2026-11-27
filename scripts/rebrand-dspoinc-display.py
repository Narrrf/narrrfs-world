#!/usr/bin/env python3
"""
Rebrand standalone public display occurrences of `DSPOINC` -> `dSPOINC` across
Narrrfs World public pages and frontend scripts (including `public/three.js/`).

DEVS FOR DECADES:
- Only standalone word-boundary tokens `(?<![A-Za-z0-9_])DSPOINC(?![A-Za-z0-9_])`
  are replaced, so code/DOM/route identifiers (`DSPOINC_PER_SPOINC`,
  `SPOINC_TO_DSPOINC`, `totalDSPOINC`, `cheesemanDSPOINCScores`, etc.) remain intact.
- Legacy admin pages (`public/admin/*`, `public/admin-interface.html`) and all
  backend/DB/API files (`api/`, `db/`, `owner-control/`) are excluded.
- Reads and writes raw UTF-8 bytes without altering CRLF/LF line endings.
"""

from __future__ import annotations

import argparse
import pathlib
import re
import sys

TARGET_EXTENSIONS = {".html", ".js", ".css", ".json"}
TOKEN_PATTERN = re.compile(r"(?<![A-Za-z0-9_])DSPOINC(?![A-Za-z0-9_])")


def is_excluded(rel_posix: str) -> bool:
    """Return True for legacy admin pages that must remain outside public rebrand scope."""
    return rel_posix == "admin-interface.html" or rel_posix.startswith("admin/")


def main() -> int:
    repo_root = pathlib.Path(__file__).resolve().parents[1]
    parser = argparse.ArgumentParser(
        description="Rebrand standalone public DSPOINC display text to dSPOINC."
    )
    parser.add_argument(
        "--root",
        type=pathlib.Path,
        default=repo_root / "public",
        help="Target public directory (default: <repo>/public)",
    )
    parser.add_argument(
        "--apply",
        action="store_true",
        help="Write changes to disk (omit for dry-run preview)",
    )
    args = parser.parse_args()

    public_root: pathlib.Path = args.root.resolve()
    if not public_root.is_dir():
        print(f"ERROR: Target directory does not exist: {public_root}", file=sys.stderr)
        return 1

    changed_files: list[tuple[str, int]] = []
    total_replacements = 0

    for path in sorted(public_root.rglob("*")):
        if not path.is_file() or path.suffix.lower() not in TARGET_EXTENSIONS:
            continue
        rel_posix = path.relative_to(public_root).as_posix()
        if is_excluded(rel_posix):
            continue

        raw_bytes = path.read_bytes()
        try:
            text = raw_bytes.decode("utf-8")
        except UnicodeDecodeError:
            text = raw_bytes.decode("utf-8", errors="ignore")

        updated_text, count = TOKEN_PATTERN.subn("dSPOINC", text)
        if count > 0:
            changed_files.append((rel_posix, count))
            total_replacements += count
            if args.apply:
                path.write_bytes(updated_text.encode("utf-8"))

    mode = "APPLIED" if args.apply else "DRY-RUN"
    print(f"[{mode}] {total_replacements} standalone DSPOINC -> dSPOINC replacements across {len(changed_files)} files")
    for rel_posix, count in changed_files:
        print(f"  {count:4d}  public/{rel_posix}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
