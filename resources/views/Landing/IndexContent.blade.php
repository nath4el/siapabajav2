{{-- resources/views/Landing/IndexContent.blade.php --}}

{{-- HERO --}}
<section id="Dashboard" class="hero">
  <div class="container">
    <div class="hero-grid">
      <div>
        <h1>
          Sistem Informasi Arsip<br/>
          Pengadaan Barang dan Jasa
          <span class="u">Universitas Jenderal Soedirman</span>
        </h1>

        <p>
          SIAPABAJA merupakan sistem informasi berbasis web yang digunakan untuk mengelola dan mengarsipkan dokumen
          pengadaan barang dan jasa di lingkungan Universitas Jenderal Soedirman.
        </p>

        <a class="btn btn-primary" href="#arsip">Lihat Arsip Terbaru</a>
      </div>

      <div class="hero-illustration">
        <img
          src="{{ asset('image/amico.png') }}"
          alt="Ilustrasi Arsip"
          class="hero-img"
        >
      </div>
    </div>
  </div>
</section>

@php
  use Illuminate\Support\Str;

  /**
   * ✅ Ambil 5 arsip PUBLIK paling update
   */
  $arsipPublik = \App\Models\Pengadaan::with('unit')
    ->where('status_arsip', 'Publik')
    ->orderByDesc('updated_at')
    ->limit(5)
    ->get();

  function idDate($dt){
    if(!$dt) return '-';
    try{
      $t = \Carbon\Carbon::parse($dt);
      $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      return (int)$t->format('d').' '.$bulan[(int)$t->format('m')].' '.$t->format('Y');
    }catch(\Throwable $e){
      return '-';
    }
  }

  function rupiah($v){
    if($v === null || $v === '') return '-';
    if(is_string($v) && Str::contains($v, 'Rp')) return $v;
    $n = is_numeric($v) ? (float)$v : (float)preg_replace('/[^\d]/', '', (string)$v);
    return 'Rp '.number_format($n, 0, ',', '.');
  }

  /**
   * ✅ Builder dokumen untuk modal
   */
  function buildDokumenListForLanding($pengadaan){
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

  function buildDocNoteForLanding($pengadaan){
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

{{-- ARSIP LIST --}}
<section id="arsip">
  <div class="container">
    <div class="section-title">
      <h2>Arsip Pengadaan Barang dan Jasa</h2>
      <p>Daftar dokumen pengadaan barang dan jasa yang dapat diakses oleh masyarakat.</p>
    </div>

    <div class="cards">
      @if($arsipPublik->count() === 0)
        <div style="opacity:.85;padding:18px;border-radius:14px;background:#fff;border:1px solid rgba(0,0,0,.08);">
          Belum ada arsip publik yang bisa ditampilkan.
        </div>
      @endif

      @foreach($arsipPublik as $item)
        @php
          $unitName = $item->unit?->nama ?? '-';

          $payload = [
            'title'   => $item->nama_pekerjaan ?? '-',
            'unit'    => $unitName,
            'tahun'   => $item->tahun ?? '-',
            'idrup'   => $item->id_rup ?? '-',
            'status'  => $item->status_pekerjaan ?? '-',
            'rekanan' => $item->nama_rekanan ?? '-',
            'jenis'   => $item->jenis_pengadaan ?? '-',
            'pagu'    => rupiah($item->pagu_anggaran),
            'metode'  => $item->metode_pbj ?? $item->metode_pengadaan ?? $item->metode ?? $item->jenis_pengadaan ?? '-',
            'hps'     => rupiah($item->hps),
            'kontrak' => rupiah($item->nilai_kontrak),
            'docnote' => buildDocNoteForLanding($item),
            'docs'    => buildDokumenListForLanding($item),
          ];

          $dateLabel = idDate($item->updated_at ?? $item->created_at);
        @endphp

        <article class="card">
          <div class="card-top">
            <div>
              <div class="card-date">{{ $dateLabel }}</div>
              <div class="card-title">{{ $item->nama_pekerjaan ?? '-' }}</div>
            </div>

            <button
              type="button"
              class="btn-detail js-open-detail"
              data-payload='@json($payload)'
            >
              <i class="bi bi-info-circle"></i> Lihat Detail
            </button>
          </div>

          <div class="card-meta">
            <div class="meta-line"><span class="meta-k">Unit Kerja</span> : <span class="meta-v">{{ $unitName }}</span></div>
            <div class="meta-line"><span class="meta-k">ID RUP</span> : <span class="meta-v">{{ $item->id_rup ?? '-' }}</span></div>
            <div class="meta-line"><span class="meta-k">Status Pekerjaan</span> : <span class="meta-v">{{ $item->status_pekerjaan ?? '-' }}</span></div>
            <div class="meta-line"><span class="meta-k">Nilai Kontrak</span> : <span class="meta-v">{{ rupiah($item->nilai_kontrak) }}</span></div>
            <div class="meta-line"><span class="meta-k">Rekanan</span> : <span class="meta-v">{{ $item->nama_rekanan ?? '-' }}</span></div>
          </div>
        </article>
      @endforeach
    </div>

    <div class="more">
      {{-- ✅ PAKAI ROUTE (nanti kamu yang set di routes/web.php) --}}
      <a href="{{ route('landing.pbj') }}">
        Lihat Selengkapnya <span style="font-size:18px">›</span>
      </a>
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
          <div class="pbj-info-ic">
            <i class="bi bi-diagram-3"></i>
          </div>
          <div>
            <div class="pbj-info-k">Metode PBJ</div>
            <div class="pbj-info-v" id="mMetode">-</div>
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
</style>

@php
  /**
   * ==========================================
   * ✅ STATISTIKA REALTIME (PUBLIK)
   * ==========================================
   */

  $statusList = ["Perencanaan","Pemilihan","Pelaksanaan","Selesai"];

  $statYearOptions = \App\Models\Pengadaan::where('status_arsip','Publik')
    ->whereNotNull('tahun')
    ->select('tahun')->distinct()
    ->orderBy('tahun','desc')
    ->pluck('tahun')
    ->map(fn($t)=>(int)$t)
    ->values()
    ->all();

  $statUnitOptions = \App\Models\Unit::orderBy('nama')
    ->get(['id','nama'])
    ->map(fn($u)=>['id'=>$u->id,'nama'=>$u->nama])
    ->values()
    ->all();

  $METHOD_KEYS = [
    'Pengadaan Langsung',
    'Penunjukan Langsung',
    'E-Purchasing/E-Catalog',
    'Tender Terbatas',
    'Tender Terbuka',
    'Swakelola',
  ];

  $normalizeMethod = function($raw){
    $s = strtolower(trim((string)$raw));
    if($s === '') return null;

    $s = str_replace(['_', '-'], ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);

    if(str_contains($s, 'pengadaan langsung')) return 'Pengadaan Langsung';
    if(str_contains($s, 'penunjukan langsung')) return 'Penunjukan Langsung';
    if(str_contains($s, 'e purchasing') || str_contains($s, 'e-purchasing') || str_contains($s, 'e catalog') || str_contains($s, 'e-catalog') || str_contains($s, 'ecatalog')) return 'E-Purchasing/E-Catalog';
    if(str_contains($s, 'tender terbatas')) return 'Tender Terbatas';
    if(str_contains($s, 'tender terbuka')) return 'Tender Terbuka';
    if($s === 'tender') return 'Tender Terbuka';
    if(str_contains($s, 'swakelola')) return 'Swakelola';

    return null;
  };

  $makeKey = fn($year) => $year === null ? 'all' : (string)(int)$year;

  $donutData = [];
  $barData   = [];

  $yearsForBuild = array_merge([null], $statYearOptions);
  $unitsForBuild = array_merge([null], array_map(fn($x)=>$x['id'], $statUnitOptions));

  foreach($yearsForBuild as $y){
    $yKey = $makeKey($y);
    $donutData[$yKey] = [];
    $barData[$yKey]   = [];

    foreach($unitsForBuild as $uid){
      $uKey = $uid === null ? 'all' : (string)(int)$uid;

      $q = \App\Models\Pengadaan::query()->where('status_arsip','Publik');
      if($y !== null)   $q->where('tahun', (int)$y);
      if($uid !== null) $q->where('unit_id', (int)$uid);

      $statusCounts = (clone $q)
        ->selectRaw('status_pekerjaan as s, COUNT(*) as c')
        ->groupBy('status_pekerjaan')
        ->pluck('c','s')
        ->toArray();

      $donutData[$yKey][$uKey] = array_map(function($st) use ($statusCounts){
        return (int)($statusCounts[$st] ?? 0);
      }, $statusList);

      $jenisCounts = (clone $q)
        ->selectRaw('jenis_pengadaan as j, COUNT(*) as c')
        ->groupBy('jenis_pengadaan')
        ->pluck('c','j')
        ->toArray();

      $bucket = array_fill_keys($METHOD_KEYS, 0);
      foreach($jenisCounts as $rawJenis => $cnt){
        $k = $normalizeMethod($rawJenis);
        if($k && array_key_exists($k, $bucket)){
          $bucket[$k] += (int)$cnt;
        }
      }

      $barData[$yKey][$uKey] = array_values($bucket);
    }
  }
@endphp

{{-- STATISTIKA (REALTIME) --}}
<section class="stats-wrap" id="statistika">
  <div class="container">
    <div class="section-title">
      <h2>Statistik</h2>
    </div>

    <div class="stats-2col">
      @include('Partials.statistika-donut', ['title' => 'Status Pekerjaan', 'donutId' => 'landingDonut'])
      @include('Partials.statistika-bar',   ['title' => 'Metode Pengadaan', 'barId' => 'landingBar'])
    </div>
  </div>
</section>

{{-- REGULASI --}}
<section class="reg-wrap" id="regulasi">
  @php
    $regulasi = [
      [
        'judul' => '01 Perpres-No-12-Tahun-2021 Perubahan Atas Peraturan Presiden Nomor 16 Tahun 2018 tentang PBJ Pemerintah',
        'file'  => '01 Perpres-No-12-Tahun-2021 Perubahan Atas Peraturan Presiden Nomor 16 Tahun 2018 tentang PBJ Pemerintah.pdf'
      ],
      [
        'judul' => '02 Peraturan LKPP No. 12 Tahun 2021 Tentang Pedoman Pelaksanaan PBJ Pemerintah Melalui Penyedia',
        'file'  => '02 Peraturan LKPP No. 12 Tahun 2021 Tentang Pedoman Pelaksanaan PBJ Pemerintah Melalui Penyedia.pdf'
      ],
      [
        'judul' => '03 Peraturan Rektor Unsoed No. 2 Tahun 2023 Tentang  Pedoman Pengadaan BarangJasa Unsoed',
        'file'  => '03 Peraturan Rektor Unsoed No. 2 Tahun 2023 Tentang  Pedoman Pengadaan BarangJasa Unsoed.pdf'
      ],
    ];
  @endphp

  <div class="container">
    <div class="section-title">
      <h2>Regulasi</h2>
    </div>
  </div>

  <div class="reg-card">
    @foreach($regulasi as $item)
      <a href="{{ asset('regulasi/'.$item['file']) }}" target="_blank" class="reg-item">
        <div class="reg-icon"><i class="bi bi-file-earmark-text"></i></div>
        <div class="reg-text">{{ $item['judul'] }}</div>
      </a>
    @endforeach
  </div>
</section>

@push('scripts')
<script>
/* ======================
   MODAL (pbj-modal-*)
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

window.openDetailModal = openDetailModal;
window.closeDetailModal = closeDetailModal;

document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closeDetailModal();
});

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.js-open-detail').forEach(btn => {
    btn.addEventListener('click', function(){
      let payload = {};
      try{ payload = JSON.parse(this.dataset.payload || '{}') || {}; }
      catch(e){ payload = {}; }
      openDetailModal(payload);
    });
  });
});

/* =========================
   ✅ STATISTIKA (DB) + CONNECT KE landing.pbj
========================= */
const PBJ_BASE_URL = @json(route('landing.pbj'));

const DONUT_DATA = @json($donutData);
const BAR_DATA   = @json($barData);
const YEAR_OPTIONS = @json($statYearOptions);
const UNIT_OPTIONS = @json($statUnitOptions);

const STATUS_LABELS = ['Perencanaan','Pemilihan','Pelaksanaan','Selesai'];

const METHOD_LABELS = [
  'Pengadaan Langsung',
  'Penunjukan Langsung',
  'E-Purchasing/E-Catalog',
  'Tender Terbatas',
  'Tender Terbuka',
  'Swakelola'
];

const BAR_LABELS = [
  ["Pengadaan","Langsung"],
  ["Penunjukan","Langsung"],
  ["E-Purchasing/","E-Catalog"],
  ["Tender","Terbatas"],
  ["Tender","Terbuka"],
  ["Swakelola"]
];

const pickData = (obj, yearKey, unitKey, fallbackLen) => {
  if(obj?.[yearKey]?.[unitKey]) return obj[yearKey][unitKey];
  if(obj?.[yearKey]?.all) return obj[yearKey].all;
  if(obj?.all?.[unitKey]) return obj.all[unitKey];
  if(obj?.all?.all) return obj.all.all;
  return new Array(fallbackLen).fill(0);
};

const ensureOptions = (selectEl, items, type) => {
  if(!selectEl) return;

  let html = '';
  if(type === 'year'){
    html += `<option value="">Semua Tahun</option>`;
    (items || []).forEach(y => { html += `<option value="${String(y)}">${String(y)}</option>`; });
  }else if(type === 'unit'){
    html += `<option value="">Semua Unit</option>`;
    (items || []).forEach(u => { html += `<option value="${String(u.id)}">${String(u.nama)}</option>`; });
  }
  selectEl.innerHTML = html;
};

const getYearUnitFilters = (yearEl, unitEl) => {
  const yearVal = (yearEl?.value || '').trim();
  const unitVal = (unitEl?.value || '').trim();
  return { tahun: yearVal ? yearVal : '', unit_id: unitVal ? unitVal : '' };
};

const goToPBJ = (params) => {
  const url = new URL(PBJ_BASE_URL, window.location.origin);
  Object.keys(params || {}).forEach(k => {
    const v = params[k];
    if(v !== undefined && v !== null && String(v).trim() !== ''){
      url.searchParams.set(k, String(v));
    }
  });
  url.searchParams.delete('page');
  window.location.href = url.toString();
};

document.addEventListener('DOMContentLoaded', () => {
  const donutYearEl = document.getElementById('donutYear');
  const donutUnitEl = document.getElementById('donutUnit');
  const barYearEl   = document.getElementById('barYear');
  const barUnitEl   = document.getElementById('barUnit');

  ensureOptions(donutYearEl, YEAR_OPTIONS, 'year');
  ensureOptions(barYearEl,   YEAR_OPTIONS, 'year');
  ensureOptions(donutUnitEl, UNIT_OPTIONS, 'unit');
  ensureOptions(barUnitEl,   UNIT_OPTIONS, 'unit');

  if(donutYearEl) donutYearEl.value = '';
  if(barYearEl)   barYearEl.value = '';
  if(donutUnitEl) donutUnitEl.value = '';
  if(barUnitEl)   barUnitEl.value = '';

  const donutCtx = document.getElementById('landingDonut');
  let donutChart = null;

  if(donutCtx && window.Chart){
    donutChart = new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: STATUS_LABELS,
        datasets: [{
          data: pickData(DONUT_DATA, 'all', 'all', 4),
          backgroundColor: ['#0B4A5E', '#111827', '#F6C100', '#D6A357'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',
        layout: { padding: { right: 70 } },
        plugins: {
          legend: { display: true, position: 'right' },
          tooltip: { enabled: true }
        },
        onClick: function(evt, elements){
          if(!elements || !elements.length) return;
          const idx = elements[0].index;
          const status = STATUS_LABELS[idx] || '';
          if(!status) return;

          const f = getYearUnitFilters(donutYearEl, donutUnitEl);
          goToPBJ({ tahun: f.tahun, unit_id: f.unit_id, status_pekerjaan: status });
        }
      }
    });

    const updateDonut = () => {
      const yearKey = (donutYearEl?.value || '').trim() === '' ? 'all' : String(donutYearEl.value);
      const unitKey = (donutUnitEl?.value || '').trim() === '' ? 'all' : String(donutUnitEl.value);
      donutChart.data.datasets[0].data = pickData(DONUT_DATA, yearKey, unitKey, 4);
      donutChart.update();
    };

    donutYearEl?.addEventListener('change', updateDonut);
    donutUnitEl?.addEventListener('change', updateDonut);
  }

  const barCtx = document.getElementById('landingBar');
  let barChart = null;

  const splitLabel = (value) => Array.isArray(value) ? value : String(value ?? '');

  if(barCtx && window.Chart){
    barChart = new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: BAR_LABELS,
        datasets: [{
          label: 'Semua Tahun',
          data: pickData(BAR_DATA, 'all', 'all', 6),
          backgroundColor: '#F6C100',
          borderWidth: 0,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' }, tooltip: { enabled: true } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: {
            ticks: {
              maxRotation: 0, minRotation: 0, autoSkip: false, padding: 6,
              callback: function (value) {
                const raw = this.getLabelForValue(value);
                return splitLabel(raw);
              }
            },
            grid: { display: false }
          }
        },
        onClick: function(evt, elements){
          if(!elements || !elements.length) return;
          const idx = elements[0].index;
          const method = METHOD_LABELS[idx] || '';
          if(!method) return;

          const f = getYearUnitFilters(barYearEl, barUnitEl);
          goToPBJ({ tahun: f.tahun, unit_id: f.unit_id, q: method });
        }
      }
    });

    const updateBar = () => {
      const yearKey = (barYearEl?.value || '').trim() === '' ? 'all' : String(barYearEl.value);
      const unitKey = (barUnitEl?.value || '').trim() === '' ? 'all' : String(barUnitEl.value);
      barChart.data.datasets[0].data = pickData(BAR_DATA, yearKey, unitKey, 6);
      barChart.data.datasets[0].label = (yearKey === 'all') ? 'Semua Tahun' : yearKey;
      barChart.update();
    };

    barYearEl?.addEventListener('change', updateBar);
    barUnitEl?.addEventListener('change', updateBar);
  }
});
</script>
@endpush