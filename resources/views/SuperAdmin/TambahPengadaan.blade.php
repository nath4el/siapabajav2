<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Pengadaan - SIAPABAJA</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
</head>

<body class="dash-body">
@php
  $superAdminName = $superAdminName ?? (auth()->user()->name ?? "Super Admin");

  $tahunOptions = $tahunOptions ?? [date('Y'), date('Y')-1, date('Y')-2, date('Y')-3, date('Y')-4];
  $unitOptions = $unitOptions ?? [];
  $jenisPengadaanOptions = $jenisPengadaanOptions ?? [
    "Pengadaan Barang",
    "Pengadaaan Pekerjaan Konstruksi",
    "Pengadaan Jasa Konsultasi",
    "Pengadaan Jasa Konsultasi",
    "Pengadaan Jasa Lainnya",
  ];

$metodePengadaanOptions = [
  'Pengadaan Langsung',
  'Penunjukan Langsung',
  'E-Purchasing / E-Catalogue',
  'Tender Terbatas',
  'Tender Terbuka',
  'Swakelola',
];
  $statusPekerjaanOptions = $statusPekerjaanOptions ?? ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];

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
    <!-- Dashboard -->
    <a class="dash-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
       href="{{ route('superadmin.dashboard') }}">
      <span class="ic"><i class="bi bi-grid-fill"></i></span>
      Dashboard
    </a>

    <!-- Arsip -->
    <a class="dash-link {{ request()->routeIs('superadmin.arsip*') ? 'active' : '' }}"
       href="{{ route('superadmin.arsip') }}">
      <span class="ic"><i class="bi bi-archive"></i></span>
      Arsip PBJ
    </a>

    <!-- Tambah Pengadaan -->
    <a class="dash-link {{ request()->routeIs('superadmin.pengadaan.create') ? 'active' : '' }}"
       href="{{ route('superadmin.pengadaan.create') }}">
      <span class="ic"><i class="bi bi-plus-square"></i></span>
      Tambah Pengadaan
    </a>

    <!-- Kelola Menu -->
    <a class="dash-link {{ request()->routeIs('superadmin.kelola.menu') ? 'active' : '' }}"
       href="{{ route('superadmin.kelola.menu') }}">
      <span class="ic"><i class="bi bi-gear-fill"></i></span>
      Kelola Menu
    </a>

    <!-- Kelola Akun -->
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

  <main class="dash-main">
    <header class="dash-header">
  <div class="dash-header-left">
    <h1>Tambah Arsip Pengadaan Barang dan Jasa</h1>
    <p>Tambahkan arsip PBJ baru</p>
  </div>
