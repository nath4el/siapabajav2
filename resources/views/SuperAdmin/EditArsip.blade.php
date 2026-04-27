<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Arsip - SIAPABAJA</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
</head>

<body class="dash-body">
@php
  $arsip = $arsip ?? null;
  $pengadaan = $pengadaan ?? $arsip;
  $arsip = $pengadaan;

  $superAdminName = $superAdminName ?? (auth()->user()->name ?? "Super Admin");

  $tahunOptions = $tahunOptions ?? [date('Y'), date('Y')-1, date('Y')-2, date('Y')-3, date('Y')-4];
  $unitOptions = $unitOptions ?? [];

  $jenisPengadaanOptions = $jenisPengadaanOptions ?? [
  "Pengadaan Barang",
  "Pengadaan Pekerjaan Konstruksi",
  "Pengadaan Jasa Konsultasi",
  "Pengadaan Jasa Lainnya",
];

$metodePengadaanOptions = $metodePengadaanOptions ?? [
    'Pengadaan Langsung',
    'Penunjukan Langsung',
    'E-Purchasing / E-Catalogue',
    'Tender Terbatas',
    'Tender Terbuka',
    'Swakelola',
];
  $statusPekerjaanOptions = $statusPekerjaanOptions ?? ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];

  $get = function($source, string $key){
    if(!$source) return null;
    if(is_object($source)) return $source->$key ?? null;
    if(is_array($source)) return $source[$key] ?? null;
    return null;
  };

  $val = function(string $oldKey, array $attrs = []) use ($arsip, $get){
    $old = old($oldKey);
    if($old !== null) return $old;
    if(!$arsip) return null;
    foreach($attrs as $a){
      $v = $get($arsip, $a);
      if($v !== null && $v !== '') return $v;
    }
    return null;
  };

  $arsipId = $arsip ? (int)$get($arsip,'id') : 0;
  $statusArsipVal = $val('status_arsip', ['status_arsip']) ?? 'Publik';

  $selectedUnitName = old('unit_kerja');
  if($selectedUnitName === null || $selectedUnitName === ''){
    $selectedUnitName =
      ($arsip && isset($arsip->unit) && isset($arsip->unit->nama) ? $arsip->unit->nama : null)
      ?? ($arsip ? ($get($arsip,'unit_kerja') ?? null) : null);
  }
  $selectedUnitName = is_string($selectedUnitName) ? trim($selectedUnitName) : $selectedUnitName;

  $baseName = function($path){
    if(!$path) return '';
    return basename($path);
  };

  $parseFiles = function($raw){
    if(!$raw) return [];
    if(is_array($raw)) return array_values(array_filter($raw));
    if(is_string($raw)){
      $trim = trim($raw);
      if(strlen($trim) && ($trim[0] === '[' || $trim[0] === '{')){
        $decoded = json_decode($trim, true);
        if(is_array($decoded)){
          if(array_keys($decoded) !== range(0, count($decoded)-1)){
            $decoded = array_values($decoded);
          }
          return array_values(array_filter($decoded));
        }
      }
      return [$raw];
    }
    return [];
  };

  $parseList = function($raw){
    if(!$raw) return [];
    if(is_array($raw)) return array_values(array_filter($raw));
    if(is_string($raw)){
      $trim = trim($raw);
      if(strlen($trim) && ($trim[0] === '[' || $trim[0] === '{')){
        $decoded = json_decode($trim, true);
        if(is_array($decoded)){
          if(array_keys($decoded) !== range(0, count($decoded)-1)){
            $decoded = array_values($decoded);
          }
          return array_values(array_filter($decoded));
        }
      }
      if(strpos($trim, ',') !== false){
        $arr = array_map('trim', explode(',', $trim));
        return array_values(array_filter($arr));
      }
      return [$trim];
    }
    return [];
  };

  $eSelectedRaw = old('dokumen_tidak_dipersyaratkan');
  if($eSelectedRaw === null){
    $eSelectedRaw = $val('dokumen_tidak_dipersyaratkan', ['dokumen_tidak_dipersyaratkan']) ?? ($arsip ? $get($arsip,'dokumen_tidak_dipersyaratkan') : null);
  }
  $eSelected = $parseList($eSelectedRaw);

  $docSessions = [
    ['key'=>'dokumen_kak','label'=>'Kerangka Acuan Kerja atau KAK'],
    ['key'=>'dokumen_hps','label'=>'Harga Perkiraan Sendiri atau HPS'],
    ['key'=>'dokumen_spesifikasi_teknis','label'=>'Spesifikasi Teknis'],
    ['key'=>'dokumen_rancangan_kontrak','label'=>'Rancangan Kontrak'],
    ['key'=>'dokumen_lembar_data_kualifikasi','label'=>'Lembar Data Kualifikasi'],
    ['key'=>'dokumen_lembar_data_pemilihan','label'=>'Lembar Data Pemilihan'],
    ['key'=>'dokumen_daftar_kuantitas_harga','label'=>'Daftar Kuantitas dan Harga'],
    ['key'=>'dokumen_jadwal_lokasi_pekerjaan','label'=>'Jadwal dan Lokasi Pekerjaan'],
    ['key'=>'dokumen_gambar_rancangan_pekerjaan','label'=>'Gambar Rancangan Pekerjaan'],
    ['key'=>'dokumen_amdal','label'=>'Dokumen Analisis Mengenai Dampak Lingkungan atau AMDAL'],
    ['key'=>'dokumen_penawaran','label'=>'Dokumen Penawaran'],
    ['key'=>'surat_penawaran','label'=>'Surat Penawaran'],
    ['key'=>'dokumen_kemenkumham','label'=>'Sertifikat atau Lisensi Kemenkumham'],
    ['key'=>'ba_pemberian_penjelasan','label'=>'Berita Acara Pemberian Penjelasan'],
    ['key'=>'ba_pengumuman_negosiasi','label'=>'Berita Acara Pengumuman Negosiasi'],
    ['key'=>'ba_sanggah_banding','label'=>'Berita Acara Sanggah dan Sanggah Banding'],
    ['key'=>'ba_penetapan','label'=>'Berita Acara Penetapan'],
    ['key'=>'laporan_hasil_pemilihan','label'=>'Laporan Hasil Pemilihan Penyedia'],
    ['key'=>'dokumen_sppbj','label'=>'Surat Penunjukan Penyedia Barang Jasa atau SPPBJ'],
    ['key'=>'surat_perjanjian_kemitraan','label'=>'Surat Perjanjian Kemitraan'],
    ['key'=>'surat_perjanjian_swakelola','label'=>'Surat Perjanjian Swakelola'],
    ['key'=>'surat_penugasan_tim_swakelola','label'=>'Surat Penugasan Tim Swakelola'],
    ['key'=>'dokumen_mou','label'=>'Nota Kesepahaman atau MoU'],
    ['key'=>'dokumen_kontrak','label'=>'Dokumen Kontrak'],
    ['key'=>'ringkasan_kontrak','label'=>'Ringkasan Kontrak'],
    ['key'=>'jaminan_pelaksanaan','label'=>'Surat Jaminan Pelaksanaan'],
    ['key'=>'jaminan_uang_muka','label'=>'Surat Jaminan Uang Muka'],
    ['key'=>'jaminan_pemeliharaan','label'=>'Surat Jaminan Pemeliharaan'],
    ['key'=>'surat_tagihan','label'=>'Surat Tagihan'],
    ['key'=>'surat_pesanan_epurchasing','label'=>'Surat Pesanan Elektronik atau E-Purchasing'],
    ['key'=>'dokumen_spmk','label'=>'Surat Perintah Mulai Kerja atau SPMK'],
    ['key'=>'dokumen_sppd','label'=>'Surat Perintah Perjalanan Dinas atau SPPD'],
    ['key'=>'laporan_pelaksanaan_pekerjaan','label'=>'Laporan Pelaksanaan Pekerjaan'],
    ['key'=>'laporan_penyelesaian_pekerjaan','label'=>'Laporan Penyelesaian Pekerjaan'],
    ['key'=>'bap','label'=>'Berita Acara Pembayaran atau BAP'],
    ['key'=>'bast_sementara','label'=>'Berita Acara Serah Terima Sementara atau BAST Sementara'],
    ['key'=>'bast_akhir','label'=>'Berita Acara Serah Terima Final atau BAST Final'],
    ['key'=>'dokumen_pendukung_lainya','label'=>'Dokumen Pendukung Lainya'],
  ];
