/*
 * Адмінка: блоковий редактор сторінок, текстовий редактор новин, підтвердження видалення.
 * Дані блоку зберігаються в прихованому полі як JSON; сервер ще раз очищає все при збереженні.
 */
(function () {
  'use strict';
  var d = document;

  /* підтвердження небезпечних дій */
  d.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.dataset.confirm)) { e.preventDefault(); } });
  });
  /* незбережені зміни: попередити, якщо людина закриває чи покидає сторінку з правками */
  var dirty = false;
  function markDirty() { dirty = true; }
  d.querySelectorAll('form.edit').forEach(function (f) {
    f.addEventListener('input', markDirty);
    f.addEventListener('change', markDirty);
    f.addEventListener('submit', function () { dirty = false; });
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });

  /* файли: не дати вибрати більше, ніж приймає сервер, — інакше зайві мовчки зникнуть */
  function mb(n) { return Math.round(n / 104857.6) / 10; }
  d.querySelectorAll('input[type=file]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var maxFiles = parseInt(inp.dataset.maxFiles || '0', 10);
      var maxBytes = parseInt(inp.dataset.maxBytes || '0', 10);
      var total = 0;
      Array.prototype.forEach.call(inp.files, function (f) { total += f.size; });
      var msg = '';
      if (maxFiles && inp.files.length > maxFiles) {
        msg = 'Ви вибрали ' + inp.files.length + ' файлів, а за один раз сервер приймає до ' + maxFiles + '. Виберіть до ' + maxFiles + ', збережіть і додайте решту наступним збереженням.';
      } else if (maxBytes && total > maxBytes * 0.95) {
        msg = 'Вибрані файли разом важать ' + mb(total) + ' МБ, а за один раз сервер приймає до ' + mb(maxBytes) + ' МБ. Виберіть менше файлів або зменште їх.';
      }
      if (msg) { window.alert(msg); inp.value = ''; }
    });
  });

  /* чи закриті службові теки з інтернету (на справжньому хостингу, не на власному комп'ютері) */
  var ex = d.getElementById('exposure');
  if (ex && window.fetch && !/^(localhost|127\.0\.0\.1|\[::1\])$/.test(location.hostname) && !/\.(localhost|test)$/.test(location.hostname)) {
    var urls = [];
    try { urls = JSON.parse(ex.dataset.check || '[]'); } catch (e) { urls = []; }
    urls.forEach(function (u) {
      fetch(u, { cache: 'no-store', credentials: 'omit' }).then(function (r) {
        if (r.ok) { ex.hidden = false; }
      }).catch(function () {});
    });
  }

  /* фільтри, що відправляються одразу */
  d.querySelectorAll('.filters select').forEach(function (s) {
    s.addEventListener('change', function () { if (!s.form.querySelector('input[type=search]')) { s.form.submit(); } });
  });

  /* ── маленький «багатий» текст: жирний, курсив, посилання ── */
  function exec(cmd) {
    if (cmd === 'link') {
      var u = window.prompt('Адреса посилання (наприклад, /kontakty/ або https://…)');
      if (u) { d.execCommand('createLink', false, u.trim()); }
      return;
    }
    d.execCommand(cmd, false, null);
  }
  function toolbar() {
    var bar = el('div', 'rich-bar');
    bar.setAttribute('role', 'toolbar');
    [['bold', '<b>Ж</b>', 'Жирний'], ['italic', '<i>К</i>', 'Курсив'], ['link', 'Посилання', 'Посилання']].forEach(function (b) {
      var x = el('button');
      x.type = 'button';
      x.innerHTML = b[1];
      x.title = b[2];
      x.addEventListener('mousedown', function (e) { e.preventDefault(); exec(b[0]); });
      bar.appendChild(x);
    });
    return bar;
  }
  function el(tag, cls, text) {
    var n = d.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }
  /* при вставці — лише простий текст, без чужих стилів */
  function plainPaste(node) {
    node.addEventListener('paste', function (e) {
      e.preventDefault();
      var t = (e.clipboardData || window.clipboardData).getData('text/plain');
      d.execCommand('insertText', false, t);
    });
  }

  /* редактор тексту новини */
  var nt = d.getElementById('news-text');
  if (nt) {
    plainPaste(nt);
    var wrap = nt.closest('.rich-wrap');
    wrap.querySelectorAll('[data-cmd]').forEach(function (b) {
      b.addEventListener('mousedown', function (e) { e.preventDefault(); exec(b.dataset.cmd); });
    });
    d.getElementById('news-form').addEventListener('submit', function () {
      var out = [];
      var cur = '';
      Array.prototype.forEach.call(nt.childNodes, function (n) {
        if (n.nodeType === 1 && /^(P|DIV|H\d|LI|BLOCKQUOTE)$/.test(n.tagName)) {
          if (cur.trim()) { out.push(cur); cur = ''; }
          if (n.innerHTML.replace(/<br\s*\/?>/g, '').trim()) { out.push(n.innerHTML); }
        } else if (n.nodeType === 1 && n.tagName === 'BR') {
          if (cur.trim()) { out.push(cur); } cur = '';
        } else {
          cur += n.nodeType === 3 ? n.textContent.replace(/[<>&]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]; }) : n.outerHTML;
        }
      });
      if (cur.trim()) { out.push(cur); }
      d.getElementById('text-json').value = JSON.stringify(out);
    });
  }

  /* ── блоковий редактор сторінок ── */
  var host = d.querySelector('[data-editor]');
  if (!host) { return; }
  var cfg = JSON.parse(d.getElementById('editor-config').textContent);
  var store = d.getElementById('blocks-json');
  var model = [];
  try { model = JSON.parse(store.value) || []; } catch (e) { model = []; }
  if (typeof model === 'string') { try { model = JSON.parse(model); } catch (e2) { model = []; } }
  if (!Array.isArray(model)) { model = []; }

  var TEMPLATES = {
    p: function () { return { type: 'p', text: '' }; },
    h: function () { return { type: 'h', level: 2, text: '' }; },
    list: function () { return { type: 'list', style: 'bullets', items: [''] }; },
    timeline: function () { return { type: 'timeline', items: [{ label: '', text: '' }] }; },
    cards: function () { return { type: 'cards', items: [{ icon: 'doc', title: '', text: '' }] }; },
    stats: function () { return { type: 'stats', items: [{ n: '', label: '', note: '' }] }; },
    faq: function () { return { type: 'faq', items: [{ q: '', a: '' }] }; },
    table: function () { return { type: 'table', head: ['', ''], rows: [['', '']] }; },
    callout: function () { return { type: 'callout', tone: 'blue', title: '', text: '' }; },
    buttons: function () { return { type: 'buttons', items: [{ label: '', url: '/' }] }; },
    quote: function () { return { type: 'quote', text: '', cite: '' }; },
    compare: function () { return { type: 'compare', items: [{ label: 'Зараз', title: '', points: [] }, { label: 'Буде', title: '', points: [] }] }; },
    myths: function () { return { type: 'myths', items: [{ myth: '', fact: '' }] }; },
    gallery: function () { return { type: 'gallery', images: [] }; },
    component: function () { return { type: 'component', name: 'documents', args: {} }; },
    html: function () { return { type: 'html', html: '' }; }
  };

  var synced = false;
  function sync() {
    store.value = JSON.stringify(model);
    if (synced) { dirty = true; }   // перша синхронізація — це просто початковий стан
    synced = true;
  }

  /* поля */
  function field(label, node, wide) {
    var l = el('label', 'f' + (wide ? ' wide' : ''));
    l.appendChild(el('span', 'f-l', label));
    l.appendChild(node);
    return l;
  }
  function input(get, set, ph) {
    var i = el('input');
    i.value = get() || '';
    if (ph) { i.placeholder = ph; }
    i.addEventListener('input', function () { set(i.value); sync(); });
    return i;
  }
  function area(get, set, rows, ph) {
    var t = el('textarea');
    t.rows = rows || 3;
    t.value = get() || '';
    if (ph) { t.placeholder = ph; }
    t.addEventListener('input', function () { set(t.value); sync(); });
    return t;
  }
  function select(opts, get, set) {
    var s = el('select');
    opts.forEach(function (o) {
      var op = el('option', '', o[1]);
      op.value = o[0];
      if (String(get()) === String(o[0])) { op.selected = true; }
      s.appendChild(op);
    });
    s.addEventListener('change', function () { set(s.value); sync(); });
    return s;
  }
  function rich(get, set, ph) {
    var w = el('div', 'rich-wrap');
    w.appendChild(toolbar());
    var r = el('div', 'rich');
    r.contentEditable = 'true';
    r.innerHTML = get() || '';
    if (ph) { r.dataset.ph = ph; }
    plainPaste(r);
    r.addEventListener('input', function () { set(r.innerHTML.replace(/<div>/g, '<br>').replace(/<\/div>/g, '')); sync(); });
    w.appendChild(r);
    return w;
  }
  /* повторювані рядки (пункти списку, картки, питання…) */
  function repeater(list, make, render) {
    var box = el('div', 'rep');
    function draw() {
      box.innerHTML = '';
      list.forEach(function (item, i) {
        var row = el('div', 'rep-row');
        var body = el('div', 'rep-body');
        render(body, item, i);
        var tools = el('div', 'rep-tools');
        tools.appendChild(btn('↑', 'Вище', function () { if (i > 0) { list.splice(i - 1, 0, list.splice(i, 1)[0]); sync(); draw(); } }));
        tools.appendChild(btn('↓', 'Нижче', function () { if (i < list.length - 1) { list.splice(i + 1, 0, list.splice(i, 1)[0]); sync(); draw(); } }));
        tools.appendChild(btn('✕', 'Прибрати', function () { list.splice(i, 1); sync(); draw(); }));
        row.appendChild(body);
        row.appendChild(tools);
        box.appendChild(row);
      });
      var add = btn('+ Додати', '', function () { list.push(make()); sync(); draw(); });
      add.className = 'btn-add';
      box.appendChild(add);
    }
    draw();
    return box;
  }
  function btn(text, title, fn) {
    var b = el('button', 'ib', text);
    b.type = 'button';
    if (title) { b.title = title; b.setAttribute('aria-label', title); }
    b.addEventListener('click', fn);
    return b;
  }
  function lines(arr) { return (arr || []).join('\n'); }
  function splitLines(v) { return v.split('\n').map(function (s) { return s.trim(); }).filter(Boolean); }

  var icons = cfg.icons.map(function (i) { return [i, i]; });

  function fields(box, b) {
    switch (b.type) {
      case 'p':
        box.appendChild(field('Текст', rich(function () { return b.text; }, function (v) { b.text = v; }), true));
        box.appendChild(field('Вигляд', select([['', 'Звичайний абзац'], ['note', 'Примітка (дрібніше, сіре)'], ['lead', 'Вступ (більший шрифт)']], function () { return b.style || ''; }, function (v) { if (v) { b.style = v; } else { delete b.style; } })));
        break;
      case 'h':
        box.appendChild(field('Текст заголовка', input(function () { return b.text; }, function (v) { b.text = v; }), true));
        box.appendChild(field('Рівень', select([[2, 'Великий (розділ)'], [3, 'Менший (підрозділ)']], function () { return b.level || 2; }, function (v) { b.level = Number(v); })));
        box.appendChild(field('Якір для змісту (латиницею, необов’язково)', input(function () { return b.anchor; }, function (v) { b.anchor = v; }, 'shcho-zminytsia')));
        break;
      case 'list':
        box.appendChild(field('Вигляд', select([['bullets', 'Маркери-вітрила'], ['steps', 'Нумеровані кроки']], function () { return b.style; }, function (v) { b.style = v; })));
        box.appendChild(field('Пункти', repeater(b.items, function () { return ''; }, function (row, it, i) {
          row.appendChild(rich(function () { return b.items[i]; }, function (v) { b.items[i] = v; }));
        }), true));
        break;
      case 'timeline':
        box.appendChild(field('Події', repeater(b.items, function () { return { label: '', text: '' }; }, function (row, it) {
          row.appendChild(field('Коли', input(function () { return it.label; }, function (v) { it.label = v; })));
          row.appendChild(field('Що', rich(function () { return it.text; }, function (v) { it.text = v; }), true));
        }), true));
        break;
      case 'cards':
        box.appendChild(field('Картки', repeater(b.items, function () { return { icon: 'doc', title: '', text: '' }; }, function (row, it) {
          row.appendChild(field('Значок', select(icons, function () { return it.icon; }, function (v) { it.icon = v; })));
          row.appendChild(field('Заголовок', input(function () { return it.title; }, function (v) { it.title = v; })));
          row.appendChild(field('Текст', rich(function () { return it.text; }, function (v) { it.text = v; }), true));
        }), true));
        break;
      case 'stats':
        box.appendChild(field('Цифри', repeater(b.items, function () { return { n: '', label: '', note: '' }; }, function (row, it) {
          row.appendChild(field('Число', input(function () { return it.n; }, function (v) { it.n = v; })));
          row.appendChild(field('Підпис', input(function () { return it.label; }, function (v) { it.label = v; })));
          row.appendChild(field('Уточнення', input(function () { return it.note; }, function (v) { it.note = v; })));
        }), true));
        break;
      case 'faq':
        box.appendChild(field('Питання', repeater(b.items, function () { return { q: '', a: '' }; }, function (row, it) {
          row.appendChild(field('Питання', input(function () { return it.q; }, function (v) { it.q = v; }), true));
          row.appendChild(field('Відповідь', rich(function () { return it.a; }, function (v) { it.a = v; }), true));
        }), true));
        break;
      case 'table':
        box.appendChild(field('Назви стовпців (через | )', input(function () { return (b.head || []).join(' | '); }, function (v) { b.head = v.split('|').map(function (s) { return s.trim(); }); }), true));
        box.appendChild(field('Рядки: кожен рядок таблиці — з нового рядка, клітинки через | ', area(function () { return (b.rows || []).map(function (r) { return r.join(' | '); }).join('\n'); }, function (v) { b.rows = splitLines(v).map(function (r) { return r.split('|').map(function (s) { return s.trim(); }); }); }, 6), true));
        break;
      case 'callout':
        box.appendChild(field('Вигляд', select([['blue', 'Інформація (синій)'], ['sun', 'Важливо (сонячний)'], ['green', 'Добре, готово (зелений)'], ['sail', 'Тривога, безпека (червоний)']], function () { return b.tone; }, function (v) { b.tone = v; })));
        box.appendChild(field('Заголовок (необов’язково)', input(function () { return b.title; }, function (v) { b.title = v; })));
        box.appendChild(field('Текст', rich(function () { return b.text; }, function (v) { b.text = v; }), true));
        break;
      case 'buttons':
        box.appendChild(field('Кнопки', repeater(b.items, function () { return { label: '', url: '/' }; }, function (row, it) {
          row.appendChild(field('Напис', input(function () { return it.label; }, function (v) { it.label = v; })));
          row.appendChild(field('Куди веде', input(function () { return it.url; }, function (v) { it.url = v; }, '/kontakty/ або https://…')));
        }), true));
        break;
      case 'quote':
        box.appendChild(field('Цитата', rich(function () { return b.text; }, function (v) { b.text = v; }), true));
        box.appendChild(field('Джерело', input(function () { return b.cite; }, function (v) { b.cite = v; })));
        break;
      case 'compare':
        (b.items || []).forEach(function (it, i) {
          var g = el('fieldset', 'grp');
          g.appendChild(el('legend', '', i ? 'Права колонка' : 'Ліва колонка'));
          g.appendChild(field('Надпис', input(function () { return it.label; }, function (v) { it.label = v; })));
          g.appendChild(field('Заголовок', input(function () { return it.title; }, function (v) { it.title = v; })));
          g.appendChild(field('Пункти (кожен з нового рядка)', area(function () { return lines(it.points); }, function (v) { it.points = splitLines(v); }, 4), true));
          box.appendChild(g);
        });
        break;
      case 'myths':
        box.appendChild(field('Пари', repeater(b.items, function () { return { myth: '', fact: '' }; }, function (row, it) {
          row.appendChild(field('Міф', input(function () { return it.myth; }, function (v) { it.myth = v; }), true));
          row.appendChild(field('Факт', rich(function () { return it.fact; }, function (v) { it.fact = v; }), true));
        }), true));
        break;
      case 'gallery':
        var g = el('div', 'thumbs');
        (b.images || []).forEach(function (src, i) {
          var f = el('figure');
          var im = el('img');
          im.src = (document.body.dataset.base || '') + '/' + src;
          f.appendChild(im);
          f.appendChild(btn('✕', 'Прибрати', function () { b.images.splice(i, 1); sync(); f.remove(); }));
          g.appendChild(f);
        });
        box.appendChild(g);
        box.appendChild(el('p', 'muted small', 'Фото додаються через новини; тут можна лише прибрати зайві.'));
        break;
      case 'component':
        var argsBox = el('div', 'fields');
        var drawArgs = function () {
          argsBox.innerHTML = '';
          if (!b.args || Array.isArray(b.args)) { b.args = {}; }
          var a = b.args;
          var opt = function (list, empty) { return [['', empty]].concat(list.map(function (t) { return [t.slug, t.name]; })); };
          if (b.name === 'documents') {
            argsBox.appendChild(field('Розділ реєстру', select(opt(cfg.docSections, 'усі розділи'), function () { return a.section || ''; }, function (v) { a.section = v; })));
            argsBox.appendChild(field('Скільки показати (порожньо — усі)', input(function () { return a.limit; }, function (v) { a.limit = v; })));
          } else if (b.name === 'team') {
            argsBox.appendChild(field('Група', select(opt(cfg.staffGroups, 'усі'), function () { return a.group || ''; }, function (v) { a.group = v; })));
            argsBox.appendChild(field('Лише з предметом (слово)', input(function () { return a.subjects; }, function (v) { a.subjects = v; }, 'початков')));
          } else if (b.name === 'posts') {
            argsBox.appendChild(field('Рубрика', select(opt(cfg.newsCats, 'усі новини'), function () { return a.cat || ''; }, function (v) { a.cat = v; })));
            argsBox.appendChild(field('Скільки', input(function () { return a.limit; }, function (v) { a.limit = v; }, '6')));
            argsBox.appendChild(field('Текст, якщо порожньо', input(function () { return a.empty; }, function (v) { a.empty = v; })));
          } else if (b.name === 'club_list') {
            argsBox.appendChild(field('Скільки (порожньо — усі)', input(function () { return a.limit; }, function (v) { a.limit = v; })));
          } else {
            argsBox.appendChild(el('p', 'muted small', 'Цей блок заповнюється автоматично з даних сайту.'));
          }
        };
        box.appendChild(field('Що показати', select(Object.keys(cfg.components).map(function (k) { return [k, cfg.components[k]]; }), function () { return b.name; }, function (v) { b.name = v; b.args = {}; drawArgs(); })));
        box.appendChild(argsBox);
        drawArgs();
        break;
      case 'html':
        var t = area(function () { return b.html; }, function (v) { b.html = v; }, 6);
        t.className = 'code';
        if (!cfg.canHtml) { t.readOnly = true; }
        box.appendChild(field('HTML-код', t, true));
        box.appendChild(el('p', 'muted small', cfg.canHtml
          ? 'Скрипти (<script>) та обробники на кшталт onclick на сайті не виконуються: так браузер захищає відвідувачів. Відео, форми й карти вставляйте через <iframe> з YouTube, Google, Facebook або OpenStreetMap.'
          : 'Цей блок може змінювати лише адміністратор сайту.'));
        break;
    }
  }

  function summary(b) {
    var t = b.text || (b.items && b.items[0] && (b.items[0].title || b.items[0].q || b.items[0].label || b.items[0].myth || b.items[0])) || '';
    if (b.type === 'component') { t = cfg.components[b.name] || b.name; }
    t = String(typeof t === 'string' ? t : '').replace(/<[^>]+>/g, '');
    return t.length > 70 ? t.slice(0, 70) + '…' : t;
  }

  function render() {
    host.innerHTML = '';
    model.forEach(function (b, i) {
      var card = el('section', 'blk' + (b._open ? ' is-open' : ''));
      var head = el('div', 'blk-head');
      var title = el('button', 'blk-title');
      title.type = 'button';
      title.innerHTML = '<b></b><span></span>';
      title.querySelector('b').textContent = cfg.types[b.type] || b.type;
      title.querySelector('span').textContent = summary(b);
      title.setAttribute('aria-expanded', b._open ? 'true' : 'false');
      title.addEventListener('click', function () { b._open = !b._open; render(); });
      head.appendChild(title);
      head.appendChild(btn('↑', 'Перемістити вище', function () { if (i > 0) { model.splice(i - 1, 0, model.splice(i, 1)[0]); sync(); render(); } }));
      head.appendChild(btn('↓', 'Перемістити нижче', function () { if (i < model.length - 1) { model.splice(i + 1, 0, model.splice(i, 1)[0]); sync(); render(); } }));
      head.appendChild(btn('✕', 'Видалити блок', function () { if (window.confirm('Видалити цей блок?')) { model.splice(i, 1); sync(); render(); } }));
      card.appendChild(head);
      if (b._open) {
        var body = el('div', 'blk-body');
        fields(body, b);
        card.appendChild(body);
      }
      host.appendChild(card);
    });
    var add = el('div', 'blk-add');
    var sel = select(Object.keys(cfg.types).filter(function (k) { return k !== 'html' || cfg.canHtml; }).map(function (k) { return [k, cfg.types[k]]; }), function () { return 'p'; }, function () {});
    add.appendChild(sel);
    add.appendChild(btn('+ Додати блок', '', function () {
      var nb = TEMPLATES[sel.value]();
      nb._open = true;
      model.push(nb);
      sync();
      render();
    }));
    host.appendChild(add);
    sync();
  }
  render();

  /* службову позначку «розгорнуто» не зберігаємо */
  store.form.addEventListener('submit', function () {
    store.value = JSON.stringify(model, function (k, v) { return k === '_open' ? undefined : v; });
  });
})();
