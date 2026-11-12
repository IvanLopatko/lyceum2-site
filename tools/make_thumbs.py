#!/usr/bin/env python3
"""
Зменшені копії наявних фото для списків: uploads/<тека>/a.jpg → uploads/thumbs/<тека>/a.jpg.

Нові фото, додані через адмінку, отримують копію самі (app/admin/upload.php).
Цей скрипт потрібен один раз — для фото, перенесених зі старого сайту й Facebook.

    python3 tools/make_thumbs.py            # лише ті, яких ще немає
    python3 tools/make_thumbs.py --force    # перестворити всі
"""
import os
import sys
from PIL import Image, ImageOps

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'uploads')
FORCE = '--force' in sys.argv
made = skipped = 0
for base, dirs, files in os.walk(ROOT):
    rel = os.path.relpath(base, ROOT)
    if rel.split(os.sep)[0] in ('thumbs', 'docs'):
        continue
    for name in files:
        if not name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')) or name == 'emblema.png':
            continue
        src = os.path.join(base, name)
        dst = os.path.join(ROOT, 'thumbs', rel if rel != '.' else '', name)
        if os.path.exists(dst) and not FORCE:
            skipped += 1
            continue
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        size = 400 if rel.startswith('staff') else 720
        try:
            im = ImageOps.exif_transpose(Image.open(src)).convert('RGB')
            im.thumbnail((size, size), Image.LANCZOS)
            im.save(dst, 'JPEG', quality=78, optimize=True, progressive=True)
            made += 1
        except Exception as ex:  # пошкоджений файл — пропускаємо
            print('пропущено', src, ex)
print(f'створено: {made}, уже були: {skipped}')
