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
<body class="dash-body page-km"></body>
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
      'icon'  => 'bi-calendar-event',
      'items' => $tahunItems,
      'field' => 'Nama Tahun',
    ],
    [
      'key'   => 'unit',
      'label' => 'Dropdown Unit Kerja',
      'icon'  => 'bi-building',
      'items' => $unitItems,
      'field' => 'Nama Unit Kerja',
    ],
    [
      'key'   => 'status',
      'label' => 'Dropdown Status Pekerjaan',
      'icon'  => 'bi-layers',
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
    <a class="dash-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
       href="{{ route('superadmin.dashboard') }}">
      <span class="ic"><i class="bi bi-grid-fill"></i></span>
      Dashboard
    </a>

    <a class="dash-link {{ request()->routeIs('superadmin.arsip*') ? 'active' : '' }}"
       href="{{ route('superadmin.arsip') }}">
      <span class="ic"><i class="bi bi-archive"></i></span>
      Arsip PBJ
    </a>

    <a class="dash-link {{ request()->routeIs('superadmin.pengadaan.create') ? 'active' : '' }}"
       href="{{ route('superadmin.pengadaan.create') }}">
      <span class="ic"><i class="bi bi-plus-square"></i></span>
      Tambah Pengadaan
    </a>

    <a class="dash-link {{ request()->routeIs('superadmin.kelola.menu') ? 'active' : '' }}"
       href="{{ route('superadmin.kelola.menu') }}">
      <span class="ic"><i class="bi bi-gear-fill"></i></span>
      Kelola Menu
    </a>

    <a class="dash-link {{ request()->routeIs('superadmin.kelola.akun*') ? 'active' : '' }}"
       href="{{ route('superadmin.kelola.akun') }}">
      <span class="ic"><i class="bi bi-person-gear"></i></span>
      Kelola Akun
    </a>
  </nav>

  <div class="dash-side-actions">
    <a class="dash-side-btn" href="{{ route('home') }}">
      <i class="bi bi-house-door"></i>
      Kembali
    </a>

    <a class="dash-side-btn" href="{{ url('/logout') }}">
      <i class="bi bi-box-arrow-right"></i>
      Keluar
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
  --primary:#1f4f5f;
  --primary-dark:#173f4d;
  --bg:#f4f7fa;
  --border:#e5edf3;
}

/* ===== GLOBAL ===== */
body.page-km{
  font-family:'Nunito',sans-serif;
  font-size:16px;
  background:var(--bg);
  margin:0;
}

/* ===== SIDEBAR FIX ===== */
.dash-sidebar{
  position:fixed;
  width:230px;
  height:100vh;
}

.dash-main{
  margin-left:250px;
  padding:50px;
}

/* ===== HEADER ===== */
.km-header h1{
  font-size:28px;
  font-weight:600;
  color:#1f4f5f;
  margin-bottom:4px;
}

body.page-km .km-header{
  margin-bottom: 24px;
}

.km-header p{
  color:#6b7c8f;
  font-size:15px;
}

/* ===== GRID ===== */
.km-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:30px;
}

/* ===== CARD ===== */
.km-section{
  background:#fff;
  border-radius:14px;
  box-shadow:0 6px 18px rgba(0,0,0,0.08);
  overflow:hidden;
}

.page-km .dash-main{
  padding-top: 18px;
}

/* ===== HEADER CARD ===== */
.km-sec-head{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:18px 20px;
}

.km-sec-title{
  display:flex;
  align-items:center;
  gap:10px;
  font-size:17px;
  font-weight:600;
  color:#1f4f5f;
}



/* BUTTON TAMBAH */
.km-add-btn{
  background:#1f4f5f;
  color:#fff;
  border:none;
  padding:8px 14px;
  border-radius:10px;
  font-size:14px;
  cursor:pointer;
}

.km-add-btn:hover{
  background:#173f4d;
}

/* ===== TABLE ===== */
.km-table{
  width:100%;
}

/* HEADER TABLE */
.km-tbl-head{
  display:grid;
  grid-template-columns:60px 2fr 120px 120px;
  background:#1f4f5f;
  color:#fff;
  padding:12px 20px;
  font-size:14px;
}

/* ROW */
.km-tbl-row{
  display:grid;
  grid-template-columns:60px 2fr 120px 120px;
  align-items:center;
  padding:14px 20px;
  border-top:1px solid #eee;
  background:#fff;
}

.km-tbl-row:nth-child(even){
  background:#fafafa;
}

/* TEXT */
.km-col{
  font-size:14px;
}

.km-modal{
  position: fixed !important;
  inset: 0;
  z-index: 9999;
  display: none;
}

.km-modal.is-open{
  display: block;
}

