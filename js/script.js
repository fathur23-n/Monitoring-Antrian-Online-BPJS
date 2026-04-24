'use strict';

// ════════════════════════════════════════
//  TASK NAMES
// ════════════════════════════════════════
const TASK_NAMES = {
  3:'Akhir waktu layan admisi / mulai waktu tunggu poli',
  4:'Akhir waktu tunggu poli / mulai waktu layan poli',
  5:'Akhir waktu layan poli / mulai waktu tunggu farmasi',
  6:'Akhir waktu tunggu farmasi / mulai waktu layan farmasi',
  7:'Akhir waktu obat selesai dibuat',
};

// ════════════════════════════════════════
//  THEMES
// ════════════════════════════════════════
const THEMES = [
  { id:'purple-rain', name:'Purple Rain', colors:['#2d1b69','#6c63ff'] },
  { id:'ocean-blue',  name:'Ocean Blue',  colors:['#0c2340','#0ea5e9'] },
  { id:'midnight',    name:'Midnight',    colors:['#050510','#818cf8'] },
  { id:'emerald',     name:'Emerald',     colors:['#052e16','#22c55e'] },
  { id:'sunset',      name:'Sunset',      colors:['#431407','#f97316'] },
  { id:'rose',        name:'Rose',        colors:['#4c0519','#f43f5e'] },
  { id:'arctic',      name:'Arctic',      colors:['#083344','#06b6d4'] },
  { id:'caramel',     name:'Caramel',     colors:['#1c0a00','#b45309'] },
  { id:'crimson',     name:'Crimson',     colors:['#1f0000','#ef4444'] },
  { id:'graphite',    name:'Graphite',    colors:['#0f172a','#64748b'] },
];

function applyTheme(id) {
  document.documentElement.setAttribute('data-theme', id);
  localStorage.setItem('antrol-theme', id);
  // Update chart colors if charts exist
  if (window._chartJkn)    updateChartColors(window._chartJkn);
  if (window._chartStatus) updateChartColors(window._chartStatus);
  document.querySelectorAll('.theme-item').forEach(el => {
    el.classList.toggle('active', el.dataset.theme === id);
  });
}

function buildThemePanel() {
  const grid    = document.getElementById('themeGrid');
  const current = localStorage.getItem('antrol-theme') || 'purple-rain';
  if (!grid) return;
  grid.innerHTML = THEMES.map(t => `
    <div class="theme-item${t.id === current ? ' active' : ''}" data-theme="${t.id}" title="${t.name}">
      <div class="theme-swatch" style="background:linear-gradient(135deg,${t.colors[0]},${t.colors[1]});"></div>
      <div class="theme-item-name">${t.name}</div>
    </div>
  `).join('');
  grid.querySelectorAll('.theme-item').forEach(el => {
    el.addEventListener('click', () => {
      applyTheme(el.dataset.theme);
    });
  });
}

// ════════════════════════════════════════
//  STATE
// ════════════════════════════════════════
let allData    = [];
let dashData   = [];
let activeTask = null;
let activeBatal = null; // { kodebooking, norekammedis, kodepoli, no_rawat }
let activeView = 'dashboard';

// ════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════
const pad = n => String(n).padStart(2,'0');
const $   = id => document.getElementById(id);
const today = () => {
  const n = new Date();
  return `${n.getFullYear()}-${pad(n.getMonth()+1)}-${pad(n.getDate())}`;
};

// ════════════════════════════════════════
//  INIT VIEW FROM URL PARAM
// ════════════════════════════════════════
(function initViewFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const view   = params.get('view');
  if (view && document.getElementById(`view-${view}`)) {
    switchView(view);
  }
})();

// ════════════════════════════════════════
//  SIDEBAR
// ════════════════════════════════════════
$('sidebarToggle').addEventListener('click', () => {
  $('sidebar').classList.toggle('collapsed');
  $('main').classList.toggle('shifted');
  const footer = document.querySelector('.app-footer');
  if (footer) footer.classList.toggle('footer-shifted');
});

// Nav switching
document.querySelectorAll('.nav-item[data-view]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const view = link.dataset.view;
    switchView(view);
  });
});

function switchView(view) {
  activeView = view;
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const section = document.getElementById(`view-${view}`);
  const navLink = document.querySelector(`.nav-item[data-view="${view}"]`);
  if (section) section.classList.add('active');
  if (navLink) navLink.classList.add('active');
  const titles = { dashboard:'Dashboard', antrean:'Lihat Antrean per Tanggal' };
  if ($('pageTitle')) $('pageTitle').textContent = titles[view] || view;
}

// ════════════════════════════════════════
//  LIVE CLOCK
// ════════════════════════════════════════
(function clock() {
  const tick = () => {
    if ($('liveClock')) $('liveClock').textContent = new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  };
  tick(); setInterval(tick, 1000);
})();

