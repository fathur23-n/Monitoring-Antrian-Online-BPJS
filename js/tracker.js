'use strict';

// ════════════════════════════════════════
//  THEMES (sama seperti main app)
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

const $ = id => document.getElementById(id);
const pad = n => String(n).padStart(2,'0');

// Apply saved theme
(function() {
  const t = localStorage.getItem('antrol-theme');
  if (t) document.documentElement.setAttribute('data-theme', t);
})();

// Theme panel
function buildThemePanel() {
  const grid    = $('themeGrid');
  const current = localStorage.getItem('antrol-theme') || 'purple-rain';
  if (!grid) return;
  grid.innerHTML = THEMES.map(t => `
    <div class="theme-item${t.id===current?' active':''}" data-theme="${t.id}" title="${t.name}">
      <div class="theme-swatch" style="background:linear-gradient(135deg,${t.colors[0]},${t.colors[1]});"></div>
      <div class="theme-item-name">${t.name}</div>
    </div>`).join('');
  grid.querySelectorAll('.theme-item').forEach(el => {
    el.addEventListener('click', () => {
      document.documentElement.setAttribute('data-theme', el.dataset.theme);
      localStorage.setItem('antrol-theme', el.dataset.theme);
      grid.querySelectorAll('.theme-item').forEach(x => x.classList.toggle('active', x===el));
    });
  });
}
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

// Sidebar toggle
$('sidebarToggle').addEventListener('click', () => {
  $('sidebar').classList.toggle('collapsed');
  $('main').classList.toggle('shifted');
});

// Live clock
(function clock() {
  const tick = () => {
    if ($('liveClock')) $('liveClock').textContent = new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  };
  tick(); setInterval(tick, 1000);
})();

// Toast
function showToast(msg, type='success') {
  const c = $('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = {success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'};
  t.innerHTML = `<i class="fas ${icons[type]||icons.info}"></i><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.style.animation='toastOut .28s ease forwards'; setTimeout(()=>t.remove(),300); }, 3200);
}

// Default tanggal hari ini
(function() {
  const now = new Date();
  const d = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
  $('trStart').value = d;
  $('trEnd').value   = d;
})();

// Aksi badge
const AKSI_MAP = {
  login: { label:'Login', cls:'tr-badge-login', icon:'fa-sign-in-alt' },
  kirim_task: { label:'Kirim Task', cls:'tr-badge-task', icon:'fa-paper-plane' },
  batal_antrean: { label:'Batal Antrean', cls:'tr-badge-batal', icon:'fa-ban' },
};

function aksiBadge(aksi) {
  const m = AKSI_MAP[aksi] || { label:aksi, cls:'', icon:'fa-circle' };
  return `<span class="tr-badge ${m.cls}"><i class="fas ${m.icon}"></i> ${m.label}</span>`;
}

let dtTracker = null;

async function loadTracker() {
  const start = $('trStart').value;
  const end   = $('trEnd').value;
  const user  = $('trUser').value;
  const aksi  = $('trAksi').value;

  if (!start || !end) { showToast('Pilih periode terlebih dahulu.','error'); return; }
  if (start > end)    { showToast('Tanggal start tidak boleh lebih dari end.','error'); return; }

  const btn = $('btnLoadTracker');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';

  const params = new URLSearchParams({ start, end, user, aksi });
  try {
    const resp = await fetch(`get_tracker.php?${params}`);
    const data = await resp.json();

    if (data.error) { showToast('Gagal memuat data.','error'); return; }

    // Update user dropdown
    const trUser = $('trUser');
    const curUser = trUser.value;
    trUser.innerHTML = '<option value="">Semua User</option>';
    (data.users || []).forEach(u => {
      trUser.innerHTML += `<option value="${u}"${u===curUser?' selected':''}>${u}</option>`;
    });

    // Stat mini
    let cLogin=0, cTask=0, cBatal=0;
    data.data.forEach(r => {
      if (r.aksi==='login')          cLogin++;
      else if (r.aksi==='kirim_task') cTask++;
      else if (r.aksi==='batal_antrean') cBatal++;
    });
    $('tr-total').textContent = data.data.length;
    $('tr-login').textContent = cLogin;
    $('tr-task').textContent  = cTask;
    $('tr-batal').textContent = cBatal;
    $('trackerStats').style.display = 'flex';

    // Destroy old DataTable
    if (dtTracker) { dtTracker.destroy(); dtTracker = null; }

    // Render rows
    const tbody = $('tblTrackerBody');
    if (data.data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="state-empty"><i class="fas fa-inbox"></i><p>Tidak ada log pada periode ini.</p></td></tr>`;
      return;
    }

    tbody.innerHTML = data.data.map((row, i) => `
      <tr>
        <td style="color:var(--text-muted);font-weight:600;">${i+1}</td>
        <td style="font-size:12.5px;white-space:nowrap;">${row.created_at}</td>
        <td><code style="font-size:12px;color:var(--p700);font-weight:700;">${row.user}</code></td>
        <td style="font-size:13px;">${row.fullname||'-'}</td>
        <td>${aksiBadge(row.aksi)}</td>
        <td style="font-size:12.5px;color:var(--text-secondary);">${row.keterangan}</td>
        <td style="font-size:11.5px;color:var(--text-muted);">${row.ip}</td>
      </tr>`).join('');

    // Init DataTable
    dtTracker = jQuery('#tblTracker').DataTable({
      paging:    true,
      pageLength: 25,
      lengthMenu: [10,25,50,100],
      ordering:  true,
      searching: true,
      info:      true,
      autoWidth: false,
      order:     [[1,'desc']],
      language: {
        search:             '',
        searchPlaceholder:  'Cari user, keterangan, IP...',
        lengthMenu:         'Tampilkan _MENU_ data',
        info:               'Menampilkan _START_–_END_ dari _TOTAL_ log',
        infoEmpty:          'Tidak ada data',
        infoFiltered:       '(difilter dari _MAX_ data)',
        paginate:           { first:'«', last:'»', next:'›', previous:'‹' },
        emptyTable:         'Tidak ada log.',
        zeroRecords:        'Tidak ditemukan data yang cocok.',
      },
      dom: '<"dt-top"fl>rt<"dt-bottom"ip>',
    });

    showToast(`${data.data.length} log ditemukan.`,'success');
  } catch(e) {
    showToast('Terjadi kesalahan koneksi.','error');
    console.error(e);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-search"></i> Tampilkan';
  }
}

$('btnLoadTracker').addEventListener('click', loadTracker);
$('trStart').addEventListener('change', () => {});
$('trEnd').addEventListener('change', () => {});

// Auto load hari ini
loadTracker();