.km-modal-backdrop{
  position: absolute;
  inset: 0;

  /* warna biru kamu (LEBIH PEKAT) */
  background: rgba(29, 83, 108, 0.6) !important;

  /* blur tetap ada */
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.km-modal-panel{
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.km-modal-card{
  width: 420px;
  background: #fff;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
}
/* =========================
   KHUSUS KELOLA MENU (AMAN)
========================= */

/* =========================
   FIX MODAL (MATCH HTML KAMU)
========================= */


/* =========================
   MODAL FINAL (CLEAN UI)
========================= */

.km-modal{
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: none;
}

.km-modal.is-open{
  display: flex;
  align-items: center;
  justify-content: center;
}

/* CARD UTAMA */
.km-modal-card{
  position: relative;
  width: 480px;
  max-width: 92%;
  background: #ffffff;
  border-radius: 16px;
  padding: 22px 24px;
  
  /* SHADOW HALUS BANGET */
  box-shadow:
    0 10px 25px rgba(0,0,0,0.08),
    0 4px 10px rgba(0,0,0,0.05);

  animation: fadeScale 0.18s ease;
}

/* ANIMASI HALUS */
@keyframes fadeScale{
  from{
    opacity:0;
    transform: translateY(10px) scale(0.97);
  }
  to{
    opacity:1;
    transform: translateY(0) scale(1);
  }
}

/* HEADER */
.km-modal-head{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:12px;
}

.km-modal-title{
  font-size:18px;
  font-weight:600;
  color:#1f4f5f;
}

/* GARIS PEMISAH */
.km-modal-head::after{
  content:"";
  position:absolute;
  left:24px;
  right:24px;
  top:60px;
  height:1px;
  background:#e5edf3;
}

/* CLOSE */
.km-modal-close{
  border:none;
  background:none;
  font-size:18px;
  cursor:pointer;
  color:#64748b;
}

/* BODY */
.km-modal-body{
  margin-top:18px;
}

/* LABEL */
.km-label{
  font-size:14px;
  color:#64748b;
  margin-bottom:6px;
  display:block;
}

/* INPUT */
.km-input{
  width:100%;
  border:1px solid #dbe3ea;
  border-radius:10px;
  padding:10px 12px;
  font-size:14px;
  outline:none;
}

.km-input:focus{
  border-color:#1f4f5f;
  box-shadow:0 0 0 2px rgba(31,79,95,0.1);
}

/* FOOTER */
.km-modal-foot{
  display:flex;
  justify-content:center;
  gap:12px;
  margin-top:22px;
}

/* BUTTON */
.km-btn{
  min-width:90px;
  padding:8px 14px;
  border-radius:10px;
  font-size:14px;
  cursor:pointer;
}

/* BATAL */
.km-btn-ghost{
  background:#f1f5f9;
  color:#334155;
  border:1px solid #e2e8f0;
}

/* SIMPAN */
.km-btn-primary{
  background:#1f4f5f;
  color:#fff;
  border:none;
}

/* BALIKIN SISTEM LAYOUT NORMAL */
body.page-km .dash-wrap{
  height: auto !important;
  min-height: 100vh !important;
  overflow: visible !important;
}

body.page-km .dash-main{
  padding: 20px 30px 30px !important;
}

body.page-km .km-header p{
  margin-top: 4px;
  margin-bottom: 0;
}
/* SIDEBAR TETAP STICKY */
body.page-km .dash-sidebar{
  position: sticky !important;
  top: 0;
  height: 100vh;
}

/* BIKIN KONTEN LEBIH LEGA */
body.page-km .dash-main{
  margin-left: 0 !important; /* karena pakai flex */
}

/* GRID CARD */
body.page-km .km-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

/* TABLE FIX BIAR GAK NGE-CRAMP */
body.page-km .km-tbl-head,
body.page-km .km-tbl-row{
  display: grid;
  grid-template-columns: 60px 2fr 120px 120px;
  align-items: center;
}

/* BIAR RAPI */
body.page-km .km-section{
  border-radius: 14px;
  overflow: hidden;
}

/* RESPONSIVE */
@media(max-width:1000px){
  body.page-km .km-grid{
    grid-template-columns: 1fr;
  }
}

/* STATUS BADGE */
.km-badge{
  padding:5px 12px;
  border-radius:8px;
  font-size:12px;
}

.km-badge-aktif{
  background:#c8e6c9;
  color:#2e7d32;
}

.km-badge-nonaktif{
  background:#ffcdd2;
  color:#c62828;
}

/* ===== AKSI ===== */
.km-col-aksi{
  display:flex;
  align-items:center;
  gap:10px;
}

/* ICON */
.km-icbtn{
  background:none;
  border:none;
  cursor:pointer;
  font-size:16px;
  color:#1f4f5f;
}

/* ===== TOGGLE ===== */
.km-toggle{
  width:34px;
  height:18px;
  border-radius:20px;
  background:#ddd;
  position:relative;
  border:none;
  cursor:pointer;
}

.km-toggle.is-on{
  background:#81c784;
}

.km-toggle-knob{
  width:14px;
  height:14px;
  background:#fff;
  border-radius:50%;
  position:absolute;
  top:2px;
  left:2px;
  transition:0.2s;
}

.km-toggle.is-on .km-toggle-knob{
  transform:translateX(16px);
}

/* ===== EMPTY ===== */
.km-empty{
  padding:25px;
  text-align:center;
  color:#9aa7b3;
}

/* ===== RESPONSIVE ===== */
@media(max-width:1000px){
  .km-grid{
    grid-template-columns:1fr;
  }

  .dash-main{
    margin-left:230px;
  }
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