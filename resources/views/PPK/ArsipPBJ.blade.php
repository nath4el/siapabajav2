{{-- resources/views/PPK/ArsipPBJ.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Arsip PBJ - SIAPABAJA</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>

<body class="dash-body page-arsip">
@php
  if (!isset($arsips) || !($arsips instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) {
    throw new \RuntimeException('Variable $arsips (paginator) tidak dikirim dari controller.');
  }

  $unitName = auth()->user()->name ?? "PPK";

  $initialQ      = (string) request()->query('q', '');
  $initialUnit   = (string) request()->query('unit', 'Semua');
  $initialStatus = (string) request()->query('status', 'Semua');
  $initialTahun  = (string) request()->query('tahun', 'Semua');

  $initialSortNilai = request()->query('sort_nilai');
  $initialSortNilai = is_null($initialSortNilai) ? null : strtolower(trim((string)$initialSortNilai));
  if ($initialSortNilai !== null && !in_array($initialSortNilai, ['asc','desc'], true)) {
    $initialSortNilai = null;
  }

  $initialUnitNorm   = trim(mb_strtolower($initialUnit, 'UTF-8'));
  $initialStatusNorm = trim(mb_strtolower($initialStatus, 'UTF-8'));
  $initialTahunNorm  = trim((string)$initialTahun);

  $rows = collect($arsips->items())->map(function($item) use ($unitName){
    $r = is_array($item) ? $item : (method_exists($item, 'toArray') ? $item->toArray() : (array) $item);

    // doc note dari dokumen_tidak_dipersyaratkan
    $rawE = $r["dokumen_tidak_dipersyaratkan"] ?? null;
    $docNote = null;
    if (is_array($rawE) && count($rawE) > 0) {
      $docNote = implode(', ', array_map(fn($x) => is_string($x) ? $x : json_encode($x), $rawE));
    } elseif (is_string($rawE) && trim($rawE) !== '') {
      $docNote = trim($rawE);
    }

    // dokumen
    $dokumen = $r["dokumen"] ?? [];
    if (is_string($dokumen)) {
      $decoded = json_decode($dokumen, true);
      $dokumen = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
    if (empty($dokumen)) $dokumen = [];

    return [
      "id"               => $r["id"] ?? null,
      "tahun"            => (string)($r["tahun"] ?? ""),
      "unit"             => $r["unit"] ?? ($r["nama_unit"] ?? ($r["unit_kerja"] ?? $unitName)),
      "pekerjaan"        => $r["pekerjaan"] ?? ($r["nama_pekerjaan"] ?? ($r["judul"] ?? "-")),
      "metode_pbj"       => $r["metode_pbj"] ?? ($r["jenis_pengadaan"] ?? ($r["metode"] ?? "-")),
      "nilai_kontrak"    => $r["nilai_kontrak"] ?? ($r["kontrak"] ?? ($r["nilai"] ?? "-")),
      "status_arsip"     => $r["status_arsip"] ?? "-",
      "status_pekerjaan" => $r["status_pekerjaan"] ?? ($r["status"] ?? "-"),
      "idrup"            => $r["idrup"] ?? ($r["id_rup"] ?? "-"),
      "rekanan"          => $r["rekanan"] ?? ($r["nama_rekanan"] ?? "-"),
      "jenis"            => $r["jenis"] ?? ($r["jenis_pengadaan"] ?? "-"),
      "pagu"             => $r["pagu"] ?? ($r["pagu_anggaran"] ?? "-"),
      "hps"              => $r["hps"] ?? "-",
      "dokumen"          => $dokumen,
      "doc_note"         => $docNote,
    ];
  })->values()->all();

  if (!isset($years) || !is_array($years) || count($years) === 0) {
    $years = array_values(array_unique(array_filter(array_map(fn($x) => $x['tahun'], $rows))));
    rsort($years);
  } else {
    $years = array_values(array_unique(array_map(fn($t) => (string)$t, $years)));
    rsort($years);
  }

  if (!isset($unitOptions) || !is_array($unitOptions) || count($unitOptions) === 0) {
    $unitOptions = array_values(array_unique(array_filter(array_map(fn($x) => $x['unit'], $rows))));
    sort($unitOptions);
  } else {
    $unitOptions = array_values(array_unique(array_map(fn($u) => (string)$u, $unitOptions)));
    sort($unitOptions);
  }

  $deleteUrlTemplate = route('ppk.arsip.delete', ['id' => '__ID__']);
  $qs = request()->except('page');

  $toastMessage = session('success')
    ?? session('updated')
    ?? session('edited')
    ?? (request()->query('edited') ? 'Arsip berhasil diedit.' : null)
    ?? (request()->query('deleted') ? 'Arsip berhasil dihapus.' : null);
@endphp

<div class="dash-wrap">

  {{-- SIDEBAR --}}
  <aside class="dash-sidebar">
    <div class="dash-brand">
      <div class="dash-logo">
        <img src="{{ asset('image/Logo_Unsoed.png') }}" alt="Logo Unsoed">
      </div>
      <div class="dash-text">
        <div class="dash-app">SIAPABAJA</div>
        <div class="dash-role">ADMIN (PPK)</div>
      </div>
    </div>
    <nav class="dash-nav">
      <a class="dash-link {{ request()->routeIs('ppk.dashboard') ? 'active' : '' }}" href="{{ route('ppk.dashboard') }}">
        <span class="ic"><i class="bi bi-grid-fill"></i></span>Dashboard
      </a>
      <a class="dash-link {{ request()->routeIs('ppk.arsip*') ? 'active' : '' }}" href="{{ route('ppk.arsip') }}">
        <span class="ic"><i class="bi bi-archive"></i></span>Arsip PBJ
      </a>
      <a class="dash-link {{ request()->routeIs('ppk.pengadaan.create') ? 'active' : '' }}" href="{{ route('ppk.pengadaan.create') }}">
        <span class="ic"><i class="bi bi-plus-square"></i></span>Tambah Pengadaan
      </a>
      <a class="dash-link {{ request()->routeIs('ppk.kelola.akun') ? 'active' : '' }}" href="{{ route('ppk.kelola.akun') }}">
        <span class="ic"><i class="bi bi-person-gear"></i></span>Kelola Akun
      </a>
    </nav>
    <div class="dash-side-actions">
      <a class="dash-side-btn" href="{{ route('home') }}"><i class="bi bi-house-door"></i> Kembali</a>
      <a class="dash-side-btn" href="{{ url('/logout') }}"><i class="bi bi-box-arrow-right"></i> Keluar</a>
    </div>
  </aside>

  {{-- MAIN --}}
  <main class="dash-main">

    {{-- Header --}}
    <header class="ap-header">
      <div class="ap-header-left">
        <h1>Arsip PBJ</h1>
        <p>Kelola arsip pengadaan barang dan jasa Universitas Jenderal Soedirman</p>
      </div>
      <div class="ap-header-right">
        <button class="ap-export-btn" id="apExportBtn">
          <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
        </button>
      </div>
    </header>

    {{-- Toast --}}
    @if(!empty($toastMessage))
      <div class="nt-wrap" id="ntWrap" aria-live="polite" aria-atomic="true">
        <div class="nt-toast nt-success" id="ntToast" role="status">
          <div class="nt-ic"><i class="bi bi-check2-circle"></i></div>
          <div class="nt-content">
            <div class="nt-title">Berhasil</div>
            <div class="nt-desc">{{ $toastMessage }}</div>
          </div>
          <button type="button" class="nt-close" id="ntCloseBtn" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
          <div class="nt-bar" aria-hidden="true"></div>
        </div>
      </div>
    @endif

    {{-- Filter Bar --}}
    <section class="ap-filter-bar">
      <div class="ap-search-wrap">
        <i class="bi bi-search ap-search-ic"></i>
        <input id="apSearchInput" type="text" class="ap-search-input" placeholder="Cari Arsip..." value="{{ $initialQ }}" autocomplete="off" />
      </div>
      <div class="ap-sel-wrap">
        <select id="apUnitFilter" class="ap-sel">
          <option value="Semua" {{ $initialUnitNorm === 'semua' ? 'selected' : '' }}>Semua Unit</option>
          @foreach($unitOptions as $u)
            @php $uNorm = trim(mb_strtolower((string)$u, 'UTF-8')); @endphp
            <option value="{{ $u }}" {{ $initialUnitNorm === $uNorm ? 'selected' : '' }}>{{ $u }}</option>
          @endforeach
        </select>
      </div>
      <div class="ap-sel-wrap">
        <select id="apYearFilter" class="ap-sel">
          <option value="Semua" {{ ($initialTahunNorm === 'Semua' || $initialTahunNorm === 'semua') ? 'selected' : '' }}>Tahun</option>
          @foreach($years as $y)
            <option value="{{ $y }}" {{ $initialTahunNorm === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
          @endforeach
        </select>
      </div>
      <div class="ap-sel-wrap">
        <select id="apStatusFilter" class="ap-sel">
          <option value="Semua" {{ $initialStatusNorm === 'semua' ? 'selected' : '' }}>Status</option>
          <option value="Publik" {{ $initialStatusNorm === 'publik' ? 'selected' : '' }}>Publik</option>
          <option value="Privat" {{ $initialStatusNorm === 'privat' ? 'selected' : '' }}>Privat</option>
        </select>
      </div>
      <div class="ap-filter-tools">
        <button type="button" id="apRefreshBtn" class="ap-tool-btn" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
        <button type="button" id="apHistoriBtn" class="ap-tool-btn" title="Histori Aktivitas"><i class="bi bi-calendar3"></i></button>
      </div>
    </section>

    {{-- Table --}}
    <section class="ap-table-section">
      <div class="ap-tbl-head">
        <div class="ap-col ap-col-tahun">Tahun</div>
        <div class="ap-col ap-col-unit">Unit Kerja</div>
        <div class="ap-col ap-col-job">Nama Pekerjaan</div>
        <div class="ap-col ap-col-metode">Metode PBJ</div>
        <div class="ap-col ap-col-nilai">
          <span>Nilai Kontrak</span>
          <button type="button" id="sortNilaiBtn" class="ap-sort-btn" title="Urutkan">
            <i id="sortNilaiIcon" class="bi @if($initialSortNilai === 'asc') bi-sort-up @elseif($initialSortNilai === 'desc') bi-sort-down-alt @else bi-arrow-down-up @endif"></i>
          </button>
        </div>
        <div class="ap-col ap-col-status">Status Pekerjaan</div>
        <div class="ap-col ap-col-aksi">Aksi</div>
      </div>

      @foreach($rows as $r)
        @php
          $sp = strtolower(trim((string)($r['status_pekerjaan'] ?? '')));
          $spClass = match ($sp) {
            'perencanaan' => 'sp-badge sp-plan',
            'pemilihan'   => 'sp-badge sp-select',
            'pelaksanaan' => 'sp-badge sp-do',
            'selesai'     => 'sp-badge sp-done',
            default       => 'sp-badge',
          };
          $nilaiRaw = preg_replace('/[^\d]/', '', (string)($r['nilai_kontrak'] ?? ''));
          $nilaiRaw = $nilaiRaw === '' ? '0' : $nilaiRaw;
        @endphp

        <div class="ap-tbl-row"
             data-status="{{ trim((string)($r['status_arsip'] ?? '-')) }}"
             data-year="{{ trim((string)($r['tahun'] ?? '')) }}"
             data-unit="{{ trim((string)($r['unit'] ?? '')) }}"
             data-moneyraw="{{ $nilaiRaw }}">

          <div class="ap-col ap-col-tahun">{{ $r['tahun'] }}</div>
          <div class="ap-col ap-col-unit">{{ $r['unit'] }}</div>
          <div class="ap-col ap-col-job">{{ $r['pekerjaan'] }}</div>
          <div class="ap-col ap-col-metode">
            <span class="metode-badge">{{ $r['metode_pbj'] !== '-' ? $r['metode_pbj'] : '-' }}</span>
          </div>
          <div class="ap-col ap-col-nilai">{{ $r['nilai_kontrak'] }}</div>
          <div class="ap-col ap-col-status">
            <span class="{{ $spClass }}">{{ $r['status_pekerjaan'] }}</span>
          </div>

          <div class="ap-col ap-col-aksi">
            <button type="button"
              class="aksi-btn aksi-info js-open-detail"
              title="Detail"
              data-title="{{ $r['pekerjaan'] }}"
              data-unit="{{ $r['unit'] }}"
              data-tahun="{{ $r['tahun'] }}"
              data-idrup="{{ $r['idrup'] }}"
              data-status="{{ $r['status_pekerjaan'] }}"
              data-rekanan="{{ $r['rekanan'] }}"
              data-jenis="{{ $r['jenis'] }}"
              data-metode="{{ $r['metode_pbj'] }}"
              data-pagu="{{ $r['pagu'] }}"
              data-hps="{{ $r['hps'] }}"
              data-kontrak="{{ $r['nilai_kontrak'] }}"
              data-docnote="{{ $r['doc_note'] ?? '' }}"
              data-docs='@json($r["dokumen"] ?? [])'>
              <i class="bi bi-info-circle-fill"></i>
            </button>
            <a href="{{ route('ppk.arsip.edit', $r['id']) }}" class="aksi-btn aksi-edit" title="Edit">
              <i class="bi bi-pencil-fill"></i>
            </a>
            <button type="button" class="aksi-btn aksi-delete js-single-delete" title="Hapus" data-id="{{ $r['id'] }}">
              <i class="bi bi-trash3-fill"></i>
            </button>
          </div>
        </div>
      @endforeach

      {{-- Pagination --}}
      <div class="ap-pagination-wrap">
        <div class="ap-page-info">
          Halaman {{ $arsips->currentPage() }} dari {{ $arsips->lastPage() }}
          &bull; Menampilkan {{ $arsips->count() }} dari {{ $arsips->total() }} data
        </div>
        <div class="ap-pagination">
          @php
            $current  = $arsips->currentPage();
            $last     = $arsips->lastPage();
            $start    = max(1, $current - 2);
            $end      = min($last, $current + 2);
            $prevHref = $arsips->onFirstPage() ? '#' : $arsips->appends($qs)->url($current - 1);
            $nextHref = $arsips->hasMorePages() ? $arsips->appends($qs)->url($current + 1) : '#';
          @endphp
          <a class="ap-page-btn {{ $arsips->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $prevHref }}"><i class="bi bi-chevron-left"></i></a>
          @if($start > 1)
            <a class="ap-page-btn" href="{{ $arsips->appends($qs)->url(1) }}">1</a>
            @if($start > 2)<span class="ap-page-btn is-ellipsis">…</span>@endif
          @endif
          @for($i = $start; $i <= $end; $i++)
            <a class="ap-page-btn {{ $i === $current ? 'is-active' : '' }}" href="{{ $arsips->appends($qs)->url($i) }}">{{ $i }}</a>
          @endfor
          @if($end < $last)
            @if($end < $last - 1)<span class="ap-page-btn is-ellipsis">…</span>@endif
            <a class="ap-page-btn" href="{{ $arsips->appends($qs)->url($last) }}">{{ $last }}</a>
          @endif
          <a class="ap-page-btn {{ $arsips->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $nextHref }}"><i class="bi bi-chevron-right"></i></a>
        </div>
      </div>
    </section>
  </main>
</div>

{{-- DETAIL MODAL --}}
<div class="dt-modal" id="dtModal" aria-hidden="true">
  <div class="dt-backdrop" data-close="true"></div>
  <div class="dt-panel" role="dialog" aria-modal="true" aria-labelledby="dtTitle">
    <div class="dt-card">

      <div class="dt-topbar">
        <button type="button" class="dt-back-btn" id="dtCloseBtn" aria-label="Kembali">
          <i class="bi bi-chevron-left"></i> Kembali
        </button>
        <span class="dt-status-badge" id="dtStatusBadge" hidden></span>
      </div>
      <div class="dt-body">
        <div class="dt-title" id="dtTitle">-</div>
          <div style="height:1px; background:#eef3f6; margin:14px 0;"></div>
        <div class="dt-info-grid">
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-building"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Unit Kerja</div>
              <div class="dt-val" id="dtUnit">-</div>
            </div>
          </div>
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-calendar-event"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Tahun Anggaran</div>
              <div class="dt-val" id="dtTahun">-</div>
            </div>
          </div>
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-person-badge"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">ID RUP</div>
              <div class="dt-val" id="dtIdRup">-</div>
            </div>
          </div>
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-diagram-3"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Metode Pengadaan</div>
              <div class="dt-val" id="dtMetode">-</div>
            </div>
          </div>
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-person"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Nama Rekanan</div>
              <div class="dt-val" id="dtRekanan">-</div>
            </div>
          </div>
          <div class="dt-info">
            <div class="dt-ic"><i class="bi bi-box"></i></div>
            <div class="dt-info-txt">
              <div class="dt-label">Jenis Pengadaan</div>
              <div class="dt-val" id="dtJenis">-</div>
            </div>
          </div>
        </div>

        <div class="dt-divider"></div>
        <div class="dt-section-title">Informasi Anggaran</div>
        <div class="dt-budget-grid">
          <div class="dt-budget">
            <div class="dt-label">Pagu Anggaran</div>
            <div class="dt-money" id="dtPagu">-</div>
          </div>
          <div class="dt-budget">
            <div class="dt-label">HPS</div>
            <div class="dt-money" id="dtHps">-</div>
          </div>
          <div class="dt-budget">
            <div class="dt-label">Nilai Kontrak</div>
            <div class="dt-money" id="dtKontrak">-</div>
          </div>
        </div>

        <div class="dt-divider"></div>
        <div class="dt-section-title">Dokumen Pengadaan</div>
        <div class="dt-doc-grid" id="dtDocList"></div>
        <div class="dt-doc-empty" id="dtDocEmpty" hidden>Tidak ada dokumen yang diupload.</div>
        <div class="dt-doc-note" id="dtDocNoteWrap" hidden>
          <div class="dt-doc-note-ic"><i class="bi bi-info-circle"></i></div>
          <div class="dt-doc-note-txt">
            <div class="dt-doc-note-title">Dokumen tidak dipersyaratkan</div>
            <div class="dt-doc-note-desc" id="dtDocNote">-</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- CONFIRM DELETE MODAL --}}
<div class="cf-modal" id="cfModal" aria-hidden="true">
  <div class="cf-backdrop" data-close="true"></div>
  <div class="cf-panel" role="dialog" aria-modal="true" aria-labelledby="cfTitle" aria-describedby="cfDesc">
    <div class="cf-card">
      <div class="cf-top">
        <div class="cf-badge"><i class="bi bi-shield-exclamation"></i></div>
        <button type="button" class="cf-close" id="cfCloseBtn" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="cf-body">
        <div class="cf-title" id="cfTitle">Konfirmasi Hapus</div>
        <div class="cf-desc" id="cfDesc">Apakah Anda yakin ingin menghapus arsip ini? Tindakan ini tidak dapat dibatalkan.</div>
        <div class="cf-actions">
          <button type="button" class="cf-btn cf-btn-ghost" id="cfCancelBtn">Batal</button>
          <button type="button" class="cf-btn cf-btn-danger" id="cfConfirmBtn">
            <i class="bi bi-trash3"></i> Ya, Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- HISTORI PANEL --}}
<div class="hist-overlay" id="histOverlay" aria-hidden="true">
  <div class="hist-backdrop" id="histBackdrop"></div>
  <div class="hist-panel" role="dialog" aria-modal="true" aria-labelledby="histTitle">
    <div class="hist-topbar">
      <button type="button" class="hist-back" id="histBackBtn"><i class="bi bi-chevron-left"></i> Kembali</button>
      <div class="hist-topbar-right">
        <button type="button" class="hist-export-btn" id="histExportBtn" title="Export ke Excel"><i class="bi bi-clipboard2-pulse"></i></button>
      </div>
    </div>
    <div class="hist-body">
      <div class="hist-header">
        <h2 class="hist-title" id="histTitle">Histori Aktivitas</h2>
      </div>
      <div class="hist-table-wrap">
        <div class="hist-tbl-head">
          <div class="hist-col">Waktu</div>
          <div class="hist-col">Nama Akun</div>
          <div class="hist-col">Role</div>
          <div class="hist-col">Unit Kerja</div>
          <div class="hist-col">Aktivitas</div>
        </div>
        <div id="histTableBody">
          <div class="hist-loading" id="histLoading"><div class="hist-spinner"></div><span>Memuat data...</span></div>
          <div class="hist-empty" id="histEmpty" hidden>Tidak ada histori aktivitas.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
:root {
  --yellow: #f6c100; --yellow-dark: #d9aa00;
  --navy: #184f61; --navy2: #184f61;
  --border: #e8eef3;
  --tbl-head-bg: #184f61; --tbl-head-txt: #fff;
  --tbl-row-border: #eef3f6;
  --radius-card: 16px;
}
body.page-arsip.dash-body { font-family: 'Nunito', sans-serif; font-size: 15px; }
.dash-wrap { background: #f4f7fa; }
.dash-main { padding: 28px 28px 40px; display: flex; flex-direction: column; gap: 20px; }

.ap-header { width: 100%; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.ap-header-left { display: flex; flex-direction: column; gap: 4px; }
.ap-header h1 { margin: 0; font-size: 26px; font-weight: 600; color: var(--navy2); }
.ap-header p  { margin: 0; font-size: 15px; color: #64748b; font-weight: 400; }
.ap-header-right { display: flex; align-items: center; gap: 10px; }

.ap-export-btn {
  height: 44px; padding: 0 20px; border-radius: 12px;
  border: 1px solid rgba(0,0,0,.08); background: var(--yellow); color: #1b1b1b;
  font-size: 14px; font-weight: 700; font-family: 'Nunito', sans-serif;
  cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  transition: .15s; white-space: nowrap; box-shadow: 0 4px 14px rgba(246,193,0,.30);
}
.ap-export-btn:hover { background: var(--yellow-dark); transform: translateY(-1px); }
.ap-export-btn:disabled { opacity: .6; cursor: not-allowed; }

.ap-filter-bar {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1px solid var(--border);
  border-radius: var(--radius-card); padding: 12px 14px;
  flex-wrap: nowrap; overflow: hidden;
}
.ap-search-wrap { position: relative; flex: 1 1 120px; min-width: 100px; display: flex; align-items: center; }
.ap-search-ic { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #94a3b8; pointer-events: none; }
.ap-search-input {
  width: 100%; height: 40px; border: 1px solid var(--border); border-radius: 10px;
  padding: 0 12px 0 38px; font-size: 14px; font-family: 'Nunito', sans-serif;
  color: #0f172a; background: #f8fafc; box-sizing: border-box; outline: none; transition: border-color .15s;
}
.ap-search-input:focus { border-color: #94a3b8; background: #fff; }
.ap-search-input::placeholder { color: #b0bec5; }

.ap-sel-wrap { flex: 0 0 auto; min-width: 0; }
.ap-sel {
  height: 40px; padding: 0 32px 0 12px; border: 1px solid var(--border); border-radius: 10px;
  font-size: 14px; font-family: 'Nunito', sans-serif; color: #0f172a;
  background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z' fill='%2394a3b8'/%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; min-width: 90px; max-width: 160px;
}

.ap-filter-tools { display: flex; gap: 6px; align-items: center; margin-left: auto; flex-shrink: 0; }
.ap-tool-btn {
  width: 40px; height: 40px; border: 1px solid var(--border); border-radius: 10px;
  background: #f8fafc; color: var(--navy);
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 17px; transition: .15s;
}
.ap-tool-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

.ap-table-section {
  background: #fff; border: 1px solid var(--border);
  border-radius: var(--radius-card); overflow-x: auto; overflow-y: auto; max-height: 600px;
}

.ap-tbl-head, .ap-tbl-row {
  display: grid;
  grid-template-columns: 80px 1.5fr 1.5fr 1.4fr 1.4fr 1.2fr 110px;
  align-items: center;
  column-gap: 12px;
  padding: 0 14px;
  min-width: 820px;
}
.ap-tbl-head { background: var(--tbl-head-bg); min-height: 52px; position: sticky; top: 0; z-index: 2; }
.ap-tbl-head .ap-col { color: var(--tbl-head-txt); font-size: 13px; font-weight: 800; letter-spacing: .3px; white-space: nowrap; }
.ap-tbl-head .ap-col-nilai { display: flex; align-items: center; gap: 4px; font-weight: 700; }
.ap-tbl-head .ap-col-nilai span { font-weight: 700; }

.ap-sort-btn {
  width: 28px; height: 28px; border: none; background: transparent;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; border-radius: 8px; color: #fff; transition: .15s; padding: 0;
}
.ap-sort-btn:hover { background: rgba(255,255,255,.15); }
.ap-sort-btn i { font-size: 16px; display: block; line-height: 1; }

.ap-tbl-row {
  min-height: 64px;
  border-top: 1px solid var(--tbl-row-border);
  transition: background .15s;
  background: #fff;
}
.ap-tbl-row:hover { background: #f8fafc; }

.ap-col { font-size: 14px; color: #1e293b; min-width: 0; overflow-wrap: anywhere; text-align: left; }
.ap-col-tahun { font-weight: 400; color: #374151; }
.ap-col-unit  { color: #374151; font-weight: 400; font-size: 13px; line-height: 1.35; }
.ap-col-job   { line-height: 1.4; }
.ap-col-nilai { font-weight: 400; color: var(--navy2); }
.ap-col-aksi  { display: flex; align-items: center; gap: 6px; }
.ap-col-status, .ap-col-metode { display: flex; align-items: center; width: 100%; }

.metode-badge {
  display: inline-flex; align-items: center; justify-content: flex-start;
  width: 100%; padding: 6px 12px; border-radius: 8px;
  background: #dbeafe; color: #1e40af;
  font-size: 12px; font-weight: 400;
  white-space: normal; word-break: break-word;
  text-align: left; line-height: 1.4; box-sizing: border-box;
}
.sp-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 100px; padding: 5px 12px; border-radius: 8px; font-size: 13px; font-weight: 400; white-space: nowrap; }
.sp-plan   { background: #fef9c3; color: #854d0e; }
.sp-select { background: #ede9fe; color: #5b21b6; }
.sp-do     { background: #fee2e2; color: #b91c1c; }
.sp-done   { background: #dcfce7; color: #15803d; }

.aksi-btn {
  width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 10px;
  background: #f8fafc; display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 15px; text-decoration: none; color: #374151; transition: .15s; padding: 0;
}
.aksi-btn:hover { transform: translateY(-1px); }
.aksi-info:hover   { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.aksi-edit:hover   { background: #fefce8; border-color: #fde68a; color: #a16207; }
.aksi-delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

.ap-pagination-wrap {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px;
  border-top: 1px solid var(--tbl-row-border); min-width: 700px;
  position: sticky; bottom: 0; background: #fff; z-index: 2;
}
.ap-page-info { font-size: 13px; color: #64748b; }
.ap-pagination { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; justify-content: flex-end; }
.ap-page-btn {
  min-width: 34px; height: 32px; padding: 0 9px; border-radius: 8px; border: 1px solid var(--border);
  background: #fff; color: #0f172a; font-size: 13px; font-weight: 600;
  display: inline-flex; align-items: center; justify-content: center;
  text-decoration: none; transition: .15s; user-select: none; font-family: 'Nunito', sans-serif;
}
.ap-page-btn:hover:not(.is-disabled):not(.is-ellipsis):not(.is-active) { background: #f1f5f9; }
.ap-page-btn.is-active  { background: var(--navy); color: #fff; border-color: var(--navy); }
.ap-page-btn.is-disabled { opacity: .45; pointer-events: none; }
.ap-page-btn.is-ellipsis { pointer-events: none; background: transparent; border-color: transparent; }

/* Detail Modal */
.dt-modal { position: fixed; inset: 0; z-index: 9999; display: none; }
.dt-modal.is-open { display: flex; align-items: center; justify-content: center; padding: 10px; }
.dt-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.35); backdrop-filter: blur(8px); }
.dt-panel { width: min(1100px,96vw); max-height: calc(100vh - 20px); display: flex; flex-direction: column; position: relative; z-index: 1; border-radius: 20px; overflow: hidden; }
.dt-card  { width: 100%; display: flex; flex-direction: column; min-height: 0; border-radius: 20px; background: #fff; overflow: hidden; }
.dt-topbar {
  position: sticky; top: 0; z-index: 3; background: #fff; padding: 18px 18px 14px;
  border-bottom: 1px solid #eef3f6; display: flex; align-items: center;
  justify-content: space-between; gap: 12px;
}
.dt-title { font-size: 22px; font-weight: 400; color: #0f172a; overflow-wrap: anywhere; margin-bottom: 4px; }
.dt-status-badge {
  display: inline-flex; align-items: center; justify-content: left;
  padding: 5px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; white-space: nowrap; flex-shrink: 0;
}
.dt-status-badge.sp-plan   { background: #fef9c3; color: #854d0e; }
.dt-status-badge.sp-select { background: #ede9fe; color: #5b21b6; }
.dt-status-badge.sp-do     { background: #fee2e2; color: #b91c1c; }
.dt-status-badge.sp-done   { background: #dcfce7; color: #15803d; }
.dt-back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: none; border: none;
  font-size: 14.5px; font-weight: 400; font-family: 'Nunito', sans-serif;
  color: var(--navy); cursor: pointer; padding: 0; transition: opacity .15s;
}
.dt-back-btn:hover { opacity: .65; }
.dt-body { flex: 1; overflow-y: auto; min-height: 0; padding: 16px 18px 20px; overscroll-behavior: contain; }
.dt-info-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
.dt-info { display: flex; gap: 10px; align-items: flex-start; }
.dt-ic   { width: 38px; height: 38px; border-radius: 12px; border: 1px solid #eef3f6; background: #f8fbfd; display: grid; place-items: center; flex: 0 0 auto; font-size: 16px; color: var(--navy); }
.dt-label { font-size: 12px; color: #64748b; font-weight: 600; }
.dt-val   { font-size: 14px; color: #0f172a; font-weight: 400; margin-top: 2px; }
.dt-divider { height: 1px; background: #eef3f6; margin: 14px 0; }
.dt-section-title { font-size: 13px; font-weight: 400; color: #64748b; letter-spacing: .5px; text-transform: uppercase; margin-bottom: 10px; }
.dt-budget-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
.dt-budget { padding: 12px 14px; background: #f8fbfd; border: 1px solid #eef3f6; border-radius: 12px; }
.dt-money  { font-size: 16px; font-weight: 400; color: var(--navy2); margin-top: 4px; }
.dt-doc-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; margin-top: 10px; }
.dt-doc-card { border: 1px solid #e8eef3; background: #fff; border-radius: 14px; padding: 12px 14px; display: flex; align-items: center; gap: 10px; }
.dt-doc-ic   { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #f8fbfd; border: 1px solid #eef3f6; flex: 0 0 auto; font-size: 18px; }
.dt-doc-info  { min-width: 0; flex: 1; }
.dt-doc-title { font-size: 14px; font-weight: 400; line-height: 1.3; overflow-wrap: anywhere; }
.dt-doc-sub   { font-size: 12px; color: #64748b; margin-top: 2px; overflow-wrap: anywhere; }
.dt-doc-act   { width: 34px; height: 34px; border-radius: 12px; display: grid; place-items: center; background: #f8fbfd; border: 1px solid #eef3f6; text-decoration: none; color: inherit; flex: 0 0 auto; font-size: 15px; }
.dt-doc-empty { margin-top: 10px; opacity: .75; font-size: 14px; color: #64748b; }
.dt-doc-note  { display: flex; gap: 10px; margin-top: 12px; padding: 12px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; }
.dt-doc-note-ic    { font-size: 18px; color: #d97706; flex: 0 0 auto; }
.dt-doc-note-title { font-size: 13px; font-weight: 800; color: #92400e; }
.dt-doc-note-desc  { font-size: 13px; color: #78350f; margin-top: 2px; }

/* Confirm Modal */
.cf-modal { position: fixed; inset: 0; z-index: 10000; display: none; }
.cf-modal.is-open { display: flex; align-items: center; justify-content: center; padding: 12px; }
.cf-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.40); backdrop-filter: blur(8px); }
.cf-panel { width: min(480px,94vw); position: relative; z-index: 1; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(2,6,23,.25); }
.cf-card  { background: #fff; border: 1px solid rgba(148,163,184,.3); border-radius: 20px; overflow: hidden; }
.cf-top   { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 16px 16px 0; }
.cf-badge { width: 50px; height: 50px; border-radius: 16px; display: grid; place-items: center; background: #fef3c7; border: 1px solid #fde68a; font-size: 22px; color: #d97706; }
.cf-close { width: 40px; height: 40px; border-radius: 12px; border: 1px solid #e8eef3; background: #fff; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; }
.cf-body  { padding: 10px 16px 16px; }
.cf-title { font-size: 18px; font-weight: 800; color: #0f172a; margin: 6px 0 4px; }
.cf-desc  { font-size: 13.5px; color: #475569; line-height: 1.55; }
.cf-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; margin-top: 14px; }
.cf-btn { height: 40px; padding: 0 16px; border-radius: 12px; border: 1px solid transparent; font-size: 14px; font-weight: 700; font-family: 'Nunito', sans-serif; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 7px; transition: .15s; }
.cf-btn-ghost  { background: #fff; border-color: #e8eef3; color: #0f172a; }
.cf-btn-ghost:hover  { background: #f1f5f9; }
.cf-btn-danger { background: #ef4444; color: #fff; }
.cf-btn-danger:hover { background: #dc2626; }

/* Toast */
.nt-wrap  { position: fixed; top: 18px; right: 18px; z-index: 11000; pointer-events: none; }
.nt-toast { width: min(380px,calc(100vw - 36px)); background: #fff; border: 1px solid #e6eef2; border-radius: 16px; box-shadow: 0 16px 32px rgba(2,8,23,.12); padding: 14px; display: flex; gap: 12px; align-items: flex-start; position: relative; overflow: hidden; pointer-events: auto; }
.nt-success { border-left: 4px solid #22c55e; }
.nt-ic    { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: #ecfdf3; border: 1px solid #d8f5e3; color: #16a34a; flex: 0 0 auto; }
.nt-title { font-size: 14px; font-weight: 800; color: #0f172a; }
.nt-desc  { font-size: 13px; color: #475569; margin-top: 2px; line-height: 1.5; }
.nt-close { margin-left: auto; width: 32px; height: 32px; border-radius: 10px; border: 1px solid #eef2f7; background: #fff; display: grid; place-items: center; padding: 0; cursor: pointer; }
.nt-bar   { position: absolute; left: 0; bottom: 0; height: 3px; width: 100%; background: linear-gradient(90deg,#22c55e,#16a34a); animation: ntbar 4s linear forwards; }
@keyframes ntbar { from { width: 100%; } to { width: 0%; } }

/* Histori */
.hist-overlay { position: fixed; inset: 0; z-index: 9000; display: none; align-items: center; justify-content: center; padding: 16px; }
.hist-overlay.is-open { display: flex; }
.hist-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.38); backdrop-filter: blur(10px); }
.hist-panel { position: relative; z-index: 1; width: min(1100px,96vw); max-height: calc(100vh - 32px); display: flex; flex-direction: column; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 64px rgba(2,6,23,.22); animation: histPop .2s ease; }
@keyframes histPop { from { opacity: 0; transform: scale(.97); } to { opacity: 1; transform: scale(1); } }
.hist-topbar { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 14px; border-bottom: 1px solid var(--border); background: #fff; position: sticky; top: 0; z-index: 2; gap: 12px; }
.hist-back { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; font-size: 14.5px; font-weight: 700; font-family: 'Nunito', sans-serif; color: var(--navy); cursor: pointer; padding: 0; transition: opacity .15s; }
.hist-back:hover { opacity: .65; }
.hist-topbar-right { display: flex; align-items: center; gap: 8px; }
.hist-export-btn { width: 40px; height: 40px; border: 1px solid var(--border); border-radius: 11px; background: #f8fafc; color: var(--navy); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 19px; transition: .15s; }
.hist-export-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }
.hist-body { padding: 20px 22px 24px; overflow-y: auto; overscroll-behavior: contain; }
.hist-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
.hist-title { font-size: 21px; font-weight: 800; color: #0f172a; margin: 0; }
.hist-table-wrap { border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.hist-tbl-head, .hist-tbl-row { display: grid; grid-template-columns: 148px 170px 130px 1.5fr 2.2fr; column-gap: 16px; padding: 0 18px; }
.hist-tbl-head { background: var(--tbl-head-bg); min-height: 48px; align-items: center; }
.hist-tbl-head .hist-col { color: #fff; font-size: 13px; font-weight: 700; letter-spacing: .3px; white-space: nowrap; }
.hist-tbl-row { border-top: 1px solid var(--tbl-row-border); padding-top: 15px; padding-bottom: 15px; align-items: start; transition: background .12s; }
.hist-tbl-row:hover { background: #f8fbfe; }
.hist-col { font-size: 14px; color: #1e293b; min-width: 0; overflow-wrap: anywhere; line-height: 1.45; }
.hist-loading { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 44px; color: #64748b; font-size: 14px; }
.hist-spinner { width: 20px; height: 20px; border: 2px solid #e2e8f0; border-top-color: var(--navy); border-radius: 50%; animation: hspin .7s linear infinite; }
@keyframes hspin { to { transform: rotate(360deg); } }
.hist-empty { text-align: center; padding: 44px; color: #94a3b8; font-size: 14px; }

@media (max-width: 1100px) {
  .ap-filter-bar { flex-wrap: wrap; }
  .ap-filter-tools { margin-left: 0; }
  .dt-info-grid, .dt-budget-grid { grid-template-columns: repeat(2,1fr); }
  .dt-doc-grid { grid-template-columns: 1fr; }
}
@media (max-width: 800px) {
  .dash-sidebar { display: none; }
  .dash-main { padding: 16px; }
  .ap-filter-bar { flex-wrap: wrap; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){

  const searchInput  = document.getElementById('apSearchInput');
  const unitFilter   = document.getElementById('apUnitFilter');
  const statusFilter = document.getElementById('apStatusFilter');
  const yearFilter   = document.getElementById('apYearFilter');
  const refreshBtn   = document.getElementById('apRefreshBtn');

  const dtModal       = document.getElementById('dtModal');
  const dtCloseBtn    = document.getElementById('dtCloseBtn');
  const dtTitle       = document.getElementById('dtTitle');
  const dtUnit        = document.getElementById('dtUnit');
  const dtTahun       = document.getElementById('dtTahun');
  const dtIdRup       = document.getElementById('dtIdRup');
  const dtRekanan     = document.getElementById('dtRekanan');
  const dtJenis       = document.getElementById('dtJenis');
  const dtMetode      = document.getElementById('dtMetode');
  const dtPagu        = document.getElementById('dtPagu');
  const dtHps         = document.getElementById('dtHps');
  const dtKontrak     = document.getElementById('dtKontrak');
  const dtDocList     = document.getElementById('dtDocList');
  const dtDocEmpty    = document.getElementById('dtDocEmpty');
  const dtDocNoteWrap = document.getElementById('dtDocNoteWrap');
  const dtDocNote     = document.getElementById('dtDocNote');
  const cfModal       = document.getElementById('cfModal');
  const cfCancelBtn   = document.getElementById('cfCancelBtn');
  const cfCloseBtn    = document.getElementById('cfCloseBtn');
  const cfConfirmBtn  = document.getElementById('cfConfirmBtn');

  /* Filter server-side */
  function applyServerFilter(){
    const url = new URL(window.location.href);
    const q      = searchInput?.value?.trim() || '';
    const unit   = unitFilter?.value   || 'Semua';
    const status = statusFilter?.value || 'Semua';
    const tahun  = yearFilter?.value   || 'Semua';
    if(q)             url.searchParams.set('q', q);           else url.searchParams.delete('q');
    if(unit!=='Semua')   url.searchParams.set('unit', unit);   else url.searchParams.delete('unit');
    if(status!=='Semua') url.searchParams.set('status', status); else url.searchParams.delete('status');
    if(tahun!=='Semua')  url.searchParams.set('tahun', tahun);  else url.searchParams.delete('tahun');
    url.searchParams.delete('page');
    window.location.href = url.toString();
  }

  searchInput?.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); applyServerFilter(); }});
  unitFilter?.addEventListener('change', applyServerFilter);
  statusFilter?.addEventListener('change', applyServerFilter);
  yearFilter?.addEventListener('change', applyServerFilter);
  refreshBtn?.addEventListener('click', ()=>{ const u=new URL(window.location.href); u.search=''; window.location.href=u.toString(); });

  /* Sort nilai */
  document.getElementById('sortNilaiBtn')?.addEventListener('click', function(){
    const url = new URL(window.location.href);
    const cur = url.searchParams.get('sort_nilai');
    if(cur==='asc') url.searchParams.set('sort_nilai','desc');
    else if(cur==='desc') url.searchParams.delete('sort_nilai');
    else url.searchParams.set('sort_nilai','asc');
    url.searchParams.delete('page');
    window.location.href = url.toString();
  });

  /* Detail Modal */
  function normalizeStorageUrl(path){
    if(!path) return '#';
    let s = String(path).trim().replace(/\\/g,'/');
    if(s.startsWith('http')) return s;
    if(s.startsWith('/storage/')) return s;
    return '/storage/'+s.replace(/^\/+/,'');
  }

  function openDetail(data){
    dtTitle.textContent   = data.title   || '-';
    dtUnit.textContent    = data.unit    || '-';
    dtTahun.textContent   = data.tahun   || '-';
    dtIdRup.textContent   = data.idrup   || '-';
    dtRekanan.textContent = data.rekanan || '-';
    dtJenis.textContent   = data.jenis   || '-';
    dtMetode.textContent  = data.metode  || '-';
    dtPagu.textContent    = data.pagu    || '-';
    dtHps.textContent     = data.hps     || '-';
    dtKontrak.textContent = data.kontrak || '-';

    // Badge status
    const badge = document.getElementById('dtStatusBadge');
    if(badge){
      const sp = (data.status||'').toLowerCase().trim();
      badge.textContent = data.status || '';
      badge.className = 'dt-status-badge';
      if(sp==='perencanaan') badge.classList.add('sp-plan');
      else if(sp==='pemilihan') badge.classList.add('sp-select');
      else if(sp==='pelaksanaan') badge.classList.add('sp-do');
      else if(sp==='selesai') badge.classList.add('sp-done');
      badge.hidden = !data.status;
    }

    // Dokumen
    dtDocList.innerHTML = '';
    const docs = data.docs || {};
    let total = 0;
    Object.keys(docs).forEach(grp => {
      const arr = Array.isArray(docs[grp]) ? docs[grp] : [];
      arr.forEach(doc => {
        let fileUrl='', fileName='-';
        if(typeof doc==='string'){
          fileUrl=normalizeStorageUrl(doc);
          fileName=doc.split('/').filter(Boolean).pop()||'Dokumen';
        } else {
          fileUrl=normalizeStorageUrl(doc.url||doc.path||'');
          fileName=doc.name||doc.label||(fileUrl.split('/').filter(Boolean).pop()||'Dokumen');
        }
        total++;
        const card=document.createElement('div'); card.className='dt-doc-card';
        card.innerHTML=`
          <div class="dt-doc-ic"><i class="bi bi-file-earmark-text"></i></div>
          <div class="dt-doc-info">
            <div class="dt-doc-title">${grp||'Dokumen'}</div>
            <div class="dt-doc-sub">${fileName}</div>
          </div>
          <a class="dt-doc-act" href="${fileUrl}" target="_blank" rel="noopener"><i class="bi bi-eye"></i></a>
        `;
        dtDocList.appendChild(card);
      });
    });
    dtDocEmpty.hidden = total>0;
    const note=(data.docnote||'').trim();
    dtDocNoteWrap.hidden = !note;
    if(note) dtDocNote.textContent=note;

    dtModal.classList.add('is-open');
    dtModal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
  }

  function closeDetail(){
    dtModal.classList.remove('is-open');
    dtModal.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
  }

  document.querySelectorAll('.js-open-detail').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      let docs={};
      try{ docs=JSON.parse(btn.getAttribute('data-docs')||'{}'); }catch(_){}
      openDetail({
        title  : btn.dataset.title,
        unit   : btn.dataset.unit,
        tahun  : btn.dataset.tahun,
        idrup  : btn.dataset.idrup,
        status : btn.dataset.status,
        rekanan: btn.dataset.rekanan,
        jenis  : btn.dataset.jenis,
        metode : btn.dataset.metode,
        pagu   : btn.dataset.pagu,
        hps    : btn.dataset.hps,
        kontrak: btn.dataset.kontrak,
        docnote: btn.dataset.docnote,
        docs,
      });
    });
  });

  dtCloseBtn?.addEventListener('click', closeDetail);
  dtModal?.addEventListener('click', e=>{ if(e.target?.dataset?.close==='true') closeDetail(); });

  /* Delete Modal */
  let pendingIds=[];
  function openConfirm(ids){ pendingIds=ids.slice(); cfModal.classList.add('is-open'); cfModal.setAttribute('aria-hidden','false'); }
  function closeConfirm(){ cfModal.classList.remove('is-open'); cfModal.setAttribute('aria-hidden','true'); pendingIds=[]; }

  async function deleteOne(id){
    const url=@json($deleteUrlTemplate).replace('__ID__',id);
    const res=await fetch(url,{
      method:'DELETE',
      headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
    });
    if(!res.ok) throw new Error('Gagal menghapus arsip ID '+id);
  }

  async function runDelete(ids){
    try{
      for(const id of ids) await deleteOne(id);
      window.location.href = window.location.pathname + '?deleted=1';
    }
    catch(err){ alert(err?.message||'Gagal menghapus arsip.'); }
  }

  document.querySelectorAll('.js-single-delete').forEach(btn=>{
    btn.addEventListener('click', ()=>openConfirm([btn.dataset.id]));
  });
  cfCancelBtn?.addEventListener('click', closeConfirm);
  cfCloseBtn?.addEventListener('click', closeConfirm);
  cfModal?.addEventListener('click', e=>{ if(e.target?.dataset?.close==='true') closeConfirm(); });
  cfConfirmBtn?.addEventListener('click', async()=>{ const ids=pendingIds.slice(); if(ids.length===0) return; await runDelete(ids); });

  /* Export Excel */
  const lastPage = @json($arsips->lastPage());
  async function fetchRowsFromPage(page){
    const url=new URL(window.location.href);
    url.searchParams.set('page',page);
    const text=await fetch(url.toString()).then(r=>r.text());
    const parser=new DOMParser();
    const doc=parser.parseFromString(text,'text/html');
    return Array.from(doc.querySelectorAll('.ap-tbl-row')).map(row=>{
      const btn=row.querySelector('.js-open-detail');
      return {
        "Nama Pekerjaan"            : row.querySelector('.ap-col-job')?.textContent?.trim()||'-',
        "Unit Kerja"                : row.querySelector('.ap-col-unit')?.textContent?.trim()||'-',
        "Tahun Anggaran"            : row.querySelector('.ap-col-tahun')?.textContent?.trim()||'-',
        "Metode PBJ"                : btn?.dataset?.metode||'-',
        "ID RUP"                    : btn?.dataset?.idrup||'-',
        "Status Pekerjaan"          : btn?.dataset?.status||'-',
        "Nama Rekanan"              : btn?.dataset?.rekanan||'-',
        "Jenis Pengadaan"           : btn?.dataset?.jenis||'-',
        "Pagu Anggaran"             : btn?.dataset?.pagu||'-',
        "HPS"                       : btn?.dataset?.hps||'-',
        "Nilai Kontrak"             : btn?.dataset?.kontrak||'-',
        "Dok. Tidak Dipersyaratkan" : btn?.dataset?.docnote||'-',
      };
    });
  }
  async function exportToExcel(){
    if(typeof XLSX==='undefined'){ alert('Library Excel belum termuat.'); return; }
    let all=[];
    for(let p=1;p<=(Number(lastPage)||1);p++){ all=all.concat(await fetchRowsFromPage(p)); }
    if(all.length===0){ alert('Tidak ada data untuk diexport.'); return; }
    const ws=XLSX.utils.json_to_sheet(all);
    const wb=XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb,ws,'Arsip PBJ');
    const now=new Date(), pad=n=>String(n).padStart(2,'0');
    const stamp=`${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}_${pad(now.getHours())}${pad(now.getMinutes())}`;
    XLSX.writeFile(wb,`Arsip_PBJ_${stamp}.xlsx`);
  }
  document.getElementById('apExportBtn')?.addEventListener('click', async function(){
    try{ this.disabled=true; await exportToExcel(); }
    catch(err){ alert(err?.message||'Export gagal.'); }
    finally{ this.disabled=false; }
  });

  /* Histori */
  const histOverlay   = document.getElementById('histOverlay');
  const histBackBtn   = document.getElementById('histBackBtn');
  const histTableBody = document.getElementById('histTableBody');
  const histLoading   = document.getElementById('histLoading');
  const histEmpty     = document.getElementById('histEmpty');
  const histExportBtn = document.getElementById('histExportBtn');
  let histData=[];

  function renderHistoriRows(data){
    histTableBody.querySelectorAll('.hist-tbl-row').forEach(el=>el.remove());
    if(data.length===0){ histEmpty.hidden=false; return; }
    histEmpty.hidden=true;
    data.forEach(item=>{
      const row=document.createElement('div'); row.className='hist-tbl-row';
      row.innerHTML=`
        <div class="hist-col">${item.waktu||'-'}</div>
        <div class="hist-col">${item.nama_akun||'-'}</div>
        <div class="hist-col">${item.role||'-'}</div>
        <div class="hist-col">${item.unit_kerja||'-'}</div>
        <div class="hist-col">${item.aktivitas||'-'}</div>
      `;
      histTableBody.appendChild(row);
    });
  }

  async function loadHistori(){
    histLoading.hidden=false; histEmpty.hidden=true;
    histTableBody.querySelectorAll('.hist-tbl-row').forEach(el=>el.remove());
    try{
      const res=await fetch('/ppk/histori',{
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}
      });
      if(!res.ok) throw new Error();
      const json=await res.json();
      histData=Array.isArray(json.data)?json.data:(Array.isArray(json)?json:[]);
    }catch(err){ histData=[]; }
    histLoading.hidden=true;
    renderHistoriRows(histData);
  }

  function openHistori(){ histOverlay.classList.add('is-open'); histOverlay.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; loadHistori(); }
  function closeHistori(){ histOverlay.classList.remove('is-open'); histOverlay.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }

  document.getElementById('apHistoriBtn')?.addEventListener('click', openHistori);
  histBackBtn?.addEventListener('click', closeHistori);
  document.getElementById('histBackdrop')?.addEventListener('click', closeHistori);

  histExportBtn?.addEventListener('click', function(){
    if(typeof XLSX==='undefined'||histData.length===0){ alert('Tidak ada data histori.'); return; }
    const ws=XLSX.utils.json_to_sheet(histData.map(d=>({'Waktu':d.waktu||'-','Nama Akun':d.nama_akun||'-','Role':d.role||'-','Unit Kerja':d.unit_kerja||'-','Aktivitas':d.aktivitas||'-'})));
    const wb=XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb,ws,'Histori');
    const now=new Date(), pad=n=>String(n).padStart(2,'0');
    XLSX.writeFile(wb,`Histori_${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}.xlsx`);
  });

  /* Toast */
  const ntToast=document.getElementById('ntToast');
  const ntClose=document.getElementById('ntCloseBtn');
  if(ntToast){
    const close=()=>ntToast.parentElement?.remove();
    ntClose?.addEventListener('click', close);
    setTimeout(close,4000);
  }
});
</script>

</body>
</html>