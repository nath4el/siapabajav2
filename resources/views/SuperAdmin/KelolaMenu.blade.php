<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Menu - SIAPABAJA</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
</head>

<body class="dash-body page-km">
@php
  /*
  ┌─────────────────────────────────────────────────────────────┐
  │  Controller wajib kirim 4 collection:                       │
  │  $tahunItems   → id, nama, status ('aktif'/'tidak_aktif')   │
  │  $unitItems    → id, nama, status                           │
  │  $statusItems  → id, nama, status                           │
  │  $jenisItems   → id, nama, status                           │
  └─────────────────────────────────────────────────────────────┘
  */
  $tahunItems  = $tahunItems  ?? collect();
  $unitItems   = $unitItems   ?? collect();
  $statusItems = $statusItems ?? collect();
  $jenisItems  = $jenisItems  ?? collect();

  $sections = [
    [
      'key'   => 'tahun',
      'label' => 'Dropdown Tahun',
      'icon'  => 'bi-calendar-event-fill',
      'items' => $tahunItems,
      'field' => 'Nama Tahun',
    ],
    [
      'key'   => 'unit',
      'label' => 'Dropdown Unit Kerja',
      'icon'  => 'bi-building-fill',
      'items' => $unitItems,
      'field' => 'Nama Unit Kerja',
    ],
    [
      'key'   => 'status',
      'label' => 'Dropdown Status Pekerjaan',
      'icon'  => 'bi-layers-fill',
      'items' => $statusItems,
      'field' => 'Nama Status',
    ],
    [
      'key'   => 'jenis',
      'label' => 'Dropdown Jenis Pengadaan',
      'icon'  => 'bi-list-ul',
      'items' => $jenisItems,
      'field' => 'Nama Jenis',
    ],
  ];

  $toastMessage = session('success') ?? session('updated') ?? null;
@endphp

