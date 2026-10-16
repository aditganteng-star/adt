/* =====================================================================
   Halaman Paket & Tryout: filter, durasi, harga, checkout WhatsApp, mini-tryout
   ===================================================================== */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
  const packages = window.HAKUNA_PACKAGES || [];
  const byId = Object.fromEntries(packages.map((p) => [p.id, p]));

  let months = 1, cat = '', level = '', query = '';

  /* Diskon durasi (harus sama dengan perhitungan di api/lead.php). */
  const discountFor = (m) => (m >= 6 ? 0.10 : m >= 3 ? 0.05 : 0);
  function priceFor(p, m) {
    if (p.unit !== 'bulan') return { total: p.price, perMonth: null, old: p.old, saving: p.old ? p.old - p.price : 0 };
    const d = discountFor(m);
    const total = Math.round(p.price * m * (1 - d));
    return { total, perMonth: Math.round(total / m), old: p.old ? p.old * m : null, saving: d ? Math.round(p.price * m * d) : 0 };
  }

  /* ---------- Render harga di kartu ---------- */
  function renderPrices() {
    $$('.package-card').forEach((card) => {
      const p = byId[card.dataset.id]; if (!p) return;
      const r = priceFor(p, months);
      $('.price-now', card).textContent = rupiah(p.unit === 'bulan' ? r.total : p.price);
      $('.price-unit', card).textContent = p.unit === 'bulan' ? (months > 1 ? '/ ' + months + ' bulan' : '/ bulan') : '/ paket';
      const old = $('.price-old', card); if (old) old.textContent = r.old ? rupiah(r.old) : '';
      const note = $('.price-note', card);
      note.textContent = p.unit === 'bulan' && months > 1 ? '≈ ' + rupiah(r.perMonth) + '/bulan · hemat ' + rupiah(r.saving) : (r.saving && p.old ? 'Hemat ' + rupiah(r.saving) : '');
    });
  }

  /* ---------- Filter ---------- */
  function applyFilters() {
    let n = 0;
    $$('.package-card').forEach((card) => {
      const ok = (!cat || card.dataset.cat === cat) && (!level || card.dataset.level === level) && (!query || card.dataset.search.includes(query));
      card.classList.toggle('is-hidden', !ok); if (ok) { n++; card.classList.add('in'); }
    });
    $('#pkgEmpty').hidden = n !== 0;
  }
  $$('#pkgFilter .seg-btn').forEach((b) => b.addEventListener('click', () => {
    $$('#pkgFilter .seg-btn').forEach((x) => x.classList.remove('active')); b.classList.add('active'); cat = b.dataset.cat; applyFilters();
  }));
  $('#pkgLevel').addEventListener('change', (e) => { level = e.target.value; applyFilters(); });
  $('#pkgSearch').addEventListener('input', (e) => { query = e.target.value.trim().toLowerCase(); applyFilters(); });
  $$('#durationSeg .seg-btn').forEach((b) => b.addEventListener('click', () => {
    $$('#durationSeg .seg-btn').forEach((x) => x.classList.remove('active')); b.classList.add('active');
    months = parseInt(b.dataset.months, 10); renderPrices();
  }));

  /* ---------- Checkout ---------- */
  const modal = $('#checkoutModal'), form = $('#checkoutForm');
  let current = null;
  function openCheckout(id) {
    const p = byId[id]; if (!p) return;
    current = p;
    const m = p.unit === 'bulan' ? months : 1, r = priceFor(p, m);
    $('#coTitle').textContent = p.name;
    $('#coPkg').textContent = p.name + ' (' + p.level + ')';
    $('#coDurRow').hidden = p.unit !== 'bulan';
    $('#coDur').textContent = m + ' bulan' + (discountFor(m) ? ' · diskon ' + Math.round(discountFor(m) * 100) + '%' : '');
    $('#coTotal').textContent = rupiah(r.total);
    form.package_id.value = p.id; form.duration.value = m;
    $('.form-msg', form).textContent = '';
    $('#coForm').hidden = false; $('#coDone').hidden = true;
    window.openModal(modal);
  }
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-choose]'); if (b) openCheckout(parseInt(b.dataset.choose, 10));
    const s = e.target.closest('[data-scroll-cat]');
    if (s) { const seg = $('#pkgFilter .seg-btn[data-cat="' + s.dataset.scrollCat + '"]'); if (seg) seg.click(); }
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = $('.form-msg', form), btn = $('button[type=submit]', form);
    msg.textContent = ''; btn.disabled = true; btn.textContent = 'Menyimpan…';
    const res = await window.postForm(window.HAKUNA.base + '/api/lead.php', Object.fromEntries(new FormData(form).entries()));
    btn.disabled = false; btn.textContent = 'Lanjut ke WhatsApp →';
    if (!res.ok) { msg.textContent = res.error || 'Terjadi kesalahan.'; return; }
    $('#coWa').href = res.whatsapp;
    $('#coForm').hidden = true; $('#coDone').hidden = false;
    window.open(res.whatsapp, '_blank', 'noopener');
  });

  /* ---------- Mini tryout publik ---------- */
  const quiz = $('#miniQuiz');
  if (quiz) {
    const Q = JSON.parse(quiz.dataset.questions || '[]'), minutes = parseInt(quiz.dataset.minutes, 10) || 10;
    const start = $('.quiz-start', quiz), run = $('.quiz-run', quiz), result = $('.quiz-result', quiz);
    let idx = 0, answers = [], timer = null, left = 0;

    const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    function show() {
      const q = Q[idx];
      $('#quizNo').textContent = 'Soal ' + (idx + 1) + ' / ' + Q.length;
      $('#quizBar').style.width = ((idx + 1) / Q.length * 100) + '%';
      $('#quizSub').textContent = q.subtest;
      $('#quizQ').textContent = q.q;
      const box = $('#quizOpts'); box.innerHTML = '';
      q.o.forEach((t, i) => {
        const b = document.createElement('button'); b.type = 'button'; b.className = 'quiz-opt' + (answers[idx] === i ? ' selected' : '');
        b.innerHTML = '<b>' + 'ABCD'[i] + '</b><span></span>'; b.lastChild.textContent = t;
        b.addEventListener('click', () => { answers[idx] = i; $$('.quiz-opt', box).forEach((x) => x.classList.remove('selected')); b.classList.add('selected'); });
        box.appendChild(b);
      });
      $('#quizPrev').style.visibility = idx ? 'visible' : 'hidden';
      $('#quizNext').textContent = idx === Q.length - 1 ? 'Selesai ✓' : 'Lanjut →';
    }
    function finish() {
      clearInterval(timer);
      let ok = 0; Q.forEach((q, i) => { if (answers[i] === q.c) ok++; });
      const score = Math.round(ok / Q.length * 100);
      run.hidden = true; result.hidden = false;
      $('#quizScore').textContent = score; $('#quizRing').style.setProperty('--p', score);
      $('#quizMsg').textContent = score >= 80 ? 'Luar biasa! 🎉' : score >= 50 ? 'Bagus, tinggal sedikit lagi! 💪' : 'Yuk belajar lagi bareng Hakuna! 📖';
      $('#quizDetail').textContent = 'Benar ' + ok + ' dari ' + Q.length + ' soal.';
      const rev = $('#quizReview'); rev.innerHTML = '';
      Q.forEach((q, i) => {
        const d = document.createElement('div'); const right = answers[i] === q.c; d.className = 'qr-item ' + (right ? 'ok' : 'bad');
        const t = document.createElement('b'); t.textContent = (i + 1) + '. ' + q.q;
        const a = document.createElement('p'); a.textContent = (right ? '✓ Benar: ' : '✗ Jawabanmu: ' + (answers[i] === undefined ? '(kosong)' : q.o[answers[i]]) + ' · Kunci: ') + q.o[q.c];
        const w = document.createElement('p'); w.textContent = '💡 ' + (q.why || '');
        d.append(t, a, w); rev.appendChild(d);
      });
      quiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    $('#quizStart').addEventListener('click', () => {
      idx = 0; answers = []; left = minutes * 60;
      start.hidden = true; result.hidden = true; run.hidden = false; show();
      $('#quizTimer').textContent = '⏱ ' + fmt(left);
      clearInterval(timer); timer = setInterval(() => { left--; $('#quizTimer').textContent = '⏱ ' + fmt(Math.max(left, 0)); if (left <= 0) finish(); }, 1000);
    });
    $('#quizPrev').addEventListener('click', () => { if (idx > 0) { idx--; show(); } });
    $('#quizNext').addEventListener('click', () => { if (idx < Q.length - 1) { idx++; show(); } else finish(); });
    $('#quizRetry').addEventListener('click', () => { result.hidden = true; start.hidden = false; });
  }

  /* ---------- Init + parameter URL (?pilih=slug &kategori= &level= &q=) ---------- */
  renderPrices();
  const params = new URLSearchParams(location.search);
  if (params.get('kategori')) { const b = $('#pkgFilter .seg-btn[data-cat="' + params.get('kategori') + '"]'); if (b) b.click(); }
  if (params.get('level')) { level = params.get('level'); $('#pkgLevel').value = level; }
  if (params.get('q')) { query = params.get('q').toLowerCase(); $('#pkgSearch').value = params.get('q'); }
  applyFilters();
  if (params.get('pilih')) {
    const p = packages.find((x) => x.slug === params.get('pilih'));
    if (p) {
      const card = $('.package-card[data-slug="' + p.slug + '"]');
      if (card) { card.scrollIntoView({ block: 'center' }); card.style.outline = '3px solid var(--orange)'; }
      setTimeout(() => openCheckout(p.id), 350);
    }
  }
})();
