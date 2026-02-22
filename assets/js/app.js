// assets/js/app.js - Enhanced Version

// ---------- Constants & Assets ----------
const LOTTIE_URLS = {
  correct: 'https://assets5.lottiefiles.com/packages/lf20_lk9mpxbd.json', // Green check
  wrong: 'https://assets10.lottiefiles.com/packages/lf20_qpax9y5v.json',  // Red X
  celebrate: 'https://assets2.lottiefiles.com/packages/lf20_T68743.json' // Party pop
};

// ---------- helper fetch + json ----------
async function apiGet(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error('Network response not ok');
  return await res.json();
}
async function apiPost(url, data) {
  const formData = new FormData();
  for (const [k, v] of Object.entries(data)) {
    formData.append(k, v);
  }
  const res = await fetch(url, {
    method: 'POST',
    body: formData
  });
  return await res.json();
}


// ---------- FRONTEND ----------
document.addEventListener('DOMContentLoaded', () => {

  // elements
  const categoryBtns = document.querySelectorAll('.category-btn');
  const packageList = document.getElementById('package-list');
  const welcome = document.getElementById('welcome');
  const quizArea = document.getElementById('quiz-area');
  const quizCard = document.getElementById('quiz-card');
  const questionText = document.getElementById('question-text');
  const optionsGrid = document.getElementById('options-grid');
  const qIndexEl = document.getElementById('q-index');
  const qTotalEl = document.getElementById('q-total');
  const scoreArea = document.getElementById('score-area');
  const scoreValue = document.getElementById('score-value');
  const scoreSub = document.getElementById('score-sub');
  const restartBtn = document.getElementById('restart-btn');
  const soundCorrect = document.getElementById('sound-correct');
  const soundWrong = document.getElementById('sound-wrong');
  const feedbackOverlay = document.getElementById('feedback-overlay');
  const lottieContainer = document.getElementById('lottie-container');

  let currentQuestions = [];
  let currentIndex = 0;
  let correctCount = 0;

  // click category -> load packages
  categoryBtns.forEach(btn => {
    btn.addEventListener('click', async () => {
      // close sidebar on mobile if open
      document.querySelector('.sidebar').classList.remove('show');

      // active state
      categoryBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const cid = btn.getAttribute('data-id');
      try {
        const pkgs = await apiGet(`fetch_packages.php?category=${cid}`);
        showPackages(pkgs);
      } catch (e) {
        console.error(e);
        alert('Gagal memuat paket');
      }
    });
  });

  // Mobile sidebar toggle
  const toggleBtn = document.createElement('button');
  toggleBtn.className = 'mobile-nav-toggle btn btn-sm';
  toggleBtn.innerHTML = '<i class="fa fa-bars"></i> Kategori';
  document.querySelector('.main-area').prepend(toggleBtn);

  toggleBtn.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('show');
  });

  function showPackages(pkgs) {
    // transition out current
    gsap.to(['#welcome', '#quiz-area', '#score-area', '#package-list'], {
      opacity: 0,
      y: 10,
      duration: 0.3,
      onComplete: () => {
        welcome.classList.add('d-none');
        scoreArea.classList.add('d-none');
        quizArea.classList.add('d-none');
        packageList.innerHTML = '';
        packageList.classList.remove('d-none');
        gsap.set(packageList, { opacity: 0, y: 20 });

        if (pkgs.length === 0) {
          packageList.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">Belum ada paket di kategori ini.</p></div>';
        } else {
          pkgs.forEach(p => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6';
            col.innerHTML = `
              <div class="package-card p-4 h-100 glass-card">
                <h5 class="fw-bold">${escapeHtml(p.name)}</h5>
                <p class="text-muted small mb-4">${escapeHtml(p.description || 'Pelajaran seru menantimu!')}</p>
                <button class="btn btn-sm btn-primary px-4 start-package" data-id="${p.id}">Mulai Kuis</button>
              </div>`;
            packageList.appendChild(col);
          });
        }

        // transition in
        gsap.to(packageList, { opacity: 1, y: 0, duration: 0.5, ease: "back.out(1.7)" });
        gsap.from('.package-card', { opacity: 0, scale: 0.9, duration: 0.4, stagger: 0.1, ease: "power2.out", delay: 0.2 });

        // attach start buttons
        document.querySelectorAll('.start-package').forEach(b => {
          b.addEventListener('click', async () => {
            const pid = b.getAttribute('data-id');
            try {
              const qs = await apiGet(`fetch_questions.php?package=${pid}`);
              startQuiz(qs);
            } catch (e) {
              console.error(e);
              alert('Gagal memuat soal');
            }
          });
        });
      }
    });
  }

  function startQuiz(questions) {
    if (!questions || questions.length === 0) {
      alert('Paket kosong. Tambahkan soal di admin.');
      return;
    }
    currentQuestions = questions;
    currentIndex = 0;
    correctCount = 0;

    gsap.to('#package-list', {
      opacity: 0, y: -20, duration: 0.3, onComplete: () => {
        packageList.classList.add('d-none');
        quizArea.classList.remove('d-none');
        scoreArea.classList.add('d-none');

        // Add progress bar if not exists
        if (!document.querySelector('.quiz-progress')) {
          const prg = document.createElement('div');
          prg.className = 'quiz-progress';
          prg.innerHTML = '<div class="quiz-progress-bar"></div>';
          quizCard.prepend(prg);
        }

        renderQuestion();
      }
    });
  }

  function renderQuestion() {
    const q = currentQuestions[currentIndex];
    qIndexEl.textContent = (currentIndex + 1);
    qTotalEl.textContent = currentQuestions.length;

    // update progress bar
    const progress = (currentIndex / currentQuestions.length) * 100;
    gsap.to('.quiz-progress-bar', { width: `${progress}%`, duration: 0.5 });

    // render question (may contain MathJax)
    questionText.innerHTML = q.question_text;

    // render options as 2-col cards
    optionsGrid.innerHTML = '';
    const opts = [
      { k: 'A', v: q.option_a },
      { k: 'B', v: q.option_b },
      { k: 'C', v: q.option_c },
      { k: 'D', v: q.option_d },
    ];
    opts.forEach((opt, idx) => {
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6';
      col.innerHTML = `<div class="option-card" data-key="${opt.k}"><div class="fw-bold text-primary">${opt.k}. </div><div>${escapeHtml(opt.v)}</div></div>`;
      optionsGrid.appendChild(col);
    });

    // MathJax render if present
    if (window.MathJax && MathJax.typesetPromise) {
      MathJax.typesetPromise();
    }

    // attach click handlers
    document.querySelectorAll('.option-card').forEach(el => {
      el.addEventListener('click', () => {
        handleAnswer(el.getAttribute('data-key'), el);
      });
    });

    // animate in
    gsap.fromTo(quizCard, { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.4, ease: "power2.out" });
    gsap.from('#question-text', { duration: 0.4, x: -20, opacity: 0, delay: 0.1 });
    gsap.from('.option-card', { duration: 0.4, y: 15, opacity: 0, stagger: 0.08, delay: 0.2 });
  }

  async function handleAnswer(key, el) {
    // prevent multiple clicks
    if (el.parentElement.classList.contains('locked')) return;
    optionsGrid.classList.add('locked');

    const q = currentQuestions[currentIndex];
    const correct = q.correct_option.toUpperCase();
    const isCorrect = (key === correct);

    if (isCorrect) {
      correctCount++;
      soundCorrect.currentTime = 0; soundCorrect.play().catch(() => { });
      el.classList.add('correct');
      triggerFeedback('correct');
    } else {
      soundWrong.currentTime = 0; soundWrong.play().catch(() => { });
      el.classList.add('wrong');
      const goodEl = document.querySelector(`.option-card[data-key="${correct}"]`);
      if (goodEl) goodEl.classList.add('correct');
      triggerFeedback('wrong');
    }

    // delay before next
    setTimeout(() => {
      optionsGrid.classList.remove('locked');
      currentIndex++;
      if (currentIndex < currentQuestions.length) {
        gsap.to(quizCard, { opacity: 0, x: -20, duration: 0.3, onComplete: renderQuestion });
      } else {
        showScore();
      }
    }, 1500);
  }

  function triggerFeedback(type) {
    lottieContainer.innerHTML = `<lottie-player src="${LOTTIE_URLS[type]}" background="transparent" speed="1" style="width: 250px; height: 250px;" autoplay></lottie-player>`;
    feedbackOverlay.classList.remove('d-none');
    setTimeout(() => feedbackOverlay.classList.add('show'), 10);

    // Hide after animation
    setTimeout(() => {
      feedbackOverlay.classList.remove('show');
      setTimeout(() => feedbackOverlay.classList.add('d-none'), 300);
    }, 1200);
  }

  function showScore() {
    // Final progress bar fill
    gsap.to('.quiz-progress-bar', { width: '100%', duration: 0.5 });

    gsap.to(quizArea, {
      opacity: 0, scale: 0.8, duration: 0.5, onComplete: () => {
        quizArea.classList.add('d-none');
        scoreArea.classList.remove('d-none');

        const total = currentQuestions.length;
        const nilai = Math.round((correctCount / total) * 100);

        scoreValue.textContent = '0';
        scoreSub.textContent = `${correctCount} dari ${total} jawaban benar`;

        gsap.fromTo(scoreArea, { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.6 });

        // countdown animation
        gsap.to({}, {
          duration: 1.5,
          onUpdate: function () {
            const value = Math.round(this.progress() * nilai);
            scoreValue.textContent = value;
          },
          onComplete: function () {
            if (nilai >= 80) {
              confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#2ed573', '#1e90ff', '#ffa502']
              });
            }
            gsap.fromTo('#score-value', { scale: 0.8 }, { scale: 1.2, duration: 0.4, ease: "elastic.out(1, 0.3)" });
          }
        });
      }
    });
  }

  restartBtn?.addEventListener('click', () => {
    gsap.to(scoreArea, {
      opacity: 0, scale: 0.9, duration: 0.3, onComplete: () => {
        scoreArea.classList.add('d-none');
        packageList.classList.remove('d-none');
        gsap.fromTo('#package-list', { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.4 });
      }
    });
  });

  function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
}); // DOMContentLoaded end