<div class="dash-wrap">
  {{-- ═══════════ SIDEBAR ═══════════ --}}
  <aside class="dash-sidebar">
    <div class="dash-brand">
      <div class="dash-logo">
        <img src="{{ asset('image/Logo_Unsoed.png') }}" alt="Logo Unsoed">
      </div>
      <div class="dash-text">
        <div class="dash-app">SIAPABAJA</div>
        <div class="dash-role">Super Admin</div>
      </div>
    </div>

    <nav class="dash-nav">
      <a class="dash-link" href="{{ route('superadmin.dashboard') }}">
        <span class="ic"><i class="bi bi-grid-fill"></i></span> Dashboard
      </a>
      <a class="dash-link" href="{{ route('superadmin.arsip') }}">
        <span class="ic"><i class="bi bi-archive-fill"></i></span> Arsip PBJ
      </a>
      <a class="dash-link" href="{{ route('superadmin.pengadaan.create') }}">
        <span class="ic"><i class="bi bi-plus-square-fill"></i></span> Tambah Pengadaan
      </a>
      <a class="dash-link active" href="{{ route('superadmin.kelola.menu') }}">
        <span class="ic"><i class="bi bi-gear-fill"></i></span> Kelola Menu
      </a>
      <a class="dash-link {{ request()->routeIs('superadmin.kelola.akun') ? 'active' : '' }}" href="{{ route('superadmin.kelola.akun') }}">
        <span class="ic"><i class="bi bi-person-gear"></i></span> Kelola Akun
        <i class="bi bi-chevron-right dash-link-chevron"></i>
      </a>
    </nav>

    <div class="dash-side-actions">
      <a class="dash-side-btn" href="{{ route('home') }}">
        <i class="bi bi-house-door"></i>Kembali
      </a>
      <a class="dash-side-btn" href="{{ url('/logout') }}">
        <i class="bi bi-box-arrow-right"></i>Keluar
      </a>
    </div>
  </aside>

  {{-- ═══════════ MAIN ═══════════ --}}
  <main class="dash-main">
    <header class="km-header">
      <h1>Kelola Menu</h1>
      <p>Kelola seluruh menu dropdown</p>
    </header>

    @if(!empty($toastMessage))
      <div class="nt-wrap" id="ntWrap">
        <div class="nt-toast nt-success" id="ntToast">
          <div class="nt-ic"><i class="bi bi-check2-circle"></i></div>
          <div class="nt-content">
            <div class="nt-title">Berhasil</div>
            <div class="nt-desc">{{ $toastMessage }}</div>
          </div>
          <button class="nt-close" id="ntCloseBtn"><i class="bi bi-x-lg"></i></button>
          <div class="nt-bar"></div>
        </div>
      </div>
    @endif

    {{-- ── 4 SECTION GRID ── --}}
    <div class="km-grid">
      @foreach($sections as $sec)
      <div class="km-section" id="sec-{{ $sec['key'] }}">

        {{-- Section header --}}
        <div class="km-sec-head">
          <div class="km-sec-title">
            <i class="bi {{ $sec['icon'] }}"></i>
            {{ $sec['label'] }}
          </div>
          <button
            type="button"
            class="km-add-btn"
            onclick="openAdd('{{ $sec['key'] }}', '{{ $sec['label'] }}', '{{ $sec['field'] }}')"
          >
            <i class="bi bi-plus-lg"></i> Tambah
          </button>
        </div>

        {{-- Table --}}
        <div class="km-table">
          <div class="km-tbl-head">
            <div class="km-col km-col-no">No</div>
            <div class="km-col km-col-nama">Nama</div>
            <div class="km-col km-col-status">Status</div>
            <div class="km-col km-col-aksi">Aksi</div>
          </div>

          <div class="km-tbl-body" id="body-{{ $sec['key'] }}">
            @forelse($sec['items'] as $idx => $item)
              @php
                $isArr    = is_array($item);
                $id       = $isArr ? ($item['id'] ?? '') : ($item->id ?? '');
                $nama     = $isArr ? ($item['nama'] ?? '') : ($item->nama ?? '');
                $status   = strtolower($isArr ? ($item['status'] ?? 'aktif') : ($item->status ?? 'aktif'));
                $isAktif  = $status === 'aktif';
              @endphp
              <div class="km-tbl-row" data-id="{{ $id }}" data-type="{{ $sec['key'] }}">
                <div class="km-col km-col-no">{{ $idx + 1 }}</div>
                <div class="km-col km-col-nama km-nama-val">{{ $nama }}</div>
                <div class="km-col km-col-status">
                  <span class="km-badge {{ $isAktif ? 'km-badge-aktif' : 'km-badge-nonaktif' }}">
                    {{ $isAktif ? 'Aktif' : 'Tidak Aktif' }}
                  </span>
                </div>
                <div class="km-col km-col-aksi">
                  <button type="button" class="km-icbtn km-icbtn-edit"
                    title="Edit"
                    onclick="openEdit('{{ $sec['key'] }}','{{ $sec['label'] }}','{{ $sec['field'] }}','{{ $id }}','{{ addslashes($nama) }}')">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button type="button" class="km-icbtn km-icbtn-del"
                    title="Hapus"
                    onclick="openDelete('{{ $sec['key'] }}','{{ $id }}','{{ addslashes($nama) }}')">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                  <button type="button"
                    class="km-toggle {{ $isAktif ? 'is-on' : '' }}"
                    title="Toggle Status"
                    data-id="{{ $id }}"
                    data-type="{{ $sec['key'] }}"
                    onclick="toggleStatus(this)">
                    <span class="km-toggle-knob"></span>
                  </button>
                </div>
              </div>
            @empty
              <div class="km-empty" id="empty-{{ $sec['key'] }}">Belum ada data.</div>
            @endforelse
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </main>
</div>

{{-- ═══════════ MODAL TAMBAH / EDIT ═══════════ --}}
<div class="km-modal" id="formModal" aria-hidden="true">
  <div class="km-modal-backdrop" onclick="closeFormModal()"></div>
  <div class="km-modal-panel" role="dialog" aria-modal="true">
    <div class="km-modal-card">

      <div class="km-modal-head">
        <div class="km-modal-title" id="formModalTitle">Tambah</div>
        <button type="button" class="km-modal-close" onclick="closeFormModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="km-modal-body">
        <div class="km-field">
          <label class="km-label" id="formFieldLabel">Nama</label>
          <input type="text" class="km-input" id="formInput" placeholder="" autocomplete="off" />
          <div class="km-field-err" id="formErr" hidden></div>
        </div>
      </div>

      <div class="km-modal-foot">
        <button type="button" class="km-btn km-btn-ghost" onclick="closeFormModal()">Batal</button>
        <button type="button" class="km-btn km-btn-primary" id="formSaveBtn" onclick="saveForm()">
          <span id="formSaveTxt">Simpan</span>
          <span id="formSaveLoader" hidden class="km-btn-loader"></span>
        </button>
      </div>

    </div>
  </div>
