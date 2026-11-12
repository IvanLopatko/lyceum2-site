#!/usr/bin/env python3
"""Збирає чистий архів сайту для завантаження на хостинг.

    python3 tools/pack_for_hosting.py            → dist/lyceum-site-РРРРММДД.zip

В архів НЕ потрапляють: локальні облікові записи й паролі (config/, крім .htaccess і README.md),
тека .git, статична копія docs/, робочі скрипти tools/, історія правок і кошик, службові файли macOS.
"""
import datetime
import pathlib
import sys
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent
SKIP_TOP = {".git", ".github", "docs", "tools", "dist", "node_modules", ".claude"}
KEEP_IN_CONFIG = {".htaccess", "README.md"}
SKIP_NAMES = {".DS_Store", "Thumbs.db"}


def wanted(rel: pathlib.PurePosixPath) -> bool:
    parts = rel.parts
    if parts[0] in SKIP_TOP or rel.name in SKIP_NAMES:
        return False
    if parts[0] == "config":
        return len(parts) == 2 and rel.name in KEEP_IN_CONFIG
    if parts[0] == "content" and len(parts) > 1 and parts[1] in {".history", ".trash", ".write.lock"}:
        return False
    if rel.suffix in {".lock", ".log"} or ".tmp" in rel.name:
        return False
    return True


def main() -> int:
    out_dir = ROOT / "dist"
    out_dir.mkdir(exist_ok=True)
    out = out_dir / f"lyceum-site-{datetime.date.today():%Y%m%d}.zip"
    count = size = 0
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as z:
        for f in sorted(ROOT.rglob("*")):
            if not f.is_file() or f.is_symlink():
                continue
            rel = pathlib.PurePosixPath(f.relative_to(ROOT).as_posix())
            if not wanted(rel):
                continue
            # фото й PDF уже стиснуті — кладемо як є, так швидше
            method = zipfile.ZIP_STORED if rel.suffix.lower() in {".jpg", ".jpeg", ".png", ".webp", ".pdf", ".woff2"} else zipfile.ZIP_DEFLATED
            z.write(f, rel.as_posix(), compress_type=method)
            count += 1
            size += f.stat().st_size
    with zipfile.ZipFile(out) as z:
        names = z.namelist()
    leaked = [n for n in names if n.startswith(("config/users", "config/local-", "config/sessions", "config/login-", "config/setup-", ".git/", "tools/", "docs/"))]
    if leaked:
        out.unlink()
        print("Помилка: в архів потрапили службові файли:", leaked[:5], file=sys.stderr)
        return 1
    print(f"Готово: {out}")
    print(f"Файлів: {count}, розмір архіву: {out.stat().st_size / 1048576:.0f} МБ (вміст — {size / 1048576:.0f} МБ)")
    print("Розпакуйте його в корінь сайту на хостингу й відкрийте https://…/admin/ — сайт попросить код із config/setup-token.php.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