// ════════════════════════════════════════
//  TOAST
// ════════════════════════════════════════
const ICONS = {success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'};
function showToast(msg, type='success') {
  const c = $('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fas ${ICONS[type]||ICONS.info}"></i><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.animation='toastOut .28s ease forwards'; setTimeout(()=>t.remove(),300); }, 3200);
}

// ════════════════════════════════════════
//  MODAL HELPERS
// ════════════════════════════════════════
function openModal(id)  { $(id).classList.add('open'); }
function closeModal(id) { $(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target===ov) closeModal(ov.id); });
});
document.addEventListener('keydown', e => {
  if (e.key==='Escape') document.querySelectorAll('.modal-overlay.open').forEach(ov=>closeModal(ov.id));
});

// ════════════════════════════════════════
//  THEME PANEL
// ════════════════════════════════════════
buildThemePanel();
$('btnThemeToggle').addEventListener('click', () => {
  $('themePanel').classList.toggle('open');
  $('themeBackdrop').classList.toggle('open');
});
$('themeClose').addEventListener('click', () => {
  $('themePanel').classList.remove('open');
  $('themeBackdrop').classList.remove('open');
});
$('themeBackdrop').addEventListener('click', () => {
  $('themePanel').classList.remove('open');
  $('themeBackdrop').classList.remove('open');
});

// ════════════════════════════════════════
//  STATUS HELPERS
// ════════════════════════════════════════
function getStatusBadge(status) {
  const s = String(status||'').toLowerCase();
  if (s.includes('selesai')) return `<span class="sbadge sbadge-selesai"><i class="fas fa-check-circle"></i>${status}</span>`;
  if (s.includes('batal'))   return `<span class="sbadge sbadge-batal"><i class="fas fa-times-circle"></i>${status}</span>`;
  if (s.includes('belum'))   return `<span class="sbadge sbadge-belum"><i class="fas fa-clock"></i>${status}</span>`;
  return `<span class="sbadge sbadge-other">${status||'-'}</span>`;
}
function isDone(status) {
  const s = String(status||'').toLowerCase();
  return s.includes('selesai') || s.includes('batal');
}

// ════════════════════════════════════════
//  CHARTS
// ════════════════════════════════════════
function getAccentColor() {
  return getComputedStyle(document.documentElement).getPropertyValue('--p500').trim() || '#6c63ff';
}

// ════════════════════════════════════════
//  CHART: RENDER
// ════════════════════════════════════════
function renderDashboardCharts(allDayData, dateLabels) {
  // Destroy old
  if (window._chartJkn)    { window._chartJkn.destroy();    window._chartJkn = null; }
  if (window._chartStatus) { window._chartStatus.destroy(); window._chartStatus = null; }

  // Aggregate totals
  let totalS=0,totalB=0,totalBatal=0;
  allDayData.forEach(d => {
    const s = String(d.status||'').toLowerCase();
    if (s.includes('selesai')) totalS++;
    else if (s.includes('batal')) totalBatal++;
    else totalB++;
  });

  // Chart 1: Line chart per tanggal — JKN & Bridging trend
  const ctx1 = document.getElementById('chartJkn');
  if (ctx1 && dateLabels) {
    window._chartJkn = new Chart(ctx1, {
      type: 'line',
      data: {
        labels: dateLabels.map(d => {
          const [y,m,day] = d.split('-');
          return `${day}/${m}`;
        }),
        datasets: [
          {
            label: 'JKN Selesai',
            data: dateLabels.map(tgl => window._dayStats[tgl]?.jknS || 0),
            borderColor: 'rgba(34,197,94,1)', backgroundColor: 'rgba(34,197,94,.1)',
            tension: .4, fill: true, pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 2.5,
          },
          {
            label: 'JKN Belum',
            data: dateLabels.map(tgl => window._dayStats[tgl]?.jknB || 0),
            borderColor: 'rgba(34,197,94,.4)', backgroundColor: 'transparent',
            tension: .4, fill: false, pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 1.5, borderDash: [5,3],
          },
          {
            label: 'Bridging Selesai',
            data: dateLabels.map(tgl => window._dayStats[tgl]?.bridgS || 0),
            borderColor: 'rgba(14,165,233,1)', backgroundColor: 'rgba(14,165,233,.1)',
            tension: .4, fill: true, pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 2.5,
          },
          {
            label: 'Bridging Belum',
            data: dateLabels.map(tgl => window._dayStats[tgl]?.bridgB || 0),
            borderColor: 'rgba(14,165,233,.4)', backgroundColor: 'transparent',
            tension: .4, fill: false, pointRadius: 4, pointHoverRadius: 6,
            borderWidth: 1.5, borderDash: [5,3],
          },
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position:'top', labels:{ usePointStyle:true, padding:14, font:{size:11,weight:'600'} } },
          tooltip: { callbacks: { title: ctx => `Tanggal: ${ctx[0].label}` } }
        },
        scales: {
          x: { grid:{display:false}, ticks:{font:{size:11,weight:'600'}} },
          y: { grid:{color:'rgba(0,0,0,.05)'}, beginAtZero:true, ticks:{precision:0,font:{size:11}} }
        }
      }
    });
  }

  // Chart 2: Donut — Status gabungan
  const ctx2 = document.getElementById('chartStatus');
  if (ctx2) {
    window._chartStatus = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: ['Selesai','Belum Dilayani','Dibatalkan'],
        datasets:[{
          data: [totalS, totalB, totalBatal],
          backgroundColor: ['rgba(34,197,94,.85)','rgba(249,115,22,.85)','rgba(239,68,68,.85)'],
          borderWidth: 0, hoverOffset: 8,
        }]
      },
      options: {
        responsive:true, maintainAspectRatio:false, cutout:'68%',
        plugins:{
          legend:{ position:'bottom', labels:{ usePointStyle:true, padding:14, font:{size:12,weight:'600'} } },
          tooltip:{ callbacks:{ label: ctx => ` ${ctx.label}: ${ctx.parsed} pasien` } }
        }
      }
    });
  }
}