</div>

{{-- ═══════════ MODAL HAPUS ═══════════ --}}
<div class="km-modal" id="delModal" aria-hidden="true">
  <div class="km-modal-backdrop" onclick="closeDelModal()"></div>
  <div class="km-modal-panel km-modal-panel-sm" role="dialog" aria-modal="true">
    <div class="km-modal-card">

      <div class="km-modal-head">
        <div class="km-del-badge"><i class="bi bi-shield-exclamation"></i></div>
        <button type="button" class="km-modal-close" onclick="closeDelModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="km-modal-body">
        <div class="km-del-title">Konfirmasi Hapus</div>
        <div class="km-del-desc">
          Yakin ingin menghapus <strong id="delItemName">item</strong> ini?
          Tindakan ini tidak dapat dibatalkan.
        </div>
      </div>

      <div class="km-modal-foot">
        <button type="button" class="km-btn km-btn-ghost" onclick="closeDelModal()">Batal</button>
        <button type="button" class="km-btn km-btn-danger" id="delConfirmBtn" onclick="confirmDelete()">
          <i class="bi bi-trash3"></i>
          <span id="delTxt">Ya, Hapus</span>
          <span id="delLoader" hidden class="km-btn-loader"></span>
        </button>
      </div>

    </div>
  </div>
</div>

{{-- ═══════════ TOAST JS ═══════════ --}}
<div class="nt-wrap" id="jsToastWrap" style="display:none;position:fixed;top:18px;right:18px;z-index:11000;">
  <div class="nt-toast" id="jsToast">
    <div class="nt-ic" id="jsToastIc"><i class="bi bi-check2-circle"></i></div>
    <div class="nt-content">
      <div class="nt-title" id="jsToastTitle">Berhasil</div>
      <div class="nt-desc"  id="jsToastDesc">-</div>
    </div>
    <button class="nt-close" onclick="hideJsToast()"><i class="bi bi-x-lg"></i></button>
    <div class="nt-bar" id="jsToastBar"></div>
  </div>
</div>

{{-- ═══════════ STYLES ═══════════ --}}
<style>
:root{
  --brand:#184f61;
  --brand-dark:#0e3d4e;
  --brand-light:rgba(24,79,97,.10);
  --yellow:#f6c100;
  --yellow-dark:#d9aa00;
  --sidebar-txt:rgba(255,255,255,.78);
  --border:#e8eef3;
  --radius:14px;
  --shadow:0 4px 18px rgba(15,23,42,.10);
}

/* ── Global font weight 500 ── */
body.page-km,
body.page-km *{
  font-weight:700 !important;
}
body.page-km .km-btn-loader{
  font-weight:400 !important;
  width:16px !important;
  height:16px !important;
  border:2px solid rgba(255,255,255,.4) !important;
  border-top-color:#fff !important;
  border-radius:50% !important;
  display:inline-block !important;
  animation:spin .6s linear infinite !important;
}

/* ── Layout ── */
*,*::before,*::after{box-sizing:border-box;}
body.page-km{
  font-family:'Nunito',sans-serif;
  font-size:15px;
  background:#f4f7fa;
  margin:0;
}
.dash-wrap{display:flex;min-height:100vh;}

/* ── Main ── */
.dash-main{
  flex:1;
  min-width:0;
  padding:28px 28px 48px;
  display:flex;
  flex-direction:column;
  gap:22px;
}

/* ── Page Header ── */
.km-header {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.km-header h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 600;
    color: #184f61;
}

.km-header p {
    margin: 0;
    font-size: 15px;
    color: #64748b;
    font-weight: 400;
}


/* ── 2-col grid ── */
.km-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;}

/* ── Section card ── */
.km-section{
  background:#fff;
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
}