// ---------- ADMIN area (single-page-ish) ----------
document.addEventListener('DOMContentLoaded', () => {

  // if on admin.php...
  if (!document.querySelector('.admin-menu')) return;

  const adminContent = document.getElementById('admin-content');

  // router for admin sections
  document.querySelectorAll('.admin-menu').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const page = btn.getAttribute('data-page');
      loadAdminPage(page);
      // active class
      document.querySelectorAll('.admin-menu').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  // default open manage-questions
  loadAdminPage('manage-questions');

  async function loadAdminPage(page) {
    if (page === 'manage-questions') return renderManageQuestions();
    if (page === 'manage-categories') return renderManageCategories();
    if (page === 'manage-packages') return renderManagePackages();
    adminContent.innerHTML = '<div>Pilih menu</div>';
  }

  // --- Manage Categories ---
  async function renderManageCategories() {
    adminContent.innerHTML = `
      <h3>Kelola Kategori</h3>
      <div class="admin-table mb-3">
        <div class="form-inline-row mb-2">
          <input id="cat-name" class="form-control" placeholder="Nama kategori">
          <button id="cat-add" class="btn btn-success">Tambah</button>
        </div>
        <div id="cat-list" class="mt-3"></div>
      </div>
    `;
    document.getElementById('cat-add').addEventListener('click', async () => {
      const name = document.getElementById('cat-name').value.trim();
      if (!name) return alert('Isi nama kategori');
      const res = await apiPost('api_categories.php', { action: 'create', name });
      if (res.success) { document.getElementById('cat-name').value = ''; loadCats(); loadAllCatsForOtherForms(); } else alert('Gagal');
    });
    await loadCats();
  }

  async function loadCats() {
    const res = await apiGet('api_categories.php');
    if (!res.success) return;
    const el = document.getElementById('cat-list');
    el.innerHTML = '<table class="table"><thead><tr><th>#</th><th>Nama</th><th>Aksi</th></tr></thead><tbody></tbody></table>';
    const tbody = el.querySelector('tbody');
    res.data.forEach((c, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${idx + 1}</td><td><input class="form-control form-control-sm cat-name-input" data-id="${c.id}" value="${escapeHtml(c.name)}"></td>
        <td>
          <button class="btn btn-sm btn-primary cat-save" data-id="${c.id}">Simpan</button>
          <button class="btn btn-sm btn-danger cat-delete" data-id="${c.id}">Hapus</button>
        </td>`;
      tbody.appendChild(tr);
    });
    // handlers
    el.querySelectorAll('.cat-save').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const inp = el.querySelector(`.cat-name-input[data-id="${id}"]`);
        const name = inp.value.trim();
        const r = await apiPost('api_categories.php', { action: 'update', id, name });
        if (r.success) { alert('Tersimpan'); loadCats(); loadAllCatsForOtherForms(); }
      });
    });
    el.querySelectorAll('.cat-delete').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Hapus kategori?')) return;
        const id = btn.getAttribute('data-id');
        const r = await apiPost('api_categories.php', { action: 'delete', id });
        if (r.success) { loadCats(); loadAllCatsForOtherForms(); }
      });
    });
  }

  // --- Manage Packages ---
  async function renderManagePackages() {
    adminContent.innerHTML = `
      <h3>Kelola Paket</h3>
      <div class="admin-table mb-3">
        <div class="form-inline-row mb-2">
          <select id="pkg-cat" class="form-control"></select>
          <input id="pkg-name" class="form-control" placeholder="Nama paket">
          <input id="pkg-desc" class="form-control" placeholder="Deskripsi singkat">
          <button id="pkg-add" class="btn btn-success">Tambah Paket</button>
        </div>
        <div id="pkg-list" class="mt-3"></div>
      </div>
    `;
    await loadAllCatsForOtherForms();
    document.getElementById('pkg-add').addEventListener('click', async () => {
      const category_id = document.getElementById('pkg-cat').value;
      const name = document.getElementById('pkg-name').value.trim();
      const description = document.getElementById('pkg-desc').value.trim();
      if (!name) return alert('Isi nama paket');
      const r = await apiPost('api_packages.php', { action: 'create', category_id, name, description });
      if (r.success) { document.getElementById('pkg-name').value = ''; document.getElementById('pkg-desc').value = ''; loadPackagesList(); }
    });
    await loadPackagesList();
  }

  async function loadPackagesList() {
    const r = await apiGet('api_packages.php');
    const el = document.getElementById('pkg-list');
    el.innerHTML = '';
    if (!r.success) return;
    const tbl = document.createElement('table'); tbl.className = 'table';
    tbl.innerHTML = '<thead><tr><th>#</th><th>Paket</th><th>Kategori</th><th>Desc</th><th>Aksi</th></tr></thead>';
    const tbody = document.createElement('tbody');
    r.data.forEach((p, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${idx + 1}</td><td><input class="form-control form-control-sm pkg-name" data-id="${p.id}" value="${escapeHtml(p.name)}"></td>
        <td>${escapeHtml(p.category_name || '')}</td>
        <td><input class="form-control form-control-sm pkg-desc" data-id="${p.id}" value="${escapeHtml(p.description || '')}"></td>
        <td>
          <button class="btn btn-sm btn-primary pkg-save" data-id="${p.id}">Simpan</button>
          <button class="btn btn-sm btn-danger pkg-delete" data-id="${p.id}">Hapus</button>
        </td>`;
      tbody.appendChild(tr);
    });
    tbl.appendChild(tbody); el.appendChild(tbl);

    el.querySelectorAll('.pkg-save').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const name = el.querySelector(`.pkg-name[data-id="${id}"]`).value.trim();
        const desc = el.querySelector(`.pkg-desc[data-id="${id}"]`).value.trim();
        // need category_id — for simplicity keep existing category
        const r2 = await apiPost('api_packages.php', { action: 'update', id, name, description: desc, category_id: 0 });
        if (r2.success) { alert('Tersimpan'); loadPackagesList(); }
      });
    });
    el.querySelectorAll('.pkg-delete').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Hapus paket?')) return;
        const id = btn.getAttribute('data-id');
        const r2 = await apiPost('api_packages.php', { action: 'delete', id });
        if (r2.success) loadPackagesList();
      });
    });
  }

  // --- Manage Questions ---
  async function renderManageQuestions() {
    adminContent.innerHTML = `
      <h3>Kelola Soal</h3>
      <div class="admin-table mb-3">
        <form id="q-form" class="mb-3">
          <div class="row g-2">
            <div class="col-md-3"><select id="q-cat" class="form-control"></select></div>
            <div class="col-md-3"><select id="q-pkg" class="form-control"></select></div>
            <div class="col-md-6"><input id="q-sort" class="form-control" placeholder="Urutan (angka)"></div>
            <div class="col-12"><textarea id="q-text" rows="2" class="form-control" placeholder="Pertanyaan (MathJax allowed)"></textarea></div>
            <div class="col-md-6"><input id="opt-a" class="form-control" placeholder="Opsi A"></div>
            <div class="col-md-6"><input id="opt-b" class="form-control" placeholder="Opsi B"></div>
            <div class="col-md-6"><input id="opt-c" class="form-control" placeholder="Opsi C"></div>
            <div class="col-md-6"><input id="opt-d" class="form-control" placeholder="Opsi D"></div>
            <div class="col-md-3"><select id="q-correct" class="form-control"><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select></div>
            <div class="col-md-9 text-end"><button id="q-add" class="btn btn-success">Tambah Soal</button></div>
          </div>
        </form>

        <div id="q-list"></div>
      </div>
    `;

    await loadAllCatsForOtherForms();
    await populatePackagesForQuestionForm();
    document.getElementById('q-add').addEventListener('click', async (e) => {
      e.preventDefault();
      const data = {
        action: 'create',
        category_id: document.getElementById('q-cat').value,
        package_id: document.getElementById('q-pkg').value,
        question_text: document.getElementById('q-text').value,
        option_a: document.getElementById('opt-a').value,
        option_b: document.getElementById('opt-b').value,
        option_c: document.getElementById('opt-c').value,
        option_d: document.getElementById('opt-d').value,
        correct_option: document.getElementById('q-correct').value,
        sort_order: parseInt(document.getElementById('q-sort').value || 0, 10)
      };
      const r = await apiPost('api_questions.php', data);
      if (r.success) { alert('Soal ditambahkan'); renderManageQuestions(); }
      else alert('Gagal menambah soal');
    });

    await loadQuestionsList();
  }

  async function loadQuestionsList() {
    const r = await apiGet('api_questions.php');
    const el = document.getElementById('q-list');
    if (!r.success) { el.innerHTML = 'Gagal ambil daftar soal'; return; }
    const tbl = document.createElement('table'); tbl.className = 'table';
    tbl.innerHTML = '<thead><tr><th>#</th><th>Soal</th><th>Opsi</th><th>Benar</th><th>Aksi</th></tr></thead>';
    const tbody = document.createElement('tbody');
    r.data.forEach((q, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${idx + 1}</td>
        <td style="min-width:240px">${escapeHtml(q.question_text)}</td>
        <td>
          A: ${escapeHtml(q.option_a)}<br>
          B: ${escapeHtml(q.option_b)}<br>
          C: ${escapeHtml(q.option_c)}<br>
          D: ${escapeHtml(q.option_d)}
        </td>
        <td>${escapeHtml(q.correct_option)}</td>
        <td>
          <button class="btn btn-sm btn-danger q-del" data-id="${q.id}">Hapus</button>
        </td>`;
      tbody.appendChild(tr);
    });
    tbl.appendChild(tbody); el.innerHTML = ''; el.appendChild(tbl);

    el.querySelectorAll('.q-del').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Hapus soal?')) return;
        const id = btn.getAttribute('data-id');
        const r2 = await apiPost('api_questions.php', { action: 'delete', id });
        if (r2.success) loadQuestionsList();
      });
    });
  }

  // helper to load categories into selects used by various forms
  async function loadAllCatsForOtherForms() {
    try {
      const r = await apiGet('api_categories.php');
      if (!r.success) return;
      document.querySelectorAll('#pkg-cat, #q-cat').forEach(sel => {
        sel.innerHTML = r.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
      });
    } catch (e) { console.error(e); }
  }

  async function populatePackagesForQuestionForm() {
    const r = await apiGet('api_packages.php');
    if (!r.success) { document.getElementById('q-pkg').innerHTML = ''; return; }
    document.getElementById('q-pkg').innerHTML = r.data.map(p => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
  }

});

function escapeHtml(unsafe) {
  if (unsafe === null || unsafe === undefined) return '';
  return String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