</header>

    <form action="{{ route('superadmin.pengadaan.store') }}" method="POST" class="tp-form" enctype="multipart/form-data">
      @csrf

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title"><span>A. Informasi Umum</span></div>
            <div class="tp-divider"></div>

            <div class="tp-grid">
              <div class="tp-field">
                <label class="tp-label">Tahun</label>
                <div class="tp-control">
                  <select name="tahun" class="tp-select" required>
                    <option value="" selected disabled hidden>Tahun</option>
                    @foreach($tahunOptions as $t)
                      <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

              <div class="tp-field">
                <label class="tp-label">Unit Kerja</label>
                <div class="tp-control">
                  <select name="unit_kerja" class="tp-select" required>
                    <option value="" selected disabled hidden>Pilih Unit</option>
                    @foreach($unitOptions as $uname)
                      <option value="{{ $uname }}">{{ $uname }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

              <div class="tp-field tp-full">
                <label class="tp-label">Nama Pekerjaan</label>
                <input type="text" name="nama_pekerjaan" class="tp-input" placeholder="Nama Pekerjaan" />
              </div>

              <div class="tp-field">
                <label class="tp-label">ID RUP</label>
                <input type="text" name="id_rup" class="tp-input" placeholder="RUP-xxxx-xxxx-xxx-xx" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Jenis Pengadaan</label>
                <div class="tp-control">
                  <select name="jenis_pengadaan" class="tp-select" required>
                    <option value="" selected disabled hidden>Pilih Jenis Pengadaan</option>
                    @foreach($jenisPengadaanOptions as $jp)
                      <option value="{{ $jp }}">{{ $jp }}</option>
                    @endforeach
                  </select>
                  <i class="bi bi-chevron-down tp-icon"></i>
                </div>
              </div>

              <div class="tp-field">
  <label class="tp-label">Metode Pengadaan</label>
  <div class="tp-control">
    <select name="metode_pengadaan" class="tp-select" required>
      <option value="" selected disabled hidden>Pilih Metode Pengadaan</option>
      @foreach($metodePengadaanOptions as $mp)
        <option value="{{ $mp }}">{{ $mp }}</option>
      @endforeach
    </select>
    <i class="bi bi-chevron-down tp-icon"></i>
  </div>
</div>

              <div class="tp-field">
                <label class="tp-label">Status Pekerjaan</label>
                <div class="tp-control">
                  <select name="status_pekerjaan" class="tp-select" required>
                    <option value="" selected disabled hidden>Pilih Status Pekerjaan</option>
                    @foreach($statusPekerjaanOptions as $sp)
                      <option value="{{ $sp }}">{{ $sp }}</option>
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
            <div class="tp-section-title"><span>B. Status Akses Arsip</span></div>
            <div class="tp-divider"></div>

            <div class="tp-grid" style="grid-template-columns:1fr;">
              <div class="tp-field">
                <label class="tp-label">Status Arsip</label>

                <div class="tp-radio-wrap">
                  <label class="tp-radio-card active">
                    <input type="radio" name="status_arsip" value="Publik" checked>
                    <span class="tp-radio-dot"></span>
                    <span class="tp-radio-text">Publik</span>
                  </label>

                  <label class="tp-radio-card">
                    <input type="radio" name="status_arsip" value="Privat">
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
            <div class="tp-section-title"><span>C. Informasi Anggaran</span></div>
            <div class="tp-divider"></div>

            <div class="tp-grid">
              <div class="tp-field">
                <label class="tp-label">Pagu Anggaran (Rp)</label>
                <input type="text" name="pagu_anggaran" class="tp-input" placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">HPS (Rp)</label>
                <input type="text" name="hps" class="tp-input" placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Nilai Kontrak (Rp)</label>
                <input type="text" name="nilai_kontrak" class="tp-input" placeholder="Rp" />
              </div>

              <div class="tp-field">
                <label class="tp-label">Nama Rekanan</label>
                <input type="text" name="nama_rekanan" class="tp-input" placeholder="Nama Rekanan" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dash-table tp-cardbox" style="border-radius:14px; overflow:visible; margin-bottom:14px;">
        <div style="padding:18px 18px 16px;">
          <div class="tp-section">
            <div class="tp-section-title"><span>D. Dokumen Pengadaan</span></div>
            <div class="tp-divider"></div>

            <div class="tp-help" style="margin:0 6px 14px;">
              Upload dokumen pengadaan sesuai dengan tahapan proses.
            </div>

            <div class="tp-acc">
              @foreach($docSessions as $s)
                <div class="tp-acc-item" data-existing-count="0">
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
                    <label class="tp-dropzone">
                      <input type="file" name="{{ $s['key'] }}[]" class="tp-file-hidden" multiple />
                      <div class="tp-drop-ic"><i class="bi bi-upload"></i></div>
                      <div class="tp-drop-title">Upload Dokumen Anda</div>
                      <div class="tp-drop-sub">Klik untuk upload atau drag & drop</div>
                      <div class="tp-drop-meta">Format : PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 10MB)</div>
                      <div class="tp-drop-btn">Pilih File</div>

                      <div class="tp-preview-wrap" hidden>
                        <div class="tp-preview-title">File terpilih</div>
                        <div class="tp-preview-list"></div>
                      </div>
                    </label>
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
            <div class="tp-section-title"><span>E. Dokumen Tidak Dipersyaratkan</span></div>
            <div class="tp-divider"></div>

            <div class="tp-help" style="margin:0 6px 14px;">
              Centang dokumen yang <b>tidak dipersyaratkan</b>. List ini otomatis mengambil nama dokumen dari kolom D.
            </div>

            <input type="hidden" name="dokumen_tidak_dipersyaratkan_json" id="tp-nondoc-json" value="[]">

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
        <a href="{{ route('superadmin.arsip') }}" class="tp-btn tp-btn-danger tp-btn-same">
          <i class="bi bi-x-circle"></i>
          Batal
        </a>

        <button type="submit" class="tp-btn tp-btn-primary tp-btn-same">
          <i class="bi bi-check2-circle"></i>
          Simpan Arsip
        </button>
      </div>
    </form>
  </main>
</div>

<style>
  .dash-body{ font-size: 16px; line-height: 1.6; font-weight: 400; }
  .dash-app{ font-weight: 700 !important; }

  /* ================= HEADER (SAMA DENGAN DASHBOARD) ================= */
.dash-header {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 18px; /* ⬅️ penting biar gak nempel ke card */
}

/* WRAPPER KIRI */
.dash-header-left {
    display: flex;
    flex-direction: column;
}

/* JUDUL */
.dash-header h1 {
    margin: 0;
    font-size: 26px;
    font-weight: var(--fw-semi);
    color: var(--navy2);
    line-height: 1.2;
}