function updateChartColors() {
  if (dashData.length) renderDashboardCharts(dashData, window._dateLabels || null);
}

// ════════════════════════════════════════
//  PERSENTASE CARDS
// ════════════════════════════════════════
function renderPctSection(data) {
  const sec = $('pctSection');
  if (!sec) return;

  let allS=0,allB=0,allX=0;
  let jknS=0,jknB=0,jknX=0;
  let brgS=0,brgB=0,brgX=0;

  data.forEach(d => {
    const src  = String(d.sumberdata||'').toLowerCase();
    const s    = String(d.status||'').toLowerCase();
    const done = s.includes('selesai');
    const batal= s.includes('batal');
    const isJkn = src.includes('mobile jkn') || src.includes('jkn');

    if (done)       { allS++; isJkn ? jknS++ : brgS++; }
    else if (batal) { allX++; isJkn ? jknX++ : brgX++; }
    else            { allB++; isJkn ? jknB++ : brgB++; }
  });

  const pct = (n, total) => total === 0 ? '0%' : Math.round(n/total*100) + '%';
  const w   = (n, total) => total === 0 ? '0%' : Math.min(100, Math.round(n/total*100)) + '%';

  const allTotal = allS+allB+allX;
  const jknTotal = jknS+jknB+jknX;
  const brgTotal = brgS+brgB+brgX;

  // All
  $('pct-all-total').textContent  = `${allTotal} pasien`;
  $('pct-all-s-num').textContent  = pct(allS,allTotal);
  $('pct-all-b-num').textContent  = pct(allB,allTotal);
  $('pct-all-x-num').textContent  = pct(allX,allTotal);
  $('pct-all-s-bar').style.width  = w(allS,allTotal);
  $('pct-all-b-bar').style.width  = w(allB,allTotal);
  $('pct-all-x-bar').style.width  = w(allX,allTotal);
  // JKN
  $('pct-jkn-total').textContent  = `${jknTotal} pasien`;
  $('pct-jkn-s-num').textContent  = pct(jknS,jknTotal);
  $('pct-jkn-b-num').textContent  = pct(jknB,jknTotal);
  $('pct-jkn-x-num').textContent  = pct(jknX,jknTotal);
  $('pct-jkn-s-bar').style.width  = w(jknS,jknTotal);
  $('pct-jkn-b-bar').style.width  = w(jknB,jknTotal);
  $('pct-jkn-x-bar').style.width  = w(jknX,jknTotal);
  // Bridging
  $('pct-brg-total').textContent  = `${brgTotal} pasien`;
  $('pct-brg-s-num').textContent  = pct(brgS,brgTotal);
  $('pct-brg-b-num').textContent  = pct(brgB,brgTotal);
  $('pct-brg-x-num').textContent  = pct(brgX,brgTotal);
  $('pct-brg-s-bar').style.width  = w(brgS,brgTotal);
  $('pct-brg-b-bar').style.width  = w(brgB,brgTotal);
  $('pct-brg-x-bar').style.width  = w(brgX,brgTotal);

  sec.style.display = 'block';
}

// ════════════════════════════════════════
//  DASHBOARD: LOAD RANGE
// ════════════════════════════════════════
function getDateRange(start, end) {
  const dates = [];
  const cur   = new Date(start);
  const last  = new Date(end);
  while (cur <= last) {
    dates.push(cur.toISOString().slice(0,10));
    cur.setDate(cur.getDate() + 1);
  }
  return dates;
}