.km-sec-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:16px 20px;
  gap:12px;
}
.km-sec-title{
  display:flex;
  align-items:center;
  gap:9px;
  font-size:16px;
  color:var(--brand);
}
.km-sec-title i{font-size:18px;}

.km-add-btn{
  height:36px;
  padding:0 14px;
  border-radius:10px;
  border:none;
  background:var(--brand);
  color:#fff;
  font-size:13px;
  font-family:'Nunito',sans-serif;
  display:inline-flex;
  align-items:center;
  gap:6px;
  cursor:pointer;
  transition:.15s;
  white-space:nowrap;
}
.km-add-btn:hover{background:var(--brand-dark);}
.km-add-btn i{font-size:13px;}

/* ── Table ── */
.km-table{border-top:1px solid var(--border);}

.km-tbl-head,
.km-tbl-row{
  display:grid;
  grid-template-columns:48px 1fr 110px 120px;
  align-items:center;
  column-gap:12px;
  padding:0 20px;
}
.km-tbl-head{
  background:var(--brand);
  min-height:44px;
}
.km-tbl-head .km-col{
  color:#fff;
  font-size:13px;
  letter-spacing:.3px;
  white-space:nowrap;
  text-align:left;
}

.km-tbl-row{
  min-height:58px;
  border-top:1px solid var(--border);
  transition:background .12s;
}
.km-tbl-row:hover{background:#f8fbfe;}

.km-col{
  font-size:14px;
  color:#1e293b;
  min-width:0;
  overflow-wrap:anywhere;
  text-align:left;
}
.km-col-no{
  text-align:left;
  color:#94a3b8;
  font-size:13px;
}
.km-col-nama{
  color:var(--brand);
  line-height:1.4;
}
.km-col-status{display:flex;align-items:center;}
.km-col-aksi{display:flex;align-items:center;gap:7px;justify-content:flex-end;}

/* ── Status badge ── */
.km-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:28px;
  padding:0 12px;
  border-radius:8px;
  font-size:12.5px;
  white-space:nowrap;
}
.km-badge-aktif{background:#dcfce7;color:#15803d;}
.km-badge-nonaktif{background:#fee2e2;color:#b91c1c;}

/* ── Icon buttons ── */
.km-icbtn{
  width:32px;
  height:32px;
  border-radius:9px;
  border:1px solid var(--border);
  background:#f8fafc;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  font-size:14px;
  color:#374151;
  transition:.15s;
  padding:0;
}
.km-icbtn:hover{transform:translateY(-1px);}
.km-icbtn-edit:hover{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;}
.km-icbtn-del:hover{background:#fef2f2;border-color:#fecaca;color:#dc2626;}

/* ── Toggle switch ── */
.km-toggle{
  position:relative;
  width:44px;
  height:24px;
  border-radius:999px;
  border:none;
  background:#e2e8f0;
  cursor:pointer;
  transition:background .2s;
  padding:0;
  flex:0 0 auto;
}
.km-toggle.is-on{background:#22c55e;}
.km-toggle-knob{
  position:absolute;
  top:3px;
  left:3px;
  width:18px;
  height:18px;
  border-radius:50%;
  background:#fff;
  box-shadow:0 1px 4px rgba(0,0,0,.15);
  transition:transform .2s;
  display:block;
}
.km-toggle.is-on .km-toggle-knob{transform:translateX(20px);}

/* ── Empty state ── */
.km-empty{
  padding:28px;
  text-align:center;
  color:#94a3b8;
  font-size:13.5px;
  border-top:1px solid var(--border);
}

/* ── Modal ── */
.km-modal{
  position:fixed;
  inset:0;
  z-index:9500;
  display:none;
  align-items:center;
  justify-content:center;
  padding:16px;
}
.km-modal.is-open{display:flex;}
.km-modal-backdrop{
  position:fixed;
  inset:0;
  background:rgba(15,23,42,.38);
  backdrop-filter:blur(10px);
  -webkit-backdrop-filter:blur(10px);
}
.km-modal-panel{
  position:relative;
  z-index:1;
  width:min(480px,96vw);
  background:#fff;
  border-radius:20px;
  overflow:hidden;
  box-shadow:0 24px 64px rgba(2,6,23,.22);
  animation:kmPop .2s ease;
}
.km-modal-panel-sm{width:min(420px,96vw);}
@keyframes kmPop{
  from{opacity:0;transform:scale(.96);}
  to{opacity:1;transform:scale(1);}
}

.km-modal-card{display:flex;flex-direction:column;}

.km-modal-head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  padding:20px 20px 0;
  gap:12px;
}
.km-modal-title{font-size:18px;color:#0f172a;}
.km-modal-close{
  width:36px;
  height:36px;
  border-radius:11px;
  border:1px solid var(--border);
  background:#f8fafc;
  display:grid;
  place-items:center;
  cursor:pointer;
  font-size:15px;
  color:#475569;
  transition:.15s;
  padding:0;
  margin-top:-2px;
}
.km-modal-close:hover{background:#f1f5f9;}

.km-del-badge{
  width:52px;
  height:52px;
  border-radius:16px;
  display:grid;
  place-items:center;
  background:#fef3c7;
  border:1px solid #fde68a;
  font-size:24px;
  color:#d97706;
}
.km-del-title{
  font-size:18px;
  color:#0f172a;
  margin-bottom:6px;
}
.km-del-desc{
  font-size:13.5px;
  color:#475569;
  line-height:1.6;
}

.km-modal-body{padding:16px 20px 8px;}

.km-field{display:flex;flex-direction:column;gap:6px;}
.km-label{font-size:13px;color:#374151;}
.km-input{
  height:42px;
  width:100%;
  border:1.5px solid var(--border);
  border-radius:10px;
  padding:0 14px;
  font-size:14px;
  font-family:'Nunito',sans-serif;
  color:#0f172a;
  background:#fff;
  outline:none;
  transition:.15s;
}
.km-input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(24,79,97,.12);}
.km-field-err{font-size:12.5px;color:#dc2626;margin-top:2px;}

.km-modal-foot{
  display:flex;
  align-items:center;
  justify-content:flex-end;
  gap:8px;
  padding:12px 20px 20px;
}

.km-btn{
  height:40px;
  padding:0 18px;
  border-radius:12px;
  border:1px solid transparent;
  font-size:14px;
  font-family:'Nunito',sans-serif;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  transition:.15s;
  white-space:nowrap;
}
.km-btn-ghost{background:#fff;border-color:var(--border);color:#374151;}
.km-btn-ghost:hover{background:#f1f5f9;}
.km-btn-primary{background:var(--brand);color:#fff;}
.km-btn-primary:hover{background:var(--brand-dark);}
.km-btn-danger{background:#ef4444;color:#fff;}
.km-btn-danger:hover{background:#dc2626;}
.km-btn:disabled{opacity:.6;cursor:not-allowed;}

.km-btn-loader{
  width:16px;
  height:16px;
  border:2px solid rgba(255,255,255,.4);
  border-top-color:#fff;
  border-radius:50%;
  animation:spin .6s linear infinite;
  display:inline-block;
}
@keyframes spin{to{transform:rotate(360deg);}}

/* ── Toast ── */
.nt-wrap{pointer-events:none;}
.nt-toast{
  width:min(360px,calc(100vw - 36px));
  background:#fff;
  border:1px solid var(--border);
  border-radius:16px;
  box-shadow:0 14px 30px rgba(2,8,23,.12);
  padding:12px 14px;
  display:flex;
  gap:10px;
  align-items:flex-start;
  position:relative;
  overflow:hidden;
  pointer-events:auto;
}
.nt-success{border-left:4px solid #22c55e;}
.nt-error{border-left:4px solid #ef4444;}
.nt-ic{
  width:36px;
  height:36px;
  border-radius:11px;
  display:grid;
  place-items:center;
  background:#ecfdf3;
  border:1px solid #d8f5e3;
  color:#16a34a;
  flex:0 0 auto;
}
.nt-error .nt-ic{background:#fef2f2;border-color:#fecaca;color:#dc2626;}
.nt-title{font-size:14px;color:#0f172a;}
.nt-desc{font-size:12.5px;color:#475569;margin-top:2px;line-height:1.5;}
.nt-close{
  margin-left:auto;
  width:30px;
  height:30px;
  border-radius:9px;
  border:1px solid #eef2f7;
  background:#fff;
  display:grid;
  place-items:center;
  padding:0;
  cursor:pointer;
}
.nt-bar{
  position:absolute;
  left:0;
  bottom:0;
  height:3px;
  width:100%;
  background:linear-gradient(90deg,#22c55e,#16a34a);
  animation:ntbar 4s linear forwards;
}
@keyframes ntbar{
  from{width:100%;}
  to{width:0%;}
}

@media(max-width:1000px){
  .km-grid{grid-template-columns:1fr;}
}
</style>

{{-- ═══════════ SCRIPTS ═══════════ --}}
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ─── State ─── */
let _formMode   = 'add';   // 'add' | 'edit'
let _formType   = '';
let _formId     = null;
let _formField  = '';
let _formLabel  = '';
let _delType    = '';
let _delId      = null;

/* ─── Helpers ─── */
function apiFetch(url, method, body){
  return fetch(url, {
    method,
    headers:{
      'Content-Type':'application/json',
      'Accept':'application/json',
      'X-CSRF-TOKEN': CSRF,
      'X-Requested-With':'XMLHttpRequest'
    },
    body: body ? JSON.stringify(body) : undefined
  });
}

function showToast(msg, isError = false){
  const wrap  = document.getElementById('jsToastWrap');
  const toast = document.getElementById('jsToast');
  const ic    = document.getElementById('jsToastIc');
  const title = document.getElementById('jsToastTitle');
  const desc  = document.getElementById('jsToastDesc');
  const bar   = document.getElementById('jsToastBar');

  toast.className = 'nt-toast ' + (isError ? 'nt-error' : 'nt-success');
  ic.innerHTML    = isError
    ? '<i class="bi bi-exclamation-circle"></i>'
    : '<i class="bi bi-check2-circle"></i>';
  title.textContent = isError ? 'Gagal' : 'Berhasil';
  desc.textContent  = msg;
  wrap.style.display = '';

  // restart bar animation
  bar.style.animation = 'none';
  bar.offsetHeight;
  bar.style.animation = '';

  clearTimeout(wrap._t);
  wrap._t = setTimeout(() => hideJsToast(), 4000);
}

function hideJsToast(){
  document.getElementById('jsToastWrap').style.display = 'none';
}

/* ─── Row numbering ─── */
function renumberBody(type){
  const body = document.getElementById('body-' + type);
  if(!body) return;
  const rows = body.querySelectorAll('.km-tbl-row');
  rows.forEach((r, i) => {
    const noEl = r.querySelector('.km-col-no');
    if(noEl) noEl.textContent = i + 1;
  });
  const empty = document.getElementById('empty-' + type);
  if(empty) empty.hidden = rows.length > 0;
}

/* ─── FORM MODAL ─── */
function openAdd(type, label, field){
  _formMode  = 'add';
  _formType  = type;
  _formField = field;
  _formLabel = label;
  _formId    = null;

  document.getElementById('formModalTitle').textContent = 'Tambah ' + label.replace('Dropdown ','');
  document.getElementById('formFieldLabel').textContent = field;
  document.getElementById('formInput').value = '';
  document.getElementById('formSaveTxt').textContent = 'Simpan';
  document.getElementById('formErr').hidden = true;

  openModal('formModal');
  setTimeout(() => document.getElementById('formInput').focus(), 120);
}

function openEdit(type, label, field, id, nama){
  _formMode  = 'edit';
  _formType  = type;
  _formField = field;
  _formLabel = label;
  _formId    = id;

  document.getElementById('formModalTitle').textContent = 'Edit ' + label.replace('Dropdown ','');
  document.getElementById('formFieldLabel').textContent = field;
  document.getElementById('formInput').value = nama;
  document.getElementById('formSaveTxt').textContent = 'Simpan';
  document.getElementById('formErr').hidden = true;

  openModal('formModal');
  setTimeout(() => document.getElementById('formInput').focus(), 120);
}

function closeFormModal(){ closeModal('formModal'); }

async function saveForm(){
  const input = document.getElementById('formInput');
  const nama  = input.value.trim();
  const errEl = document.getElementById('formErr');

  if(!nama){
    errEl.textContent = 'Nama tidak boleh kosong.';
    errEl.hidden = false;
    input.focus();
    return;
  }
  errEl.hidden = true;

  const saveBtn    = document.getElementById('formSaveBtn');
  const saveTxt    = document.getElementById('formSaveTxt');
  const saveLoader = document.getElementById('formSaveLoader');
  saveBtn.disabled = true;
  saveTxt.hidden   = true;
  saveLoader.hidden= false;

  try {
    let url, method;
    if(_formMode === 'add'){
      url    = `/super-admin/kelola-menu/${_formType}`;
      method = 'POST';
    } else {
      url    = `/super-admin/kelola-menu/${_formType}/${_formId}`;
      method = 'PUT';
    }

    const res  = await apiFetch(url, method, { nama });
    const json = await res.json();

    if(!res.ok) throw new Error(json.message || 'Gagal menyimpan data.');

    closeFormModal();

    if(_formMode === 'add'){
      appendRow(_formType, json.data || json);
      showToast(`Data berhasil ditambahkan.`);
    } else {
      updateRow(_formType, _formId, nama);
      showToast(`Data berhasil diperbarui.`);
    }
  } catch(err){
    errEl.textContent = err.message;
    errEl.hidden = false;
    showToast(err.message, true);
  } finally {
    saveBtn.disabled = false;
    saveTxt.hidden   = false;
    saveLoader.hidden= true;
  }
}

/* ─── DOM helpers ─── */
function appendRow(type, item){
  const body  = document.getElementById('body-' + type);
  const empty = body?.querySelector('.km-empty');
  if(empty) empty.hidden = true;

  const id     = item.id;
  const nama   = item.nama;
  const aktif  = (item.status || 'aktif').toLowerCase() === 'aktif';
  const count  = body.querySelectorAll('.km-tbl-row').length + 1;

  // find the section to get field/label
  const secEl = document.getElementById('sec-' + type);
  const addBtn = secEl?.querySelector('.km-add-btn');
  // retrieve field/label from onclick attr
  const onclickStr = addBtn?.getAttribute('onclick') || '';
  const match = onclickStr.match(/openAdd\('([^']+)','([^']+)','([^']+)'\)/);
  const label = match ? match[2] : type;
  const field = match ? match[3] : 'Nama';

  const row = document.createElement('div');
  row.className = 'km-tbl-row';
  row.dataset.id   = id;
  row.dataset.type = type;
  row.innerHTML = `
    <div class="km-col km-col-no">${count}</div>
    <div class="km-col km-col-nama km-nama-val">${escHtml(nama)}</div>
    <div class="km-col km-col-status">
      <span class="km-badge km-badge-aktif">Aktif</span>
    </div>
    <div class="km-col km-col-aksi">
      <button type="button" class="km-icbtn km-icbtn-edit" title="Edit"
        onclick="openEdit('${type}','${escAttr(label)}','${escAttr(field)}','${id}','${escAttr(nama)}')">
        <i class="bi bi-pencil-fill"></i>
      </button>
      <button type="button" class="km-icbtn km-icbtn-del" title="Hapus"
        onclick="openDelete('${type}','${id}','${escAttr(nama)}')">
        <i class="bi bi-trash3-fill"></i>
      </button>
      <button type="button" class="km-toggle ${aktif ? 'is-on' : ''}"
        title="Toggle Status" data-id="${id}" data-type="${type}" onclick="toggleStatus(this)">
        <span class="km-toggle-knob"></span>
      </button>
    </div>
  `;
  body.appendChild(row);
}

function updateRow(type, id, nama){
  const body = document.getElementById('body-' + type);
  const row  = body?.querySelector(`.km-tbl-row[data-id="${id}"]`);
  if(!row) return;
  const nameEl = row.querySelector('.km-nama-val');
  if(nameEl) nameEl.textContent = nama;

  // update onclick attrs
  const editBtn = row.querySelector('.km-icbtn-edit');
  const delBtn  = row.querySelector('.km-icbtn-del');
  if(editBtn){
    const old = editBtn.getAttribute('onclick');
    const updated = old.replace(/,'[^']*'\)$/, `,'${escAttr(nama)}')`);
    editBtn.setAttribute('onclick', updated);
  }
  if(delBtn){
    const old = delBtn.getAttribute('onclick');
    const updated = old.replace(/,'[^']*'\)$/, `,'${escAttr(nama)}')`);
    delBtn.setAttribute('onclick', updated);
  }
}

function removeRow(type, id){
  const body = document.getElementById('body-' + type);
  const row  = body?.querySelector(`.km-tbl-row[data-id="${id}"]`);
  row?.remove();
  renumberBody(type);
}

/* ─── DELETE MODAL ─── */
function openDelete(type, id, nama){
  _delType = type;
  _delId   = id;
  document.getElementById('delItemName').textContent = nama;
  openModal('delModal');
}

function closeDelModal(){ closeModal('delModal'); }

async function confirmDelete(){
  const btn    = document.getElementById('delConfirmBtn');
  const txt    = document.getElementById('delTxt');
  const loader = document.getElementById('delLoader');
  btn.disabled = true;
  txt.hidden   = true;
  loader.hidden= false;

  try {
    const res  = await apiFetch(`/super-admin/kelola-menu/${_delType}/${_delId}`, 'DELETE');
    const json = await res.json();
    if(!res.ok) throw new Error(json.message || 'Gagal menghapus.');
    closeDelModal();
    removeRow(_delType, _delId);
    showToast('Data berhasil dihapus.');
  } catch(err){
    showToast(err.message, true);
    closeDelModal();
  } finally {
    btn.disabled = false;
    txt.hidden   = false;
    loader.hidden= true;
  }
}

/* ─── TOGGLE ─── */
async function toggleStatus(btn){
  btn.disabled = true;
  const id   = btn.dataset.id;
  const type = btn.dataset.type;
  const isOn = btn.classList.contains('is-on');
  const newStatus = isOn ? 'tidak_aktif' : 'aktif';

  try {
    const res  = await apiFetch(`/super-admin/kelola-menu/${type}/${id}/toggle`, 'PATCH', { status: newStatus });
    const json = await res.json();
    if(!res.ok) throw new Error(json.message || 'Gagal mengubah status.');

    btn.classList.toggle('is-on', !isOn);

    // update badge in same row
    const row   = btn.closest('.km-tbl-row');
    const badge = row?.querySelector('.km-badge');
    if(badge){
      badge.className = 'km-badge ' + (!isOn ? 'km-badge-aktif' : 'km-badge-nonaktif');
      badge.textContent = !isOn ? 'Aktif' : 'Tidak Aktif';
    }

    showToast(`Status berhasil diubah menjadi ${!isOn ? 'Aktif' : 'Tidak Aktif'}.`);
  } catch(err){
    showToast(err.message, true);
  } finally {
    btn.disabled = false;
  }
}

/* ─── Modal open/close ─── */
function openModal(id){
  const m = document.getElementById(id);
  if(!m) return;
  m.classList.add('is-open');
  m.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function closeModal(id){
  const m = document.getElementById(id);
  if(!m) return;
  m.classList.remove('is-open');
  m.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}

/* ─── Keyboard: Enter to save, Esc to close ─── */
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape'){
    if(document.getElementById('formModal')?.classList.contains('is-open')) closeFormModal();
    else if(document.getElementById('delModal')?.classList.contains('is-open')) closeDelModal();
  }
  if(e.key === 'Enter' && document.getElementById('formModal')?.classList.contains('is-open')){
    const active = document.activeElement;
    if(active && active.id === 'formInput') saveForm();
  }
});

/* ─── Escape empty els on load ─── */
document.addEventListener('DOMContentLoaded', function(){
  ['tahun','unit','status','jenis'].forEach(type => {
    const body = document.getElementById('body-' + type);
    if(!body) return;
    const rows = body.querySelectorAll('.km-tbl-row');
    const empty = document.getElementById('empty-' + type);
    if(empty) empty.hidden = rows.length > 0;
  });

  // blade toast auto-close
  const nt = document.getElementById('ntToast');
  if(nt){
    document.getElementById('ntCloseBtn')?.addEventListener('click', () => nt.parentElement?.remove());
    setTimeout(() => nt.parentElement?.remove(), 4000);
  }
});

/* ─── XSS helpers ─── */
function escHtml(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function escAttr(s){ return (s||'').replace(/'/g,"\\'"); }
</script>

</body>
</html>