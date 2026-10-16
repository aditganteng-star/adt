/* =====================================================================
   Hakuna Matata Course — JavaScript umum (semua halaman)
   ===================================================================== */
(function () {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  /* ---------- Toast ---------- */
  window.toast = function (message, type) {
    const region = $('#toastRegion');
    if (!region) return alert(message);
    const el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : '');
    el.textContent = message;
    region.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, 3200);
    setTimeout(() => el.remove(), 3600);
  };

  /* ---------- Fetch POST dengan CSRF ---------- */
  window.postForm = async function (url, data) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': (window.HAKUNA || {}).csrf || '', 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
      credentials: 'same-origin',
    });
    let json = {};
    try { json = await res.json(); } catch (e) { json = { ok: false, error: 'Respons server tidak valid.' }; }
    return json;
  };

  /* ---------- Header: bayangan saat scroll ---------- */
  const header = $('#siteHeader');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Menu mobile & dropdown ---------- */
  const navToggle = $('#navToggle');
  const mainNav = $('#mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      const open = mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
    });
    $$('a', mainNav).forEach((a) => a.addEventListener('click', () => {
      mainNav.classList.remove('open'); navToggle.setAttribute('aria-expanded', 'false'); document.body.style.overflow = '';
    }));
  }
  $$('.nav-dropdown').forEach((dd) => {
    const btn = $('.nav-dropdown-btn', dd);
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = dd.classList.toggle('open');
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  document.addEventListener('click', (e) => {
    $$('.nav-dropdown.open').forEach((dd) => { if (!dd.contains(e.target)) dd.classList.remove('open'); });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      $$('.nav-dropdown.open').forEach((dd) => dd.classList.remove('open'));
      $$('.modal-backdrop.open').forEach(closeModal);
    }
  });

  /* ---------- Animasi muncul saat scroll ---------- */
  const revealEls = $$('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) {
          en.target.style.transitionDelay = (Math.min(Array.from(en.target.parentNode.children).indexOf(en.target), 6) * 70) + 'ms';
          en.target.classList.add('in');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in'));
  }

  /* ---------- Hitung naik (count-up) ---------- */
  const counters = $$('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    const co = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        const el = en.target, target = parseInt(el.dataset.count, 10) || 0, dur = 1400, t0 = performance.now();
        const step = (t) => {
          const p = Math.min((t - t0) / dur, 1);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        co.unobserve(el);
      });
    }, { threshold: 0.4 });
    counters.forEach((c) => co.observe(c));
  } else {
    counters.forEach((c) => { c.textContent = c.dataset.count; });
  }

  /* ---------- Tab ---------- */
  $$('[data-tabs]').forEach((group) => {
    const tabs = $$('.tab', group);
    tabs.forEach((tab) => tab.addEventListener('click', () => {
      tabs.forEach((t) => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
      tab.classList.add('active'); tab.setAttribute('aria-selected', 'true');
      tabs.forEach((t) => { const p = document.getElementById(t.dataset.tab); if (p) p.classList.remove('active'); });
      const panel = document.getElementById(tab.dataset.tab);
      if (panel) panel.classList.add('active');
    }));
  });

  /* ---------- Carousel ---------- */
  $$('[data-carousel-prev], [data-carousel-next]').forEach((btn) => {
    const id = btn.dataset.carouselPrev || btn.dataset.carouselNext;
    const dir = btn.hasAttribute('data-carousel-prev') ? -1 : 1;
    btn.addEventListener('click', () => {
      const c = document.getElementById(id);
      if (c) c.scrollBy({ left: dir * (c.firstElementChild ? c.firstElementChild.offsetWidth + 20 : 300), behavior: 'smooth' });
    });
  });

  /* ---------- Accordion ---------- */
  $$('.acc-item').forEach((item) => {
    const head = $('.acc-head', item), body = $('.acc-body', item);
    head.addEventListener('click', () => {
      const open = item.classList.contains('open');
      $$('.acc-item.open').forEach((o) => { if (o !== item) { o.classList.remove('open'); $('.acc-body', o).style.maxHeight = null; $('.acc-head', o).setAttribute('aria-expanded', 'false'); } });
      item.classList.toggle('open', !open);
      head.setAttribute('aria-expanded', String(!open));
      body.style.maxHeight = !open ? body.scrollHeight + 'px' : null;
    });
  });

  /* ---------- Modal ---------- */
  function openModal(m) { m.classList.add('open'); m.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; const f = $('input:not([type=hidden]), button.btn', m); if (f) setTimeout(() => f.focus(), 60); }
  function closeModal(m) { m.classList.remove('open'); m.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
  window.openModal = openModal; window.closeModal = closeModal;
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) { const m = document.getElementById(opener.dataset.modalOpen); if (m) openModal(m); }
    if (e.target.closest('[data-modal-close]')) { const m = e.target.closest('.modal-backdrop'); if (m) closeModal(m); }
    if (e.target.classList && e.target.classList.contains('modal-backdrop')) closeModal(e.target);
  });

  /* ---------- Form lead di beranda ---------- */
  const heroLead = $('#heroLead');
  if (heroLead) {
    heroLead.addEventListener('submit', async (e) => {
      e.preventDefault();
      const msg = $('.form-msg', heroLead), btn = $('button[type=submit]', heroLead);
      msg.textContent = ''; msg.classList.remove('ok');
      btn.disabled = true; btn.textContent = 'Mengirim…';
      const data = Object.fromEntries(new FormData(heroLead).entries());
      const res = await window.postForm(window.HAKUNA.base + '/api/lead.php', data);
      btn.disabled = false; btn.textContent = 'Hubungi saya';
      if (res.ok) {
        msg.classList.add('ok'); msg.innerHTML = 'Data tersimpan! <a class="text-link" target="_blank" rel="noopener" href="' + res.whatsapp + '">Lanjut ke WhatsApp →</a>';
        window.open(res.whatsapp, '_blank', 'noopener');
        heroLead.reset();
      } else {
        msg.textContent = res.error || 'Terjadi kesalahan. Coba lagi.';
      }
    });
  }

  /* ---------- Konfirmasi sebelum aksi berbahaya ---------- */
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.dataset && f.dataset.confirm && !window.confirm(f.dataset.confirm)) { e.preventDefault(); return; }
    if (f.dataset && f.dataset.promptPassword) {
      const pw = window.prompt(f.dataset.promptPassword);
      if (!pw) { e.preventDefault(); return; }
      f.querySelector('input[name=password]').value = pw;
    }
  });

  /* ---------- Salin teks ---------- */
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    const el = $(b.dataset.copy);
    if (el && navigator.clipboard) navigator.clipboard.writeText(el.textContent.trim()).then(() => window.toast('Tersalin: ' + el.textContent.trim()));
  });

  /* ---------- Chip minat di halaman program ---------- */
  $$('[data-chip-select]').forEach((group) => {
    const aside = group.closest('.program-detail');
    const wa = aside ? $('[data-wa-subjects]', aside) : null;
    group.addEventListener('click', (e) => {
      const chip = e.target.closest('.chip'); if (!chip) return;
      chip.classList.toggle('selected');
      if (wa) {
        const picked = $$('.chip.selected', group).map((c) => c.dataset.value);
        const text = wa.dataset.waBase + (picked.length ? ' (mapel: ' + picked.join(', ') + ')' : '') + '. Mohon info jadwal & biayanya.';
        wa.href = 'https://wa.me/' + (window.HAKUNA.wa || '') + '?text=' + encodeURIComponent(text);
      }
    });
  });

  /* ---------- Filter tutor ---------- */
  const tGrid = $('#tutorGrid');
  if (tGrid) {
    const cards = $$('.tutor-card', tGrid), s = $('#tSearch'), l = $('#tLevel'), count = $('#tCount'), empty = $('#tEmpty');
    const apply = () => {
      const q = s.value.trim().toLowerCase(), lv = l.value; let n = 0;
      cards.forEach((c) => {
        const ok = (!q || c.dataset.search.includes(q)) && (!lv || c.dataset.levels.split(',').includes(lv));
        c.classList.toggle('is-hidden', !ok); if (ok) { n++; c.classList.add('in'); }
      });
      count.textContent = n + ' tutor ditemukan';
      empty.hidden = n !== 0;
    };
    s.addEventListener('input', apply); l.addEventListener('change', apply);
    $('#tReset').addEventListener('click', () => { s.value = ''; l.value = ''; apply(); });
    apply();
  }

  /* ---------- Lightbox galeri foto ---------- */
  const lb = $('#lightbox');
  if (lb) {
    const lbImg = $('img', lb);
    document.addEventListener('click', (e) => {
      const item = e.target.closest('[data-lightbox]');
      if (item) { lbImg.src = item.dataset.lightbox; lb.hidden = false; document.body.style.overflow = 'hidden'; return; }
      if (!lb.hidden && (e.target === lb || e.target.closest('.lightbox-close'))) { lb.hidden = true; document.body.style.overflow = ''; }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !lb.hidden) { lb.hidden = true; document.body.style.overflow = ''; } });
  }

  /* ---------- Tampilkan / sembunyikan password ---------- */
  $$('[data-toggle-pw]').forEach((b) => b.addEventListener('click', () => {
    const i = document.getElementById(b.dataset.togglePw); if (i) i.type = i.type === 'password' ? 'text' : 'password';
  }));
})();