async function loadDashboard(start, end) {
  if (!end) end = start;
  const loading   = $('dashLoading');
  const loadBar   = $('loadBar');
  const loadLabel = $('loadLabel');
  const statCards = $('statCards');
  const chartGrid = $('chartGrid');
  const belumCard = $('belumCard');
  const pctSec    = $('pctSection');
  const dashEmpty = $('dashEmpty');

  // Reset
  const btnLoad = $('btnDashLoad');
  if (btnLoad) { btnLoad.disabled = true; btnLoad.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...'; }
  loading.style.display   = 'flex';
  statCards.style.display = 'none';
  chartGrid.style.display = 'none';
  belumCard.style.display = 'none';
  dashEmpty.style.display = 'none';
  if (pctSec) pctSec.style.display = 'none';

  const dates    = getDateRange(start, end);
  const total    = dates.length;
  dashData       = [];
  window._dayStats  = {};
  window._dateLabels = dates;

  // Loop fetch per tanggal dengan progress
  for (let i = 0; i < dates.length; i++) {
    const tgl = dates[i];
    const pct = Math.round((i / total) * 100);
    if (loadBar)   loadBar.style.width = pct + '%';
    if (loadLabel) loadLabel.textContent = `Memuat ${tgl} (${i+1}/${total})...`;

    try {
      const resp   = await fetch(`get_antrean_by_tanggal.php?tanggal=${encodeURIComponent(tgl)}`);
      const result = await resp.json();
      if (result?.metadata?.code === 200) {
        const raw  = result.response ?? [];
        const list = Array.isArray(raw) ? raw : (raw?.list ?? []);
        dashData.push(...list);

        // Hitung per hari untuk line chart
        let jknS=0,jknB=0,bridgS=0,bridgB=0;
        list.forEach(d => {
          const src  = String(d.sumberdata||'').toLowerCase();
          const done = isDone(d.status);
          const batal= String(d.status||'').toLowerCase().includes('batal');
          if (batal) return;
          const isJkn = src.includes('mobile jkn') || src.includes('jkn');
          if (isJkn)  { done ? jknS++   : jknB++; }
          else        { done ? bridgS++ : bridgB++; }
        });
        window._dayStats[tgl] = { jknS, jknB, bridgS, bridgB };
      }
    } catch { /* skip day on error */ }
  }

  if (loadBar)   loadBar.style.width = '100%';
  if (loadLabel) loadLabel.textContent = 'Selesai!';
  setTimeout(() => {
    loading.style.display = 'none';
    if (btnLoad) { btnLoad.disabled = false; btnLoad.innerHTML = '<i class="fas fa-sync-alt"></i> Tampilkan'; }
  }, 400);

  if (dashData.length === 0) {
    dashEmpty.style.display = 'block';
    return;
  }

  // Stat cards
  let cntS=0,cntB=0,cntBatal=0;
  dashData.forEach(d => {
    const s = String(d.status||'').toLowerCase();
    if (s.includes('selesai')) cntS++;
    else if (s.includes('batal')) cntBatal++;
    else cntB++;
  });
  $('sc-total').textContent   = dashData.length;
  $('sc-selesai').textContent = cntS;
  $('sc-belum').textContent   = cntB;
  $('sc-batal').textContent   = cntBatal;
  $('sc-sep').textContent     = '…';
  statCards.style.display = 'grid';

  // Fetch SEP range
  fetch(`get_total_sep.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`)
    .then(r=>r.json()).then(d=>{ if($('sc-sep')) $('sc-sep').textContent = d?.count ?? 0; }).catch(()=>{});

  // Charts (line chart + donut)
  chartGrid.style.display = 'grid';
  renderDashboardCharts(dashData, dates);

  // Persentase
  renderPctSection(dashData);

  // Belum dilayani (hanya dari tanggal end / hari terakhir)
  const belumList = dashData.filter(d => !isDone(d.status) && d.tanggal === end);
  $('belumCount').textContent = `${belumList.length} pasien`;
  belumCard.style.display = 'block';
  renderBelumTable(belumList, start, end);
}

async function renderBelumTable(belumList, start, end) {
  const tbody = $('tblBelumBody');
  if (belumList.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="state-empty"><i class="fas fa-user-check"></i><p>Semua pasien sudah dilayani!</p></td></tr>`;
    return;
  }
  const norawat = belumList.map(d => d.norekammedis).filter(Boolean);
  let sepMap = {};
  if (norawat.length > 0) {
    try {
      const resp = await fetch('get_belum_user.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ start, end, norawat }),
      });
      const data = await resp.json();
      if (Array.isArray(data?.data)) {
        data.data.forEach(row => { sepMap[row.nomr] = { user: row.user || '-', nama: row.nm_pasien || '' }; });
      }
    } catch { /* no user info */ }
  }
  tbody.innerHTML = belumList.map((item, i) => {
    const info    = sepMap[item.norekammedis] || null;
    const userSep = info ? info.user : (norawat.length > 0 ? '-' : '—');
    const namaPas = info ? info.nama : '';
    return `<tr>
      <td style="color:var(--text-muted);font-weight:600;">${i+1}</td>
      <td><code style="font-size:12px;color:var(--p700);font-weight:700;">${item.norekammedis||'-'}</code></td>
      <td style="font-size:13px;font-weight:600;">${namaPas || '<span style="color:var(--text-muted);">—</span>'}</td>
      <td style="font-size:12px;">${item.kodebooking||'-'}</td>
      <td><strong>${item.kodepoli||'-'}</strong></td>
      <td>${item.jampraktek||'-'}</td>
      <td>${userSep !== '—'
        ? `<span style="background:var(--p50);color:var(--p700);padding:3px 9px;border-radius:99px;font-size:12px;font-weight:600;">${userSep}</span>`
        : `<span style="color:var(--text-muted);font-size:12px;">—</span>`}
      </td>
    </tr>`;
  }).join('');
}

// ════════════════════════════════════════
//  DASHBOARD INIT
// ════════════════════════════════════════
(function initDashboard() {
  const startEl = $('dashStart');
  const endEl   = $('dashEnd');
  if (!startEl || !endEl) return;
  const t = today();
  startEl.value = t;
  endEl.value   = t;
  loadDashboard(t, t);
  $('btnDashLoad').addEventListener('click', () => {
    const s = startEl.value, e = endEl.value;
    if (!s || !e) { showToast('Pilih tanggal start dan end.','error'); return; }
    if (s > e) { showToast('Tanggal start tidak boleh lebih dari end.','error'); return; }
    loadDashboard(s, e);
  });
})();

// ════════════════════════════════════════
//  ANTREAN: DATE PICKER DEFAULT
// ════════════════════════════════════════
(function() {
  const tgl = $('tglAntrean');
  if (tgl) tgl.value = today();
})();

// ════════════════════════════════════════
//  CHIP COUNTS (selalu dari allData penuh)
// ════════════════════════════════════════
function updateChipCounts(data) {
  let cS=0,cB=0,cBatal=0;
  data.forEach(d => {
    const s = String(d.status||'').toLowerCase();
    if (s.includes('selesai')) cS++;
    else if (s.includes('batal')) cBatal++;
    else cB++;
  });
  $('cnt-semua').textContent   = data.length;
  $('cnt-selesai').textContent = cS;
  $('cnt-belum').textContent   = cB;
  $('cnt-batal').textContent   = cBatal;
  $('cnt-total').textContent   = data.length;
}

// ════════════════════════════════════════
//  ANTREAN: RENDER TABLE
// ════════════════════════════════════════
function destroyDT() {
  if (jQuery.fn.DataTable.isDataTable('#tblAntrean')) {
    jQuery('#tblAntrean').DataTable().destroy();
  }
  const bar = document.querySelector('.dt-search-bar');
  if (bar) bar.remove();
}

function initDT() {
  setTimeout(() => {
    if (!jQuery.fn.DataTable.isDataTable('#tblAntrean')) {
      jQuery('#tblAntrean').DataTable({
        drawCallback: function() {
          // Pastikan dt-top selalu ada di sticky card
          const dtTop = document.querySelector('.dt-top');
          const stickyCard = document.querySelector('.sticky-card');
          if (dtTop && stickyCard && !stickyCard.contains(dtTop)) {
            stickyCard.appendChild(dtTop);
          }
        },
        paging:      true,
        pageLength:  25,
        lengthMenu:  [10, 25, 50, 100],
        ordering:    true,
        searching:   true,
        info:        true,
        autoWidth:   false,
        language: {
          search:         '<i class="fas fa-search"></i>',
          searchPlaceholder: 'Cari No RM, Booking, Poli...',
          lengthMenu:     'Tampilkan _MENU_ data',
          info:           'Menampilkan _START_–_END_ dari _TOTAL_ antrean',
          infoEmpty:      'Tidak ada data',
          infoFiltered:   '(difilter dari _MAX_ data)',
          paginate: {
            first:    '«',
            last:     '»',
            next:     '›',
            previous: '‹',
          },
          emptyTable: 'Tidak ada data antrean.',
          zeroRecords: 'Tidak ditemukan data yang cocok.',
        },
        columnDefs: [
          { orderable: false, targets: [8] }, // kolom Aksi
        ],
        dom: 'rt<"dt-bottom"ip>',
        initComplete: function() {
          // Buat search box manual dan inject ke sticky card
          const api      = this.api();
          const stickyCard = document.querySelector('.sticky-card');
          if (!stickyCard) return;

          // Hapus existing dt-search jika ada
          const existing = stickyCard.querySelector('.dt-search-bar');
          if (existing) existing.remove();

          const wrap = document.createElement('div');
          wrap.className = 'dt-search-bar';
          wrap.innerHTML = `
            <div class="dt-search-wrap">
              <i class="fas fa-search"></i>
              <input type="text" id="dtSearchInput" placeholder="Cari No RM, Booking, Poli, Dokter..." autocomplete="off">
            </div>
            <div class="dt-length-wrap">
              <span>Tampilkan</span>
              <select id="dtLengthSelect">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span>data</span>
            </div>`;
          stickyCard.appendChild(wrap);

          document.getElementById('dtSearchInput').addEventListener('input', function() {
            api.search(this.value).draw();
          });
          document.getElementById('dtLengthSelect').addEventListener('change', function() {
            api.page.len(Number(this.value)).draw();
          });
        },
      });
    }
  }, 50);
}

function renderTable(data) {
  destroyDT();
  const tbody     = $('tblBody');
  const filterRow = $('filterRow');
  const statsBar  = $('statsBar');
  tbody.innerHTML = '';

  if (!Array.isArray(data) || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" class="state-empty"><i class="fas fa-inbox"></i><p>Tidak ada data antrean.</p></td></tr>`;
    filterRow.style.display = 'none';
    statsBar.style.display  = 'none';
    return;
  }

  filterRow.style.display = 'flex';

  // Counts hanya diupdate via updateChipCounts(allData) — tidak dari filtered data

  data.forEach((item, idx) => {
    const done     = isDone(item.status);
    const estimasi = item.estimasidilayani
      ? new Date(Number(item.estimasidilayani)).toLocaleString('id-ID',{timeZone:'Asia/Jakarta',hour12:false})
      : '-';
    const isSelesai = String(item.status||'').toLowerCase().includes('selesai');
    const isBatal   = String(item.status||'').toLowerCase().includes('batal');
    const showTasks = !isSelesai && !isBatal;
    const taskBtns  = showTasks ? [3,4,5,6,7].map(tid =>
      `<button class="btn-task btn-send-task" data-task="${tid}" data-kode="${item.kodebooking}" data-tgl="${item.tanggal}" title="${TASK_NAMES[tid]}">TASK ${tid}</button>`
    ).join('') : '';
    const utilGroup = `
      <button class="btn-history btn-show-riwayat" data-kode="${item.kodebooking}"><i class="fas fa-history"></i> Riwayat</button>
      ${showTasks ? `<button class="btn-batal btn-batal-antrean"
        data-kode="${item.kodebooking}"
        data-rm="${item.norekammedis||''}"
        data-poli="${item.kodepoli||''}"
        data-rawat="${item.norekammedis||''}">
        <i class="fas fa-ban"></i> Batal</button>` : ''}`;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="color:var(--text-muted);font-weight:600;">${idx+1}</td>
      <td>
        <code style="font-size:11.5px;color:var(--p700);font-weight:700;">${item.kodebooking||'-'}</code>
        ${item.norekammedis?`<div style="font-size:11px;color:var(--text-muted);margin-top:3px;display:flex;align-items:center;gap:4px;">
          <i class="fas fa-id-card" style="font-size:10px;"></i>
          <span>RM: ${item.norekammedis}</span>
          <button class="btn-copy-rm" data-rm="${item.norekammedis}" title="Salin No RM" style="background:none;border:none;cursor:pointer;padding:1px 4px;border-radius:4px;color:var(--text-muted);transition:all .2s;line-height:1;">
            <i class="fas fa-copy" style="font-size:10px;"></i>
          </button>
        </div>`:''}
      </td>
      <td>${item.tanggal||'-'}</td>
      <td>${item.kodepoli||'-'}</td>
      <td>${item.kodedokter||'-'}</td>
      <td><strong>${item.jampraktek||'-'}</strong></td>
      <td>${getStatusBadge(item.status)}</td>
      <td style="font-size:12.5px;">${estimasi}</td>
      <td>
        <div class="aksi-wrap">
          <div class="task-group">${taskBtns}</div>
          <div class="util-group">${utilGroup}</div>
        </div>
      </td>`;
    tbody.appendChild(tr);
  });
  bindTableButtons();
  initDT();
}

// ════════════════════════════════════════
//  BIND TABLE BUTTONS
// ════════════════════════════════════════
function bindTableButtons() {
  // Task buttons
  document.querySelectorAll('.btn-send-task').forEach(btn => {
    btn.addEventListener('click', () => {
      const taskid = Number(btn.dataset.task);
      const kode   = btn.dataset.kode;
      const tgl    = btn.dataset.tgl;
      activeTask   = { taskid, kodebooking:kode, tanggal:tgl };
      $('modalWaktuTitle').textContent = `Atur Waktu • TASK ${taskid}`;
      $('modalTanggal').textContent    = tgl;
      const n = new Date();
      $('inpJam').value   = pad(n.getHours());
      $('inpMenit').value = pad(n.getMinutes());
      $('inpDetik').value = pad(n.getSeconds());
      openModal('modalWaktu');
      setTimeout(() => $('inpJam').focus(), 100);
    });
  });
  // Riwayat
  document.querySelectorAll('.btn-show-riwayat').forEach(btn => {
    btn.addEventListener('click', async () => {
      const kode = btn.dataset.kode;
      $('modalRiwayatTitle').textContent = `Task Terkirim  •  ${kode}`;
      $('timelineContainer').innerHTML = `<div class="tl-empty"><i class="fas fa-spinner fa-spin" style="font-size:24px;opacity:.4;"></i><p>Memuat riwayat...</p></div>`;
      openModal('modalRiwayat');
      const result = await fetchRiwayatTask(kode);
      renderRiwayatTable(result);
    });
  });
  // Copy RM
  document.querySelectorAll('.btn-copy-rm').forEach(btn => {
    btn.addEventListener('click', () => {
      navigator.clipboard.writeText(btn.dataset.rm).then(() => {
        btn.innerHTML = '<i class="fas fa-check" style="font-size:10px;"></i>';
        btn.style.color = 'var(--green-500)';
        setTimeout(() => { btn.innerHTML='<i class="fas fa-copy" style="font-size:10px;"></i>'; btn.style.color=''; }, 1500);
      }).catch(() => showToast('Gagal menyalin No RM.','error'));
    });
  });
  // Batal
  document.querySelectorAll('.btn-batal-antrean:not(:disabled)').forEach(btn => {
    btn.addEventListener('click', () => {
      activeBatal = {
        kodebooking:  btn.dataset.kode,
        norekammedis: btn.dataset.rm   || '',
        kodepoli:     btn.dataset.poli || '',
        no_rawat:     btn.dataset.rawat|| '',
      };
      $('modalBatalTitle').textContent  = `Batal Antrean • ${activeBatal.kodebooking}`;
      $('batalKode').textContent        = activeBatal.kodebooking;
      $('batalRM').textContent          = activeBatal.norekammedis || '—';
      $('batalPoli').textContent        = activeBatal.kodepoli     || '—';
      $('batalAlasan').value            = 'Batal tidak hadir';
      $('batalCharCount').textContent   = String('Batal tidak hadir'.length);
      openModal('modalBatal');
      setTimeout(() => $('batalAlasan').focus(), 100);
    });
  });
}

// ════════════════════════════════════════
//  MODAL WAKTU
// ════════════════════════════════════════
$('btnBatalWaktu').addEventListener('click', () => { closeModal('modalWaktu'); activeTask=null; });

$('btnKirimWaktu').addEventListener('click', async () => {
  if (!activeTask) return;
  const jam   = String($('inpJam').value||'').padStart(2,'0');
  const menit = String($('inpMenit').value||'').padStart(2,'0');
  const detik = String($('inpDetik').value||'').padStart(2,'0');
  if (!$('inpJam').value||!$('inpMenit').value||!$('inpDetik').value) {
    showToast('Lengkapi jam, menit, dan detik.','error'); return;
  }
  const epoch = new Date(`${activeTask.tanggal}T${jam}:${menit}:${detik}`).getTime();
  if (isNaN(epoch)) { showToast('Format waktu tidak valid.','error'); return; }

  const btn = $('btnKirimWaktu');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
  try {
    const resp = await fetch('send_request.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({kodebooking:activeTask.kodebooking,taskid:activeTask.taskid,waktu:epoch})});
    const data = await resp.json();
    if (data?.metadata?.code===200) { showToast(`TASK ${activeTask.taskid} berhasil dikirim!`,'success'); closeModal('modalWaktu'); activeTask=null; }
    else showToast(data?.metadata?.message||'Gagal mengirim task.','error');
  } catch { showToast('Terjadi kesalahan koneksi.','error'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Kirim'; }
});

// ════════════════════════════════════════
//  MODAL RIWAYAT
// ════════════════════════════════════════
$('btnTutupRiwayat').addEventListener('click', () => closeModal('modalRiwayat'));

async function fetchRiwayatTask(kodebooking) {
  try {
    const resp = await fetch('get_task_terkirim.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({kodebooking})});
    return await resp.json();
  } catch { return {error:true}; }
}

function renderRiwayatTable(result) {
  const container = $('timelineContainer');
  if (!result||result?.metadata?.code!==200) {
    container.innerHTML = `<div class="tl-empty"><i class="fas fa-exclamation-triangle"></i><p>${result?.metadata?.message||'Gagal memuat data dari BPJS.'}</p></div>`; return;
  }
  const raw  = result?.response ?? [];
  const list = Array.isArray(raw) ? raw : (raw?.list ?? []);
  if (list.length===0) {
    container.innerHTML = `<div class="tl-empty"><i class="fas fa-inbox"></i><p>Belum ada task yang dikirim.</p></div>`; return;
  }
  const COLORS = {3:'#6c63ff',4:'#0ea5e9',5:'#22c55e',6:'#f97316',7:'#ef4444'};
  container.innerHTML = `<div class="tl-wrap">${list.map((row,i)=>{
    const c = COLORS[row.taskid]||'#6c63ff';
    const isLast = i===list.length-1;
    return `<div class="tl-item${isLast?' tl-last':''}">
      <div class="tl-left">
        <div class="tl-dot" style="background:${c};box-shadow:0 0 0 4px ${c}22;">${row.taskid}</div>
        ${!isLast?`<div class="tl-line" style="background:linear-gradient(to bottom,${c}55,transparent);"></div>`:''}
      </div>
      <div class="tl-card">
        <div class="tl-card-header" style="border-left:3px solid ${c};">
          <span class="tl-taskname">${row.taskname||'-'}</span>
          <span class="tl-taskid-label" style="color:${c};">TASK ${row.taskid}</span>
        </div>
        <div class="tl-times">
          <div class="tl-time-item"><i class="fas fa-stethoscope" style="color:${c};"></i><div><div class="tl-time-label">Waktu Pelayanan</div><div class="tl-time-val">${row.wakturs||'-'}</div></div></div>
          <div class="tl-time-arrow"><i class="fas fa-arrow-right"></i></div>
          <div class="tl-time-item"><i class="fas fa-paper-plane" style="color:${c};"></i><div><div class="tl-time-label">Waktu Kirim</div><div class="tl-time-val">${row.waktu||'-'}</div></div></div>
        </div>
      </div>
    </div>`;
  }).join('')}</div>`;
}

// ════════════════════════════════════════
//  MODAL BATAL
// ════════════════════════════════════════
$('btnBatalCancel').addEventListener('click', () => {
  closeModal('modalBatal');
  activeBatal = null;
});

// Char counter
$('batalAlasan').addEventListener('input', function() {
  $('batalCharCount').textContent = this.value.length;
});

$('btnBatalKirim').addEventListener('click', async () => {
  if (!activeBatal) return;
  const alasan = $('batalAlasan').value.trim();
  if (!alasan) {
    $('batalAlasan').style.borderColor = 'var(--red-500)';
    $('batalAlasan').focus();
    showToast('Isi alasan pembatalan terlebih dahulu.', 'error');
    return;
  }
  $('batalAlasan').style.borderColor = '';

  const btn = $('btnBatalKirim');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

  try {
    const resp = await fetch('batal_antrean.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        kodebooking:  activeBatal.kodebooking,
        keterangan:   alasan,
        norekammedis: activeBatal.norekammedis,
        no_rawat:     activeBatal.no_rawat,
      }),
    });
    const data = await resp.json();

    if (data?.metadata?.code === 200) {
      showToast('Antrean berhasil dibatalkan!', 'success');
      closeModal('modalBatal');
      activeBatal = null;
      // Refresh tabel
      loadAntrean();
    } else if (data?.metadata?.code === 409) {
      showToast(data.metadata.message, 'info');
      closeModal('modalBatal');
    } else {
      showToast(data?.metadata?.message || 'Gagal membatalkan antrean.', 'error');
    }
  } catch(e) {
    showToast('Terjadi kesalahan koneksi.', 'error');
    console.error(e);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-ban"></i> Konfirmasi Batal';
  }
});

// ════════════════════════════════════════
//  FILTER CHIPS
// ════════════════════════════════════════
document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    applyFilter(chip.dataset.filter);
  });
});
function applyFilter(filter) {
  const f = filter.toLowerCase();
  let result = allData;
  if (f==='selesai') result = allData.filter(d=>String(d.status||'').toLowerCase().includes('selesai'));
  else if (f==='belum') result = allData.filter(d=>{const s=String(d.status||'').toLowerCase();return!s.includes('selesai')&&!s.includes('batal');});
  else if (f==='batal') result = allData.filter(d=>String(d.status||'').toLowerCase().includes('batal'));
  renderTable(result);
}

// ════════════════════════════════════════
//  LOAD ANTREAN
// ════════════════════════════════════════
$('btnLoad').addEventListener('click', loadAntrean);
$('tglAntrean').addEventListener('keydown', e => { if(e.key==='Enter') loadAntrean(); });

async function loadAntrean() {
  const tanggal = $('tglAntrean').value;
  if (!tanggal) { showToast('Pilih tanggal terlebih dahulu.','error'); return; }
  allData = [];
  $('filterRow').style.display = 'none';
  $('statsBar').style.display  = 'none';
  document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active'));
  document.querySelector('.chip-all').classList.add('active');
  $('tblBody').innerHTML = `<tr><td colspan="9" class="state-empty"><i class="fas fa-spinner fa-spin" style="font-size:30px;opacity:.3;"></i><p>Memuat data antrean...</p></td></tr>`;
  const btn = $('btnLoad');
  btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Memuat...';
  try {
    const resp = await fetch(`get_antrean_by_tanggal.php?tanggal=${encodeURIComponent(tanggal)}`);
    const data = await resp.json();
    if (data?.metadata?.code===200&&Array.isArray(data.response)) {
      allData = data.response;
      updateChipCounts(allData);
      renderTable(allData);
      if (allData.length>0) { showToast(`${allData.length} antrean ditemukan.`,'success'); computeStats(allData,tanggal); }
    } else { renderTable([]); showToast(data?.metadata?.message||'Gagal memuat antrean.','error'); }
  } catch { renderTable([]); showToast('Gagal memuat data dari server.','error'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="fas fa-search"></i> Tampilkan'; }
}

// ════════════════════════════════════════
//  STATS BAR (antrean view)
// ════════════════════════════════════════
async function computeStats(data, tanggal) {
  const bar = $('statsBar');
  if (!data||data.length===0) { bar.style.display='none'; return; }
  bar.style.display='block';
  let jknS=0,jknB=0,bridgS=0,bridgB=0;
  data.forEach(d=>{
    const src=String(d.sumberdata||'').toLowerCase();
    const done=isDone(d.status);
    if (src.includes('mobile jkn')||src.includes('jkn')) { done?jknS++:jknB++; }
    else { done?bridgS++:bridgB++; }
  });
  $('jkn-selesai').textContent     = jknS;
  $('jkn-belum').textContent       = jknB;
  $('bridging-selesai').textContent = bridgS;
  $('bridging-belum').textContent   = bridgB;
  $('sep-total').textContent        = '...';
  try {
    const resp = await fetch(`get_total_sep.php?tanggal=${encodeURIComponent(tanggal)}`);
    const d    = await resp.json();
    $('sep-total').textContent = d?.count??0;
  } catch { $('sep-total').textContent='err'; }
}

// ════════════════════════════════════════
//  SCROLL TO TOP
// ════════════════════════════════════════
const btnScrollTop = $('btnScrollTop');
window.addEventListener('scroll', () => {
  btnScrollTop.classList.toggle('visible', window.scrollY > 300);
});
btnScrollTop.addEventListener('click', () => window.scrollTo({top:0,behavior:'smooth'}));