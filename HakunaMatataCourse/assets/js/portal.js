/* =====================================================================
   Dashboard: sidebar mobile, form materi, absensi massal, pengerjaan tryout
   ===================================================================== */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ---------- Sidebar mobile ---------- */
  const sb = $('#sidebar'), scrim = $('#sidebarScrim'), tg = $('#sidebarToggle');
  if (sb && tg) {
    const toggle = (open) => { sb.classList.toggle('open', open); scrim.classList.toggle('open', open); };
    tg.addEventListener('click', () => toggle(!sb.classList.contains('open')));
    scrim.addEventListener('click', () => toggle(false));
  }

  /* ---------- Form materi: file vs tautan ---------- */
  const kind = $('#kindSel');
  if (kind) {
    const sync = () => { $('#fileField').hidden = kind.value !== 'file'; $('#urlField').hidden = kind.value === 'file'; };
    kind.addEventListener('change', sync); sync();
  }

  /* ---------- Absensi: hadirkan semua ---------- */
  const all = $('#markAllPresent');
  if (all) all.addEventListener('click', () => {
    $$('input[type=radio][value="Hadir"]').forEach((r) => { r.checked = true; });
    window.toast('Semua murid ditandai Hadir — jangan lupa simpan');
  });

  /* ---------- Pengerjaan tryout ---------- */
  const form = $('#tryoutForm');
  if (form) {
    const cards = $$('.q-card', form), dots = $$('.q-dot', form), total = cards.length;
    let cur = 0, remaining = parseInt(form.dataset.remaining, 10) || 0, submitting = false;

    const go = (i) => {
      cur = Math.max(0, Math.min(total - 1, i));
      cards.forEach((c, k) => { c.hidden = k !== cur; });
      dots.forEach((d, k) => d.classList.toggle('current', k === cur));
      $('#prevQ').style.visibility = cur ? 'visible' : 'hidden';
      $('#nextQ').style.visibility = cur < total - 1 ? 'visible' : 'hidden';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const refresh = () => {
      let n = 0;
      cards.forEach((c, k) => { const done = !!$('input:checked', c); dots[k].classList.toggle('answered', done); if (done) n++; });
      $('#answeredInfo').textContent = n + ' / ' + total + ' terjawab';
    };
    $('#prevQ').addEventListener('click', () => go(cur - 1));
    $('#nextQ').addEventListener('click', () => go(cur + 1));
    dots.forEach((d) => d.addEventListener('click', () => go(parseInt(d.dataset.go, 10))));
    form.addEventListener('change', refresh);

    // Timer
    const timerEl = $('#timer');
    const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    const tick = () => {
      timerEl.textContent = fmt(Math.max(remaining, 0));
      timerEl.classList.toggle('warn', remaining <= 60);
      if (remaining <= 0 && !submitting) { submitting = true; window.toast('Waktu habis! Jawabanmu dikirim otomatis.'); form.submit(); return; }
      remaining--;
    };
    tick(); setInterval(tick, 1000);

    // Konfirmasi saat menekan Selesai (tidak untuk submit otomatis)
    form.addEventListener('submit', (e) => {
      if (submitting) return;
      const unanswered = total - $$('.q-card', form).filter((c) => $('input:checked', c)).length;
      const text = (unanswered ? unanswered + ' soal belum dijawab. ' : '') + $('#finishBtn').dataset.confirmSubmit;
      if (!window.confirm(text)) { e.preventDefault(); return; }
      submitting = true;
    });
    window.addEventListener('beforeunload', (e) => { if (!submitting) { e.preventDefault(); e.returnValue = ''; } });

    // Pintasan keyboard: ← → dan A–D
    document.addEventListener('keydown', (e) => {
      if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName) && e.key.length > 1) return;
      if (e.key === 'ArrowRight') go(cur + 1);
      if (e.key === 'ArrowLeft') go(cur - 1);
      const k = e.key.toUpperCase();
      if ('ABCD'.includes(k) && k.length === 1) { const r = $('input[value="' + k + '"]', cards[cur]); if (r) { r.checked = true; refresh(); } }
    });
    go(0); refresh();
  }
})();