/* DESKRIPSI */
.dash-header p {
    margin: 4px 0 0;
    font-size: 15px;
    color: var(--muted);
    font-weight: var(--fw-normal);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .dash-header {
        gap: 6px;
    }
}

.dash-link {
  font-weight: 600;
}

.dash-side-btn {
  font-weight: 600;
}

.tp-label {
  font-weight: 500;
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
  }
  .tp-control{ position:relative; }
  .tp-control .tp-select{ appearance:none; padding-right: 42px; }
  .tp-icon{
    position:absolute; right: 14px; top: 50%; transform: translateY(-50%);
    opacity: .55; pointer-events:none; font-size: 18px; color: var(--navy2);
  }

  .tp-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border-radius: 12px; padding: 12px 16px; font-size: 16px; text-decoration:none;
    border: 1px solid #e2e8f0; cursor:pointer; background:#fff; white-space: nowrap;
  }
  .tp-btn i{ font-size: 18px; }
  .tp-btn-same{ min-width: 210px; height: 46px; padding: 12px 16px; }
  .tp-btn-ghost{ background:#fff; color: var(--navy2); }
  .tp-btn-primary{ background: var(--yellow); border-color: transparent; color: #0f172a; }
  .tp-btn-danger{ background: #fff; border-color: rgba(239,68,68,.35); color: #ef4444; }

  .tp-actions{ display:flex; gap: 12px; padding: 10px 6px 2px; margin-top: 6px; }
  .tp-actions-split{ justify-content: space-between; align-items:center; }

  .tp-radio-wrap{ display:grid; gap: 12px; }
  .tp-radio-card{
    display:flex; align-items:center; gap: 12px; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px 14px; background:#fff; cursor:pointer; user-select:none; color: var(--navy2); font-size: 16px;
  }
  .tp-radio-card input{ display:none; }
  .tp-radio-dot{
    width: 18px; height: 18px; border-radius: 999px; border: 2px solid var(--navy2);
    display:inline-block; position:relative; flex: 0 0 auto;
  }
  .tp-radio-card.active{
    background: #dff1ff; border-color: #9fd0ff;
  }
  .tp-radio-card.active .tp-radio-dot::after{
    content:""; position:absolute; left:50%; top:50%; width: 8px; height: 8px;
    transform: translate(-50%, -50%); border-radius:999px; background: var(--navy2);
  }

  .tp-acc-item{
    border: 1px solid #e6eef2; border-radius: 14px; background:#fff; overflow:hidden; margin-bottom:10px;
  }
  .tp-acc-head{
    width:100%; display:flex; justify-content:space-between; align-items:center; gap: 12px;
    padding: 12px 14px; border: 0; background: #dff1ff; cursor:pointer; font-family: inherit;
    color: var(--navy2); font-size: 16px;
  }
  .tp-acc-left{ display:flex; align-items:center; gap: 10px; min-width: 0; }
  .tp-acc-right{ display:flex; align-items:center; gap: 10px; flex: 0 0 auto; }
  .tp-acc-body{ border-top: 1px solid #eef3f6; background:#fff; padding: 14px; }

  .tp-dropzone{
    display:grid; place-items:center; text-align:center; gap: 8px;
    border: 2px dashed #cbd5e1; border-radius: 14px; padding: 22px 16px;
    cursor:pointer; user-select:none; background:#fff;
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
    if (!head || !body) return;

    head.addEventListener('click', function () {
      const isHidden = body.style.display === 'none';
      body.style.display = isHidden ? '' : 'none';
    });
  });

  document.querySelectorAll('.tp-file-hidden').forEach(input => {
    input.addEventListener('change', function () {
      const wrap = input.closest('.tp-dropzone')?.querySelector('.tp-preview-wrap');
      const list = input.closest('.tp-dropzone')?.querySelector('.tp-preview-list');
      if (!wrap || !list) return;

      list.innerHTML = '';

      Array.from(input.files || []).forEach(file => {
        const row = document.createElement('div');
        row.className = 'tp-preview-item';
        row.innerHTML = `<div>${file.name}</div>`;
        list.appendChild(row);
      });

      wrap.hidden = (input.files || []).length === 0;
    });
  });

  const listBox = document.getElementById('tp-nondoc-list');
  const chipsWrap = document.getElementById('tp-nondoc-chips');
  const selectedWrap = document.getElementById('tp-nondoc-selected');
  const jsonInput = document.getElementById('tp-nondoc-json');
  const clearBtn = document.getElementById('tp-nondoc-clear');

  const docNames = Array.from(document.querySelectorAll('.tp-acc-head .tp-acc-left')).map(el => el.textContent.trim());
  const selected = new Set();

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