@endphp

<div class="dash-wrap">
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

      <a class="dash-link {{ request()->routeIs('superadmin.kelola.akun') ? 'active' : '' }}"
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

  <main class="dash-main">
    <header class="dash-header">
      <div class="tp-header-actions">
        <a href="{{ route('superadmin.arsip') }}" class="tp-btn tp-btn-ghost tp-btn-fit">
          <i class="bi bi-arrow-left"></i>
          Kembali
        </a>
      </div>

      <h1>Edit Arsip Pengadaan Barang dan Jasa</h1>
      <p>Perbarui arsip PBJ</p>
    </header>

    <form id="editForm"
          action="{{ $arsip ? route('superadmin.arsip.update', $arsipId) : route('superadmin.pengadaan.store') }}"
          method="POST" class="tp-form" enctype="multipart/form-data">
      @csrf
      @if($arsip) @method('PUT') @endif

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title">
              <span>A. Informasi Umum</span>
            </div>
            <div class="tp-divider"></div>

            <div class="tp-grid">
              <div class="tp-field">
                <label class="tp-label">Tahun</label>
                <div class="tp-control">
                  <select name="tahun" class="tp-select" required>
                    <option value="" {{ $val('tahun',['tahun']) ? '' : 'selected' }} disabled hidden>Tahun</option>
                    @foreach($tahunOptions as $t)
                      <option value="{{ $t }}" @selected((string)$val('tahun',['tahun']) === (string)$t)>{{ $t }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

              <div class="tp-field">
                <label class="tp-label">Unit Kerja</label>
                <div class="tp-control">
                  <select name="unit_kerja" class="tp-select" required>
                    <option value="" {{ $selectedUnitName ? '' : 'selected' }} disabled hidden>Pilih Unit</option>
                    @foreach($unitOptions as $uname)
                      @php $uname = is_string($uname) ? trim($uname) : (string)$uname; @endphp
                      <option value="{{ $uname }}" @selected((string)$selectedUnitName === (string)$uname)>{{ $uname }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

              <div class="tp-field tp-full">
                <label class="tp-label">Nama Pekerjaan</label>
                <input type="text" name="nama_pekerjaan" class="tp-input"
                       value="{{ $val('nama_pekerjaan',['nama_pekerjaan']) }}"
                       placeholder="Nama Pekerjaan" />
              </div>

              <div class="tp-field">
                <label class="tp-label">ID RUP</label>
                <input type="text" name="id_rup" class="tp-input"
                       value="{{ $val('id_rup',['id_rup']) }}"
                       placeholder="RUP-xxxx-xxxx-xxx-xx" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Jenis Pengadaan</label>
                <div class="tp-control">
                  <select name="jenis_pengadaan" class="tp-select" required>
                    <option value="" {{ $val('jenis_pengadaan',['jenis_pengadaan']) ? '' : 'selected' }} disabled hidden>Pilih Jenis Pengadaan</option>
                    @foreach($jenisPengadaanOptions as $jp)
                      <option value="{{ $jp }}" @selected((string)$val('jenis_pengadaan',['jenis_pengadaan']) === (string)$jp)>{{ $jp }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

             <div class="tp-field">
  <label class="tp-label">Metode Pengadaan</label>
  <div class="tp-control">
    <select name="metode_pengadaan" class="tp-select" required>
      <option value="" {{ $val('metode_pengadaan',['metode_pengadaan']) ? '' : 'selected' }} disabled hidden>Pilih Metode Pengadaan</option>
      @foreach($metodePengadaanOptions as $mp)
        <option value="{{ $mp }}" @selected((string)$val('metode_pengadaan',['metode_pengadaan']) === (string)$mp)>{{ $mp }}</option>
      @endforeach
    </select>
    <i class="bi bi-chevron-down tp-icon"></i>
  </div>
</div>


              <div class="tp-field">
                <label class="tp-label">Status Pekerjaan</label>
                <div class="tp-control">
                  <select name="status_pekerjaan" class="tp-select" required>
                    <option value="" {{ $val('status_pekerjaan',['status_pekerjaan']) ? '' : 'selected' }} disabled hidden>Pilih Status Pekerjaan</option>
                    @foreach($statusPekerjaanOptions as $sp)
                      <option value="{{ $sp }}" @selected((string)$val('status_pekerjaan',['status_pekerjaan']) === (string)$sp)>{{ $sp }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title">
              <span>B. Status Akses Arsip</span>
            </div>
            <div class="tp-divider"></div>

            <div class="tp-grid" style="grid-template-columns: 1fr;">
              <div class="tp-field">
                <label class="tp-label">Status Arsip</label>

                <div class="tp-radio-wrap">
                  <label class="tp-radio-card {{ $statusArsipVal==='Publik' ? 'active' : '' }}">
                    <input type="radio" name="status_arsip" value="Publik" {{ $statusArsipVal==='Publik' ? 'checked' : '' }}>
                    <span class="tp-radio-dot"></span>
                    <span class="tp-radio-text">Publik</span>
                  </label>

                  <label class="tp-radio-card {{ $statusArsipVal==='Privat' ? 'active' : '' }}">
                    <input type="radio" name="status_arsip" value="Privat" {{ $statusArsipVal==='Privat' ? 'checked' : '' }}>
                    <span class="tp-radio-dot"></span>
                    <span class="tp-radio-text">Privat</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title">
              <span>C. Informasi Anggaran</span>
            </div>
            <div class="tp-divider"></div>

            <div class="tp-grid">
              <div class="tp-field">
                <label class="tp-label">Pagu Anggaran (Rp)</label>
                <input type="text" name="pagu_anggaran" class="tp-input"
                       value="{{ $val('pagu_anggaran',['pagu_anggaran']) }}"
                       placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">HPS (Rp)</label>
                <input type="text" name="hps" class="tp-input"
                       value="{{ $val('hps',['hps']) }}"
                       placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Nilai Kontrak (Rp)</label>
                <input type="text" name="nilai_kontrak" class="tp-input"
                       value="{{ $val('nilai_kontrak',['nilai_kontrak']) }}"
                       placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Nama Rekanan</label>
                <input type="text" name="nama_rekanan" class="tp-input"
                       value="{{ $val('nama_rekanan',['nama_rekanan']) }}"
                       placeholder="Nama Rekanan" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title">
              <span>D. Dokumen Pengadaan</span>
            </div>
            <div class="tp-divider"></div>

            <div class="tp-help" style="margin:0 6px 14px;">
              Upload dokumen pengadaan sesuai dengan tahapan proses.
            </div>

            <div class="tp-acc">
              @foreach($docSessions as $s)
                @php
                  $key = $s['key'];

                  $existingFiles = [];
                  if(isset($dokumenExisting) && is_array($dokumenExisting) && isset($dokumenExisting[$key])){
                    $existingFiles = collect($dokumenExisting[$key])->pluck('path')->all();
                  } else {
                    $existingFiles = $parseFiles($arsip ? $get($arsip, $key) : null);
                  }

                  $existingCount = count($existingFiles);
                @endphp

                <div class="tp-acc-item" data-existing-count="{{ $existingCount }}">
                  <button type="button" class="tp-acc-head" aria-expanded="true">
                    <span class="tp-acc-left">
                      <i class="bi bi-file-earmark-text"></i>
                      {{ $s['label'] }}
                    </span>
                    <span class="tp-acc-right">
                      <i class="bi bi-chevron-down tp-acc-ic"></i>
                    </span>
                  </button>

                  <div class="tp-acc-body">
                    <div class="tp-upload-row" style="margin-bottom:0;">

                      <label class="tp-dropzone">
                        <input
                          type="file"
                          name="{{ $key }}[]"
                          class="tp-file-hidden"
                          multiple
                          data-key="{{ $key }}"
                          data-arsip-id="{{ $arsipId }}"
                        />

                        <div class="tp-drop-ic"><i class="bi bi-upload"></i></div>
                        <div class="tp-drop-title">Upload Dokumen Anda</div>
                        <div class="tp-drop-sub">Klik untuk upload atau drag & drop</div>
                        <div class="tp-drop-meta">Format : PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 10MB)</div>
                        <div class="tp-drop-btn">Pilih File</div>

                        <div class="tp-preview-wrap" {{ $existingCount ? '' : 'hidden' }}>
                          <div class="tp-preview-title">File terpilih</div>

                          <div class="tp-preview-list">
                            @foreach($existingFiles as $path)
                              <div class="tp-preview-item tp-existing" data-existing="1" data-path="{{ $path }}" data-field="{{ $key }}">
                                <div class="tp-preview-left">
                                  <div class="tp-preview-thumb">
                                    <i class="bi bi-file-earmark"></i>
                                  </div>
                                  <div class="tp-preview-info">
                                    <div class="tp-preview-name">{{ $baseName($path) }}</div>
                                    <div class="tp-preview-meta">File tersimpan</div>
                                  </div>
                                </div>
                                <button type="button" class="tp-preview-remove js-remove-existing" aria-label="Hapus file">
                                  <i class="bi bi-x-lg"></i>
                                </button>
                              </div>
                            @endforeach
                          </div>
                        </div>

                      </label>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

          </div>
        </div>
      </section>

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title">
              <span>E. Dokumen Tidak Dipersyaratkan</span>
            </div>
            <div class="tp-divider"></div>

            <div class="tp-help" style="margin:0 6px 14px;">
              Centang dokumen yang <b>tidak dipersyaratkan</b>. List ini otomatis mengambil nama dokumen dari kolom D.
            </div>

            <input type="hidden" name="dokumen_tidak_dipersyaratkan_json" id="tp-nondoc-json" value="[]">
            <input type="hidden" id="tp-nondoc-preselect" value='@json($eSelected)'>

            <div class="tp-nondoc-wrap">
              <div class="tp-nondoc-head">
                <div class="tp-nondoc-title">
                  <i class="bi bi-check2-square"></i>
                  Pilih Dokumen
                </div>
                <div class="tp-nondoc-actions">
                  <button type="button" class="tp-nondoc-btn" id="tp-nondoc-clear">
                    <i class="bi bi-x-circle"></i>
                    Reset
                  </button>
                </div>
              </div>

              <div class="tp-nondoc-box" id="tp-nondoc-list"></div>

              <div class="tp-nondoc-selected" id="tp-nondoc-selected" hidden>
                <div class="tp-nondoc-selected-title">Terpilih</div>
                <div class="tp-nondoc-chips" id="tp-nondoc-chips"></div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <div class="tp-actions tp-actions-split">
        <button type="button" class="tp-btn tp-btn-danger tp-btn-same" id="btnResetChanges">
          <i class="bi bi-x-circle"></i>
          Hapus Perubahan
        </button>

        <button type="submit" class="tp-btn tp-btn-primary tp-btn-same">
          <i class="bi bi-check2-circle"></i>
          Simpan Perubahan
        </button>
      </div>
    </form>
  </main>
</div>

<style>
  .dash-body{ font-size: 18px; line-height: 1.6; font-weight: 700; }
  .dash-app{ font-weight: 700 !important; }

 .dash-header{
  display:flex;
  flex-direction:column;
  align-items:flex-start;
  gap:6px;
}

.dash-header h1{
  margin:0;
  font-weight:700;
  color:#184f61;
}

.dash-header p{
  margin:0;
  color:#64748b;
}


  .tp-section-title,.tp-badge,.tp-label,.tp-input,.tp-select,.tp-actions .tp-btn,.tp-help,
  .tp-radio-card,.tp-radio-text,.tp-acc-head,.tp-upload-label,.tp-drop-title,.tp-drop-sub,
  .tp-drop-meta,.tp-drop-btn,.tp-preview-title,.tp-acc-count{
    font-weight: 700 !important;
  }

.dash-side-actions{
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid rgba(255,255,255,.12);
  display: grid;
  gap: 10px;
}

.dash-sidebar{
  position: fixed;
  left: 0; top: 0;
  height: 100vh;
  width: 220px;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 100;
  display: flex;
  flex-direction: column;
}

.dash-main {
  padding: 28px 28px 40px;
  display: flex;
  flex-direction: column;
}

.dash-header {
  width: 100%;
}

.tp-form {
  width: 100%;
}
  .dash-side-actions{
    margin-top:auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.12);
    display:grid; gap: 10px;
  }

  .tp-header-actions{ display:flex; align-items:center; justify-content:flex-start; margin-bottom: 5px; }
  .tp-btn-fit{ min-width: 0 !important; width: auto !important; height: 46px; padding: 12px 16px; }

  .tp-section-title{
    display:flex; align-items:center; gap:10px; background: transparent; color: var(--navy2);
    padding: 0; border-radius: 0; font-size: 18px; width: 100%; box-sizing: border-box;
  }
  .tp-divider{ height:1px; background: #eef3f6; margin: 12px 0 14px; }

  .tp-label{ display:block; font-size: 15px; color: var(--muted); margin-bottom: 8px; }

  .tp-input,.tp-select,.tp-textarea,.tp-file{
    width:100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 12px;
    font-family: inherit; font-size: 16px; outline: none; background: #fff;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .tp-control{ position:relative; }
  .tp-control .tp-select{ appearance:none; padding-right: 42px; }

  .tp-select{ color: var(--navy2) !important; background-color: #fff !important; }
  .tp-select:focus{ color: var(--navy2) !important; }
  .tp-select:invalid{ color:#94a3b8 !important; }

  .tp-icon{
    position:absolute; right: 14px; top: 50%; transform: translateY(-50%);
    opacity: .55; pointer-events:none; font-size: 18px;
    transition: opacity .18s ease, transform .18s ease, color .18s ease;
    color: var(--navy2);
  }

  .tp-input:hover,.tp-select:hover{
    border-color: rgba(24,79,97,.62);
    box-shadow: 0 8px 14px rgba(2,8,23,.05);
    transform: translateY(-1px);
  }
  .tp-input:focus,.tp-select:focus,.tp-textarea:focus{
    border-color: var(--navy2);
    box-shadow: 0 0 0 4px rgba(24,79,97,.14), 0 10px 18px rgba(2,8,23,.06);
    transform: translateY(-1px);
  }
  .tp-control:focus-within .tp-icon{
    opacity: .95; color: var(--navy2); transform: translateY(-50%) rotate(-180deg);
  }

  .tp-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border-radius: 12px; padding: 12px 16px; font-size: 16px; text-decoration:none;
    border: 1px solid #e2e8f0; cursor:pointer; background:#fff;
    transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
    white-space: nowrap;
  }
  .tp-btn:hover{ transform: translateY(-1px); box-shadow: 0 12px 20px rgba(2,8,23,.08); }
  .tp-btn i{ font-size: 18px; }

  .tp-btn-same{ min-width: 210px; height: 46px; padding: 12px 16px; }
  .tp-btn-ghost{ background:#fff; color: var(--navy2); }
  .tp-btn-primary{ background: var(--yellow); border-color: transparent; color: #0f172a; }

  .tp-btn-danger{ background: #fff; border-color: rgba(239,68,68,.35); color: #ef4444; }
  .tp-btn-danger:hover{ border-color: rgba(239,68,68,.65); box-shadow: 0 12px 20px rgba(239,68,68,.10); }

  .tp-actions{ display:flex; gap: 12px; padding: 10px 6px 2px; margin-top: 6px; }
  .tp-actions-split{ justify-content: space-between; align-items:center; }

  .tp-radio-wrap{ display:grid; gap: 12px; }
  .tp-radio-card{
    display:flex; align-items:center; gap: 12px; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px 14px; background:#fff; cursor:pointer; user-select:none; color: var(--navy2);
    font-size: 16px; transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .tp-radio-card:hover{ border-color: rgba(24,79,97,.55); box-shadow: 0 10px 18px rgba(2,8,23,.07); transform: translateY(-1px); }
  .tp-radio-card input{ display:none; }
  .tp-radio-dot{
    width: 18px; height: 18px; border-radius: 999px; border: 2px solid var(--navy2);
    display:inline-block; position:relative; flex: 0 0 auto;
  }
  .tp-radio-card.active{ background: #dff1ff; border-color: #9fd0ff; box-shadow: 0 0 0 4px rgba(24,79,97,.10); }
  .tp-radio-card.active .tp-radio-dot::after{
    content:""; position:absolute; left:50%; top:50%; width: 8px; height: 8px;
    transform: translate(-50%, -50%); border-radius:999px; background: var(--navy2);
  }

  .tp-acc-item{
    border: 1px solid #e6eef2; border-radius: 14px; background:#fff; box-shadow: 0 10px 18px rgba(2,8,23,.05);
    overflow:hidden; transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
  }
  .tp-acc-item:hover{ transform: translateY(-1px); box-shadow: 0 12px 20px rgba(2,8,23,.07); }

  .tp-acc-count{ font-size: 13px; opacity: .78; white-space: nowrap; margin-right: 10px; color: currentColor; }

  .tp-acc-item.has-file{ border-color: rgba(34,197,94,.65); box-shadow: 0 14px 26px rgba(2,8,23,.08); }
  .tp-acc-item.has-file .tp-acc-head{ background: #22c55e; color: #fff; }
  .tp-acc-item.has-file .tp-acc-left i{ color:#fff; opacity:.95; }
  .tp-acc-item.has-file .tp-acc-ic{ color:#fff; opacity:.95; }

  .tp-acc-head{
    width:100%; display:flex; justify-content:space-between; align-items:center; gap: 12px;
    padding: 12px 14px; border: 0; background: #dff1ff; cursor:pointer; font-family: inherit;
    color: var(--navy2); font-size: 16px; transition: background .18s ease, color .18s ease;
  }
  .tp-acc-left{ display:flex; align-items:center; gap: 10px; min-width: 0; }
  .tp-acc-left i{ font-size: 18px; }
  .tp-acc-right{ display:flex; align-items:center; gap: 10px; flex: 0 0 auto; }
  .tp-acc-ic{ opacity:.9; transition: transform .16s ease; font-size: 18px; }

  .tp-acc-body{ border-top: 1px solid #eef3f6; background:#fff; padding: 14px; }

  .tp-dropzone{
    display:grid; place-items:center; text-align:center; gap: 8px;
    border: 2px dashed #cbd5e1; border-radius: 14px; padding: 22px 16px;
    cursor:pointer; user-select:none; background:#fff;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
  }
  .tp-dropzone:hover{
    border-color: rgba(24,79,97,.70);
    box-shadow: 0 0 0 4px rgba(24,79,97,.12), 0 12px 20px rgba(2,8,23,.06);
    transform: translateY(-1px);
  }
  .tp-acc-item.has-file .tp-dropzone{
    border-style: solid; border-color: rgba(34,197,94,.90); background: rgba(34,197,94,.05);
    box-shadow: 0 0 0 4px rgba(34,197,94,.09), 0 12px 20px rgba(2,8,23,.05);
    transform: translateY(-1px);
  }
  .tp-file-hidden{ display:none; }

  .tp-drop-ic{
    width: 48px; height: 48px; border-radius: 16px;
    display:grid; place-items:center; background:#f8fbfd; border:1px solid #eef3f6;
  }
  .tp-drop-ic i{ font-size: 20px; }
  .tp-drop-title{ font-size:16px; color:#0f172a; }
  .tp-drop-sub{ font-size:14px; color:#475569; }
  .tp-drop-meta{ font-size:12px; color:#94a3b8; }
  .tp-drop-btn{
    margin-top: 4px;
    height: 40px; padding: 0 14px; border-radius: 12px;
    border: 1px solid #e2e8f0; background:#fff; display:inline-flex; align-items:center; justify-content:center;
  }

  .tp-preview-wrap{ width:100%; margin-top: 14px; text-align:left; }
  .tp-preview-title{ font-size:14px; color:#0f172a; margin-bottom:10px; }
  .tp-preview-list{ display:grid; gap:10px; }
  .tp-preview-item{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    border:1px solid #e8eef3; background:#fff; border-radius:14px; padding:10px 12px;
  }
  .tp-preview-left{ display:flex; align-items:center; gap:10px; min-width:0; }
  .tp-preview-thumb{
    width:38px; height:38px; border-radius:12px; display:grid; place-items:center;
    background:#f8fbfd; border:1px solid #eef3f6; flex:0 0 auto;
  }
  .tp-preview-info{ min-width:0; }
  .tp-preview-name{ font-size:14px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .tp-preview-meta{ font-size:12px; color:#64748b; }
  .tp-preview-remove{
    width:34px; height:34px; border-radius:12px; border:1px solid #e8eef3;
    background:#fff; display:grid; place-items:center; cursor:pointer;
  }

  .tp-nondoc-wrap{
    border: 1px solid #e6eef2;
    border-radius: 14px;
    background:#fff;
    overflow:hidden;
  }
  .tp-nondoc-head{
    display:flex; justify-content:space-between; align-items:center; gap:12px;
    padding:12px 14px; background:#f8fbfd; border-bottom:1px solid #eef3f6;
  }
  .tp-nondoc-title{ display:flex; align-items:center; gap:8px; font-size:15px; color:#0f172a; }
  .tp-nondoc-btn{
    height:36px; padding:0 12px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; cursor:pointer;
    display:inline-flex; align-items:center; gap:8px;
  }
  .tp-nondoc-box{ padding:14px; display:grid; gap:10px; }
  .tp-nondoc-item{
    display:flex; align-items:center; gap:10px; border:1px solid #e8eef3; border-radius:12px; padding:10px 12px;
  }
  .tp-nondoc-selected{ padding:0 14px 14px; }
  .tp-nondoc-selected-title{ font-size:14px; color:#0f172a; margin-bottom:10px; }
  .tp-nondoc-chips{ display:flex; gap:8px; flex-wrap:wrap; }
  .tp-chip{
    display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border-radius:999px;
    border:1px solid #d7e9ee; background:#e9f3f6; font-size:13px; color:#184f61;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tp-radio-card').forEach(card => {
    const input = card.querySelector('input[type="radio"]');
    if (!input) return;
    input.addEventListener('change', function () {
      document.querySelectorAll(`input[name="${input.name}"]`).forEach(r => {
        r.closest('.tp-radio-card')?.classList.remove('active');
      });
      card.classList.add('active');
    });
  });

  document.querySelectorAll('.tp-acc-item').forEach(item => {
    const head = item.querySelector('.tp-acc-head');
    const body = item.querySelector('.tp-acc-body');
    const icon = item.querySelector('.tp-acc-ic');
    if (!head || !body || !icon) return;

    head.addEventListener('click', function () {
      const isHidden = body.style.display === 'none';
      body.style.display = isHidden ? '' : 'none';
      icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(-180deg)';
    });
  });

  document.querySelectorAll('.tp-file-hidden').forEach(input => {
    input.addEventListener('change', function () {
      const item = input.closest('.tp-acc-item');
      const wrap = input.closest('.tp-dropzone')?.querySelector('.tp-preview-wrap');
      const list = input.closest('.tp-dropzone')?.querySelector('.tp-preview-list');
      if (!wrap || !list) return;

      const existingCount = list.querySelectorAll('.tp-existing').length;
      const files = Array.from(input.files || []);

      list.querySelectorAll('.tp-new').forEach(el => el.remove());

      files.forEach(file => {
        const row = document.createElement('div');
        row.className = 'tp-preview-item tp-new';
        row.innerHTML = `
          <div class="tp-preview-left">
            <div class="tp-preview-thumb"><i class="bi bi-file-earmark"></i></div>
            <div class="tp-preview-info">
              <div class="tp-preview-name">${file.name}</div>
              <div class="tp-preview-meta">File baru dipilih</div>
            </div>
          </div>
          <button type="button" class="tp-preview-remove"><i class="bi bi-x-lg"></i></button>
        `;
        row.querySelector('.tp-preview-remove').addEventListener('click', function () {
          row.remove();
          if (list.children.length === 0) wrap.hidden = true;
          if ((existingCount + list.querySelectorAll('.tp-new').length) === 0) {
            item?.classList.remove('has-file');
          }
        });
        list.appendChild(row);
      });

      wrap.hidden = (existingCount + files.length) === 0;
      if ((existingCount + files.length) > 0) {
        item?.classList.add('has-file');
      }
    });
  });

  const preselect = (() => {
    try { return JSON.parse(document.getElementById('tp-nondoc-preselect')?.value || '[]'); }
    catch(e){ return []; }
  })();

  const docNames = Array.from(document.querySelectorAll('.tp-acc-head .tp-acc-left')).map(el => {
    return el.textContent.trim();
  });

  const listBox = document.getElementById('tp-nondoc-list');
  const chipsWrap = document.getElementById('tp-nondoc-chips');
  const selectedWrap = document.getElementById('tp-nondoc-selected');
  const jsonInput = document.getElementById('tp-nondoc-json');
  const clearBtn = document.getElementById('tp-nondoc-clear');

  const selected = new Set(preselect);

  function renderNonDoc(){
    if (!listBox || !chipsWrap || !jsonInput || !selectedWrap) return;

    listBox.innerHTML = '';
    chipsWrap.innerHTML = '';

    docNames.forEach(name => {
      const row = document.createElement('label');
      row.className = 'tp-nondoc-item';
      row.innerHTML = `
        <input type="checkbox" value="${name}" ${selected.has(name) ? 'checked' : ''}>
        <span>${name}</span>
      `;
      const cb = row.querySelector('input');
      cb.addEventListener('change', function () {
        if (cb.checked) selected.add(name);
        else selected.delete(name);
        renderNonDoc();
      });
      listBox.appendChild(row);
    });

    const arr = Array.from(selected);
    jsonInput.value = JSON.stringify(arr);
    selectedWrap.hidden = arr.length === 0;

    arr.forEach(name => {
      const chip = document.createElement('div');
      chip.className = 'tp-chip';
      chip.textContent = name;
      chipsWrap.appendChild(chip);
    });
  }

  clearBtn?.addEventListener('click', function(){
    selected.clear();
    renderNonDoc();
  });

  renderNonDoc();
});
</script>

</body>
</html>