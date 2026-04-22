{{-- resources/views/Home/pbj.blade.php --}}
@extends('layouts.app-home')
@section('title', 'Arsip PBJ | SIAPABAJA')

@section('content')
<section class="pbj-page">
  <div class="container">

    {{-- ✅ bedanya HOME: balik ke home --}}
    <a class="detail-back" href="{{ route('home') }}">
      <i class="bi bi-chevron-left"></i> Kembali
    </a>

    @php
      use App\Models\Pengadaan;
      use App\Models\Unit;
      use Illuminate\Support\Str;
      use Illuminate\Support\Facades\Schema;

      // =========================
      // ✅ FILTER (SAMA KONSEP DENGAN PPK/ArsipPBJ)
      // HOME: hanya tampilkan arsip PUBLIK
      // ✅ Status = STATUS PEKERJAJAAN
      // =========================
      $q      = request('q');
      $unitId = request('unit_id');
      $statusPekerjaan = request('status_pekerjaan');
      $tahun  = request('tahun');

      // opsi status pekerjaan (samakan dengan PPK)
      $statusPekerjaanOptions = ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];

      // ✅ Unit dropdown dari DB
      $unitOptions = Unit::orderBy('nama')->get();

      // ✅ Tahun dropdown dari DB (HANYA yang muncul di pengadaans publik)
      $tahunOptions = Pengadaan::where('status_arsip', 'Publik')
        ->whereNotNull('tahun')
        ->select('tahun')
        ->distinct()
        ->orderBy('tahun', 'desc')
        ->pluck('tahun')
        ->map(fn($t) => (int)$t)
        ->values();

      // =========================
      // ✅ QUERY ARSIP (SERVER-SIDE + PAGINATION 10)
      // paling atas = yang TERUPDATE
      // =========================
      $arsipQuery = Pengadaan::with('unit')
        ->where('status_arsip', 'Publik');

      /**
       * ✅ FIX SEARCH (HOME) - DIBATASI:
       * HANYA mencakup:
       * - Tahun
       * - Unit Kerja (unit.nama)
       * - Nama Pekerjaan
       * - Nilai Kontrak
       * - Status Pekerjaan
       *
       * ❌ Tidak cari ke dokumen/file/lampiran
       * ❌ Tidak cari ke id_rup/nama_rekanan/jenis/pagu/hps
       * PostgreSQL: ILIKE + regexp_replace untuk search angka nilai kontrak
       * Multi kata = AND antar term
       */
      if($q){
        $qqRaw = trim((string)$q);

        $terms = preg_split('/\s+/', $qqRaw, -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_values(array_filter(array_map(fn($t) => trim($t), $terms)));

        foreach($terms as $term){
          $arsipQuery->where(function($sub) use ($term){
            $like = "%{$term}%";

            // ✅ Nama Pekerjaan & Status Pekerjaan
            $sub->where('nama_pekerjaan', 'ILIKE', $like)
                ->orWhere('status_pekerjaan', 'ILIKE', $like);

            // ✅ Tahun (cast text)
            $sub->orWhereRaw('CAST(tahun AS TEXT) ILIKE ?', [$like]);

            // ✅ Unit Kerja (relasi unit.nama)
            $sub->orWhereHas('unit', function($u) use ($like){
              $u->where('nama', 'ILIKE', $like);
            });

            // ✅ Nilai Kontrak (angka) -> cocokkan digit-only
            $digits = preg_replace('/\D+/', '', (string)$term);
            if($digits !== ''){
              $digLike = "%{$digits}%";
              $sub->orWhereRaw("regexp_replace(CAST(nilai_kontrak AS TEXT), '\\D', '', 'g') LIKE ?", [$digLike]);
            }
          });
        }
      }

      if($unitId && is_numeric($unitId)){
        $arsipQuery->where('unit_id', (int)$unitId);
      }

      if($statusPekerjaan && in_array($statusPekerjaan, $statusPekerjaanOptions, true)){
        $arsipQuery->where('status_pekerjaan', $statusPekerjaan);
      }

      if($tahun && is_numeric($tahun)){
        $arsipQuery->where('tahun', (int)$tahun);
      }

      // ✅ pagination 10 terbaru + query string kebawa saat pindah halaman
      $arsips = $arsipQuery
        ->orderByDesc('updated_at')
        ->orderByDesc('id')
        ->paginate(10);

      // query string yang dipertahankan (samakan konsep PPK)
      $qs = request()->except('page');

      $totalRows = $arsips->total();

      // helper rupiah
      $rupiah = function($v){
        if($v === null || $v === '') return '-';
        if(is_string($v) && Str::contains($v, 'Rp')) return $v;
        $n = is_numeric($v) ? (float)$v : (float)preg_replace('/[^\d]/', '', (string)$v);
        return 'Rp. ' . number_format($n, 0, ',', '.') . ',00';
      };

      function chipClass($s){
        return match($s){
          'Perencanaan' => 'chip chip-yellow',
          'Pemilihan'   => 'chip chip-purple',
          'Pelaksanaan' => 'chip chip-pink',
          'Selesai'     => 'chip chip-green',
          default       => 'chip'
        };
      }

      /**
       * ✅ Builder dokumen untuk modal (SAMA PERSIS dengan Home/IndexContent)
       */
      function buildDokumenListForHome($pengadaan){
        if(!$pengadaan) return [];
        $attrs = method_exists($pengadaan, 'getAttributes') ? $pengadaan->getAttributes() : (array)$pengadaan;

        $out = [];
        foreach($attrs as $field => $rawValue){
          $lk = strtolower((string)$field);

          if(!(str_contains($lk,'dokumen') || str_contains($lk,'file') || str_contains($lk,'lampiran'))) continue;
          if(in_array($field, ['dokumen_tidak_dipersyaratkan','dokumen_tidak_dipersyaratkan_json'], true)) continue;

          $files = [];
          if(is_array($rawValue)) $files = $rawValue;
          elseif(is_string($rawValue) && trim($rawValue) !== ''){
            $s = trim($rawValue);
            $decoded = json_decode($s, true);
            if(is_array($decoded)) $files = $decoded;
            else $files = [$s];
          }

          $files = array_values(array_filter(array_map(function($x){
            if($x === null) return null;
            $s = trim((string)$x);
            if($s === '') return null;

            $s = str_replace('\\','/',$s);
            $s = explode('?', $s)[0];

            if(Str::startsWith($s, ['http://','https://'])){
              $u = parse_url($s);
              if(!empty($u['path'])) $s = $u['path'];
            }

            $s = ltrim($s,'/');
            if(Str::startsWith($s, 'public/'))  $s = Str::after($s, 'public/');
            if(Str::startsWith($s, 'storage/')) $s = Str::after($s, 'storage/');
            $s = preg_replace('#^storage/#','',$s);

            return $s !== '' ? $s : null;
          }, $files)));

          if(count($files) === 0) continue;

          foreach($files as $path){
            $out[$field][] = [
              'field' => $field,
              'name'  => basename($path),
              'url'   => '/storage/'.ltrim($path,'/'),
            ];
          }
        }

        return $out;
      }

      // ✅ Kolom E (dokumen tidak dipersyaratkan) -> sama seperti IndexContent
      function buildDocNoteForHome($pengadaan){
        if(!$pengadaan) return '';

        $rawE = is_array($pengadaan->dokumen_tidak_dipersyaratkan ?? null)
          ? $pengadaan->dokumen_tidak_dipersyaratkan
          : (json_decode((string)($pengadaan->dokumen_tidak_dipersyaratkan ?? ''), true) ?: []);

        if(is_array($rawE) && count($rawE) > 0){
          return implode(', ', array_map(fn($x) => is_string($x) ? $x : json_encode($x), $rawE));
        }

        $eVal = is_string($pengadaan->dokumen_tidak_dipersyaratkan ?? null)
          ? trim((string)$pengadaan->dokumen_tidak_dipersyaratkan)
          : ($pengadaan->dokumen_tidak_dipersyaratkan ?? null);

        if($eVal === true || $eVal === 1 || $eVal === "1" || (is_string($eVal) && in_array(strtolower($eVal), ["ya","iya","true","yes"], true))){
          return "Dokumen pada Kolom E bersifat opsional (tidak dipersyaratkan).";
        }

        return is_string($eVal) ? $eVal : '';
      }
    @endphp

    {{-- FILTER BAR --}}
    <form class="pbj-filters" id="pbjFilterForm" method="GET" action="{{ url()->current() }}">
      <div class="pbj-search">
        <i class="bi bi-search"></i>
        <input type="text" id="pbjSearch" name="q" value="{{ $q ?? '' }}" placeholder="Cari..." />
      </div>

      {{-- ✅ Unit --}}
      <select class="pbj-select" id="pbjUnit" name="unit_id">
        <option value="" {{ !$unitId ? 'selected' : '' }}>Semua Unit</option>
        @foreach($unitOptions as $u)
          <option value="{{ $u->id }}" {{ (string)$unitId === (string)$u->id ? 'selected' : '' }}>
            {{ $u->nama }}
          </option>
        @endforeach
      </select>

      {{-- ✅ Status = Status Pekerjaan --}}
      <select class="pbj-select" id="pbjStatusPekerjaan" name="status_pekerjaan">
        <option value="" {{ !$statusPekerjaan ? 'selected' : '' }}>Semua Status</option>
        @foreach($statusPekerjaanOptions as $sp)
          <option value="{{ $sp }}" {{ (string)$statusPekerjaan === (string)$sp ? 'selected' : '' }}>
            {{ $sp }}
          </option>
        @endforeach
      </select>

      {{-- ✅ Tahun --}}
      <select class="pbj-select" id="pbjYear" name="tahun">
        <option value="" {{ !$tahun ? 'selected' : '' }}>Semua Tahun</option>
        @foreach($tahunOptions as $t)
          <option value="{{ $t }}" {{ (string)$tahun === (string)$t ? 'selected' : '' }}>
            {{ $t }}
          </option>
        @endforeach
      </select>

      <div class="pbj-actions">
        <a class="pbj-icon-btn" id="pbjRefreshBtn" href="{{ url()->current() }}" title="Refresh" style="display:inline-flex; align-items:center; justify-content:center;">
          <i class="bi bi-arrow-clockwise"></i>
        </a>
      </div>
    </form>

    {{-- TABLE CARD --}}
   <div class="pbj-card">
  {{-- Header Tabel --}}
  <div class="pbj-tbl-head">
    <div class="pbj-col pbj-col-tahun">Tahun</div>
    <div class="pbj-col pbj-col-unit">Unit Kerja</div>
    <div class="pbj-col pbj-col-job">Nama Pekerjaan</div>
    <div class="pbj-col pbj-col-metode">Metode PBJ</div>
    <div class="pbj-col pbj-col-nilai">
      <span>Nilai Kontrak</span>
      <button type="button" class="pbj-sort-btn" id="sortNilaiBtn" title="Urutkan">
        <i class="bi bi-arrow-down-up" id="sortNilaiIcon"></i>
      </button>
    </div>
    <div class="pbj-col pbj-col-status">Status Pekerjaan</div>
    <div class="pbj-col pbj-col-aksi">Aksi</div>
  </div>

  {{-- Baris Data --}}
  @forelse($arsips as $a)
    @php
      $nilaiText = $rupiah($a->nilai_kontrak ?? null);
      $unitName  = $a->unit?->nama ?? '-';
      $sp        = strtolower(trim((string)($a->status_pekerjaan ?? '')));
      $spClass   = match($sp) {
        'perencanaan' => 'sp-badge sp-plan',
        'pemilihan'   => 'sp-badge sp-select',
        'pelaksanaan' => 'sp-badge sp-do',
        'selesai'     => 'sp-badge sp-done',
        default       => 'sp-badge',
      };
      $nilaiRaw = preg_replace('/[^\d]/', '', (string)($a->nilai_kontrak ?? ''));

      $payload = [
        'title'   => $a->nama_pekerjaan ?? '-',
        'unit'    => $unitName,
        'tahun'   => $a->tahun ?? '-',
        'idrup'   => $a->id_rup ?? '-',
        'status'  => $a->status_pekerjaan ?? '-',
        'rekanan' => $a->nama_rekanan ?? '-',
        'jenis'   => $a->jenis_pengadaan ?? '-',
        'pagu'    => $rupiah($a->pagu_anggaran),
        'hps'     => $rupiah($a->hps),
        'kontrak' => $rupiah($a->nilai_kontrak),
        'metode'  => $a->metode_pbj ?? $a->metode_pengadaan ?? $a->metode ?? $a->jenis_pengadaan ?? '-',
        'docnote' => buildDocNoteForHome($a),
        'docs'    => buildDokumenListForHome($a),
      ];
    @endphp

    <div class="pbj-tbl-row" data-moneyraw="{{ $nilaiRaw }}">
      <div class="pbj-col pbj-col-tahun">{{ $a->tahun ?? '-' }}</div>
      <div class="pbj-col pbj-col-unit">{{ $unitName }}</div>
      <div class="pbj-col pbj-col-job">{{ $a->nama_pekerjaan ?? '-' }}</div>
      <div class="pbj-col pbj-col-metode">
        <span class="metode-badge">{{ $a->metode_pbj ?? $a->jenis_pengadaan ?? $a->metode ?? '-' }}</span>
      </div>
      <div class="pbj-col pbj-col-nilai">{{ $nilaiText }}</div>
      <div class="pbj-col pbj-col-status">
        <span class="{{ $spClass }}">{{ $a->status_pekerjaan ?? '-' }}</span>
      </div>
      <div class="pbj-col pbj-col-aksi">
        <button type="button" class="aksi-btn aksi-info" onclick='openDetailModal(@json($payload))' title="Detail">
          <i class="bi bi-info-circle-fill"></i>
        </button>
      </div>
    </div>
  @empty
    <div style="text-align:center; padding:32px; color:#94a3b8;">
      Tidak ada data arsip publik yang sesuai filter.
    </div>
  @endforelse

  {{-- Pagination --}}
  <div class="pbj-foot">
    <div class="pbj-foot-left">
      Halaman {{ $arsips->currentPage() }} dari {{ $arsips->lastPage() }}
      &bull; Menampilkan {{ $arsips->count() }} dari {{ $arsips->total() }} data
    </div>
    <div class="pbj-pager">
      @php
        $current  = $arsips->currentPage();
        $last     = $arsips->lastPage();
        $start    = max(1, $current - 2);
        $end      = min($last, $current + 2);
        $prevHref = $arsips->onFirstPage() ? '#' : $arsips->appends($qs)->url($current - 1);
        $nextHref = $arsips->hasMorePages() ? $arsips->appends($qs)->url($current + 1) : '#';
      @endphp
      <a class="pbj-page-btn {{ $arsips->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $prevHref }}">
        <i class="bi bi-chevron-left"></i>
      </a>
      @if($start > 1)
        <a class="pbj-page-btn" href="{{ $arsips->appends($qs)->url(1) }}">1</a>
        @if($start > 2)<span class="pbj-page-btn is-ellipsis">…</span>@endif
      @endif
      @for($i = $start; $i <= $end; $i++)
        <a class="pbj-page-btn {{ $i === $current ? 'is-active' : '' }}" href="{{ $arsips->appends($qs)->url($i) }}">{{ $i }}</a>
      @endfor
      @if($end < $last)
        @if($end < $last - 1)<span class="pbj-page-btn is-ellipsis">…</span>@endif
        <a class="pbj-page-btn" href="{{ $arsips->appends($qs)->url($last) }}">{{ $last }}</a>
      @endif
      <a class="pbj-page-btn {{ $arsips->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $nextHref }}">
        <i class="bi bi-chevron-right"></i>
      </a>
    </div>
  </div>
</div>

  </div>
</section>

{{-- ✅ MODAL DETAIL --}}
<div id="detailModal" class="pbj-modal-overlay" onclick="closeDetailModal()">
  <div class="pbj-modal" onclick="event.stopPropagation()">

    <div class="pbj-modal-head">
      <h3 class="pbj-modal-title" id="mTitle">-</h3>
      <button type="button" class="pbj-modal-close" onclick="closeDetailModal()">&times;</button>
    </div>

    <div class="pbj-modal-body">

      <div class="pbj-info-grid">
        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-envelope"></i></div>
          <div>
            <div class="pbj-info-k">Unit Kerja</div>
            <div class="pbj-info-v" id="mUnit">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-calendar3"></i></div>
          <div>
            <div class="pbj-info-k">Tahun Anggaran</div>
            <div class="pbj-info-v" id="mTahun">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-credit-card-2-front"></i></div>
          <div>
            <div class="pbj-info-k">ID RUP</div>
            <div class="pbj-info-v" id="mIdrup">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-bookmark-check"></i></div>
          <div>
            <div class="pbj-info-k">Status Pekerjaan</div>
            <div class="pbj-info-v" id="mStatus">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-person"></i></div>
          <div>
            <div class="pbj-info-k">Nama Rekanan</div>
            <div class="pbj-info-v" id="mRekanan">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic"><i class="bi bi-folder2"></i></div>
          <div>
            <div class="pbj-info-k">Jenis Pengadaan</div>
            <div class="pbj-info-v" id="mJenis">-</div>
          </div>
        </div>

        <div class="pbj-info-card">
          <div class="pbj-info-ic">
            <i class="bi bi-diagram-3"></i>
          </div>
          <div>
            <div class="pbj-info-k">Metode PBJ</div>
            <div class="pbj-info-v" id="mMetode">-</div>
          </div>
        </div>
        </div>

      <div class="pbj-divider"></div>

      <div class="pbj-section-title">Informasi Anggaran</div>
      <div class="pbj-budget-grid">
        <div class="pbj-budget-card">
          <div class="pbj-budget-k">Pagu Anggaran</div>
          <div class="pbj-budget-v" id="mPagu">-</div>
        </div>
        <div class="pbj-budget-card">
          <div class="pbj-budget-k">HPS</div>
          <div class="pbj-budget-v" id="mHps">-</div>
        </div>
        <div class="pbj-budget-card">
          <div class="pbj-budget-k">Nilai Kontrak</div>
          <div class="pbj-budget-v" id="mKontrak">-</div>
        </div>
      </div>

      <div class="pbj-divider"></div>

      <div class="pbj-section-title">Dokumen Pengadaan</div>

      <div class="pbj-docs-grid" id="mDocs"></div>

      <div id="mDocsEmpty" style="margin-top:10px;opacity:.85;display:none;">
        Tidak ada dokumen yang diupload.
      </div>

      <div class="pbj-divider" id="mDocNoteDivider" style="display:none;"></div>
      <div id="mDocNoteBox" style="display:none;">
        <div class="pbj-section-title">Dokumen tidak dipersyaratkan</div>
        <div style="opacity:.85;" id="mDocNote">-</div>
      </div>

    </div>
  </div>
</div>

<style>

/* ── PBJ Table Grid (Home) ── */
.pbj-card {
  background: #fff;
  border: 1px solid #e8eef3;
  border-radius: 16px;
  overflow-x: auto;
  overflow-y: auto;
  max-height: 620px;
}

.pbj-tbl-head,
.pbj-tbl-row {
  display: grid;
  grid-template-columns: 1fr 1.8fr 1.4fr 1.2fr 1.2fr 1.2fr 80px;
  align-items: center;
  column-gap: 14px;
  padding: 0 16px;
  min-width: 820px;
}

.pbj-tbl-head {
  background: #184f61;
  min-height: 52px;
  position: sticky;
  top: 0;
  z-index: 2;
}

.pbj-tbl-head .pbj-col {
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: .3px;
  white-space: nowrap;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.pbj-tbl-head .pbj-col,
.pbj-tbl-row .pbj-col {
  text-align: left !important;
  justify-content: flex-start !important;
}

.pbj-col-tahun {
  text-align: left !important;
}

.pbj-tbl-head .pbj-col-nilai {
  display: flex;
  align-items: center;
  gap: 4px;
}

.pbj-sort-btn {
  width: 28px; height: 28px;
  border: none; background: transparent;
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; border-radius: 8px;
  color: #fff; transition: .15s; padding: 0;
}
.pbj-sort-btn:hover { background: rgba(255,255,255,.15); }
.pbj-sort-btn i { font-size: 16px; }

.pbj-tbl-row {
  min-height: 64px;
  border-top: 1px solid #eef3f6;
  background: #fff;
  transition: background .12s;
}
.pbj-tbl-row:hover { background: #f8fbfe; }

.pbj-col {
  font-size: 14px;
  color: #1e293b;
  min-width: 0;
  overflow-wrap: anywhere;
  text-align: left;
}
.pbj-col-tahun { font-weight: 700; color: #374151; }
.pbj-col-unit  { color: #374151; font-weight: 600; font-size: 13px; }
.pbj-col-nilai { font-weight: 700; color: #184f61; white-space: nowrap; }
.pbj-col-metode { display: flex; align-items: left; justify-content: flex-start; }
.pbj-col-status { display: flex; align-items: center; justify-content: flex-start; }
.pbj-col-aksi   { display: flex; align-items: center; justify-content: flex-start; }

.metode-badge {
  display: inline-flex;
  align-items: left;
  justify-content: left;
  width: 100%;
  max-width: 160px;
  min-height: 36px;
  padding: 5px 10px;
  border-radius: 8px;
  background: #dbeafe;
  color: #1e40af;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.4;
  text-align: left;
  word-break: break-word;
  box-sizing: border-box;
}

.sp-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 100px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}
.sp-plan   { background: #fef9c3; color: #854d0e; }
.sp-select { background: #ede9fe; color: #5b21b6; }
.sp-do     { background: #fee2e2; color: #b91c1c; }
.sp-done   { background: #dcfce7; color: #15803d; }

.aksi-btn {
  width: 34px; height: 34px;
  border: 1px solid #e8eef3;
  border-radius: 10px;
  background: #f8fafc;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 15px;
  color: #374151;
  transition: .15s;
  padding: 0;
}
.aksi-btn:hover { transform: translateY(-1px); }
.aksi-info:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }

.pbj-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-top: 1px solid #eef3f6;
  position: sticky;
  bottom: 0;
  background: #fff;
  z-index: 2;
  min-width: 820px;
}
.pbj-foot-left { font-size: 13px; color: #64748b; }
  #mDocs.pbj-docs-grid{ display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
  @media (max-width: 900px){ #mDocs.pbj-docs-grid{ grid-template-columns: 1fr; } }

  #mDocs .pbj-doc-card{
    border:1px solid rgba(0,0,0,.08);
    background:#fff;
    border-radius:16px;
    padding:12px 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
  }
  #mDocs .pbj-doc-left{ display:flex; align-items:center; gap:12px; min-width:0; flex:1; }
  #mDocs .pbj-doc-ic{
    width:44px;height:44px;border-radius:16px;display:grid;place-items:center;
    background:#f8fbfd;border:1px solid rgba(0,0,0,.06);flex:0 0 auto;
  }
  #mDocs .pbj-doc-name{ min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:700; line-height:1.3; }
  #mDocs .pbj-doc-act{
    width:40px;height:40px;border-radius:14px;display:grid;place-items:center;
    background:#f8fbfd;border:1px solid rgba(0,0,0,.08);color:#0f172a;text-decoration:none;flex:0 0 auto;
  }
  #mDocs .pbj-doc-act i{ font-size:16px; line-height:1; display:block; }
  #mDocs .pbj-doc-act:hover{ background:#eef6f8; }

  .pbj-page-btn.is-disabled{ opacity:.5; pointer-events:none; cursor:not-allowed; }
  .pbj-page-btn.is-ellipsis{ pointer-events:none; }
  .pbj-pager{ display:flex; gap:10px; align-items:center; }

  .pbj-page-btn{
    min-width:44px;
    height:44px;
    padding:0 14px;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid rgba(15, 23, 42, .14);
    background:#fff;
    color:#0f172a;
    text-decoration:none;
    font-weight:700;
    transition: all .15s ease;
  }

  .pbj-page-btn:hover{ background:#f3f6f9; }

  .pbj-page-btn.is-active{
    background:#0b4f6c;
    border-color:#0b4f6c;
    color:#fff;
    box-shadow:0 8px 18px rgba(11, 79, 108, .18);
  }
  .pbj-page-btn.is-active:hover{
    background:#0a465f;
    border-color:#0a465f;
  }

  .pbj-page-btn.is-disabled{
    opacity:.5;
    pointer-events:none;
    cursor:not-allowed;
  }

  .pbj-page-btn.is-ellipsis{
    border-color:transparent;
    background:transparent;
    min-width:auto;
    padding:0 6px;
    box-shadow:none;
  }
</style>
@endsection

@push('scripts')
<script>
// SORT NILAI KONTRAK (client-side untuk page aktif)
document.addEventListener('DOMContentLoaded', () => {
  const btn   = document.getElementById('sortNilaiBtn');
  const icon  = document.getElementById('sortNilaiIcon');
  const tbody = document.querySelector('.pbj-table tbody');
  if (!btn || !icon || !tbody) return;

  let direction = 'desc';

  function parseRupiah(text){
    return parseInt((text || '').replace(/[^\d]/g, '')) || 0;
  }

  btn.addEventListener('click', () => {
    const container = document.querySelector('.pbj-card');
    const rows = Array.from(container.querySelectorAll('.pbj-tbl-row'));

    rows.sort((a, b) => {
      const aVal = parseInt(a.dataset.moneyraw || '0');
      const bVal = parseInt(b.dataset.moneyraw || '0');
      return direction === 'desc' ? bVal - aVal : aVal - bVal;
    });

   const firstNonRow = container.querySelector('.pbj-foot');
    rows.forEach(row => container.insertBefore(row, firstNonRow));

    if(direction === 'desc'){
      direction = 'asc';
      icon.className = 'bi bi-sort-up';
    }else{
      direction = 'desc';
      icon.className = 'bi bi-sort-down-alt';
    }
  });
});

/* ======================
   ✅ FILTER AUTO-REFRESH (DEBOUNCE)
====================== */
document.addEventListener('DOMContentLoaded', () => {
  const btn  = document.getElementById('sortNilaiBtn');
  const icon = document.getElementById('sortNilaiIcon');
  if (!btn || !icon) return;

  let direction = '';

  btn.addEventListener('click', () => {
    const card = document.querySelector('.pbj-card');
    const foot = card.querySelector('.pbj-foot');
    const rows = Array.from(card.querySelectorAll('.pbj-tbl-row'));

    if (direction === '' || direction === 'desc') {
      direction = 'asc';
      icon.className = 'bi bi-sort-up';
    } else {
      direction = 'desc';
      icon.className = 'bi bi-sort-down-alt';
    }

    rows.sort((a, b) => {
      const aVal = parseInt(a.dataset.moneyraw || '0');
      const bVal = parseInt(b.dataset.moneyraw || '0');
      return direction === 'asc' ? aVal - bVal : bVal - aVal;
    });

    rows.forEach(row => card.insertBefore(row, foot));
  });
});
/* ======================
   MODAL
====================== */
function openDetailModal(payload){
  const modal = document.getElementById('detailModal');
  if(!modal) return;

  document.getElementById('mTitle').textContent   = payload?.title   ?? '-';
  document.getElementById('mUnit').textContent    = payload?.unit    ?? '-';
  document.getElementById('mTahun').textContent   = payload?.tahun   ?? '-';
  document.getElementById('mIdrup').textContent   = payload?.idrup   ?? '-';
  document.getElementById('mStatus').textContent  = payload?.status  ?? '-';
  document.getElementById('mRekanan').textContent = payload?.rekanan ?? '-';
  document.getElementById('mJenis').textContent   = payload?.jenis   ?? '-';

  document.getElementById('mPagu').textContent    = payload?.pagu    ?? '-';
  document.getElementById('mHps').textContent     = payload?.hps     ?? '-';
  document.getElementById('mKontrak').textContent = payload?.kontrak ?? '-';

  const docsWrap  = document.getElementById('mDocs');
  const docsEmpty = document.getElementById('mDocsEmpty');
  docsWrap.innerHTML = '';

  const toViewerUrl = (storageUrl) => `/file-viewer?file=${encodeURIComponent(storageUrl)}&mode=public`;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[c]));

  const docsObj = payload?.docs || {};
  let totalDocs = 0;

  Object.keys(docsObj).forEach(field => {
    const arr = Array.isArray(docsObj[field]) ? docsObj[field] : [];
    arr.forEach(it => {
      if(!it?.url) return;
      totalDocs++;

      const name = it?.name || 'Dokumen';
      const viewer = toViewerUrl(it.url);

      const card = document.createElement('div');
      card.className = 'pbj-doc-card';
      card.innerHTML = `
        <div class="pbj-doc-left">
          <span class="pbj-doc-ic"><i class="bi bi-file-earmark"></i></span>
          <span class="pbj-doc-name" title="${esc(name)}">${esc(name)}</span>
        </div>

        <a href="${esc(viewer)}"
           target="_blank"
           class="pbj-doc-act"
           rel="noopener"
           title="Lihat Dokumen"
           aria-label="Lihat Dokumen"
           onclick="event.stopPropagation();"
        >
          <i class="bi bi-eye"></i>
        </a>
      `;
      docsWrap.appendChild(card);
    });
  });

  docsEmpty.style.display = totalDocs ? 'none' : 'block';

  const note = (payload?.docnote || '').trim();
  const noteDivider = document.getElementById('mDocNoteDivider');
  const noteBox = document.getElementById('mDocNoteBox');
  const noteEl = document.getElementById('mDocNote');

  if(note){
    noteEl.textContent = note;
    noteDivider.style.display = 'block';
    noteBox.style.display = 'block';
  }else{
    noteEl.textContent = '-';
    noteDivider.style.display = 'none';
    noteBox.style.display = 'none';
  }

  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeDetailModal(){
  const modal = document.getElementById('detailModal');
  if(!modal) return;
  modal.classList.remove('show');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeDetailModal();
});
</script>
@endpush
