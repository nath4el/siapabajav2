<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Akun - SIAPABAJA</title>
  <link rel="stylesheet" href="{{ asset('css/Unit.css') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="dash-body page-akun">
@php
  $user = auth()->user();
  $name     = $superAdminName ?? ($user->name ?? 'Super Admin');
  $email    = $superAdminEmail ?? ($user->email ?? 'superadmin@gmail.com');
  $roleText = $roleText ?? 'SUPER ADMIN';
  $initials = strtoupper(mb_substr(trim($name), 0, 1));

  $kelolaAkunActive = request()->routeIs('superadmin.kelola.akun')
    || request()->routeIs('superadmin.kelola.akun.ppk')
    || request()->routeIs('superadmin.kelola.akun.unit');
@endphp

<div class="dash-wrap">

  {{-- ======= SIDEBAR ======= --}}
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

      {{-- Kelola Akun: accordion --}}
      <button class="dash-link dash-link-accordion {{ $kelolaAkunActive ? 'active' : '' }}"
              id="kelolaAkunParent" type="button">
        <span class="ic"><i class="bi bi-person-gear"></i></span>
        Kelola Akun
        <i class="bi bi-chevron-down dash-chevron"></i>
      </button>

      <div class="dash-sub {{ $kelolaAkunActive ? 'is-open' : '' }}" id="kelolaAkunSub">
        <a class="dash-sub-link {{ request()->routeIs('superadmin.kelola.akun') && !request()->routeIs('superadmin.kelola.akun.ppk') && !request()->routeIs('superadmin.kelola.akun.unit') ? 'active' : '' }}"
           href="{{ route('superadmin.kelola.akun') }}">
          <span class="ic"><i class="bi bi-person-circle"></i></span>
          Kelola Akun Saya
        </a>
        <a class="dash-sub-link {{ request()->routeIs('superadmin.kelola.akun.ppk') ? 'active' : '' }}"
           href="{{ route('superadmin.kelola.akun.ppk') }}">
          <span class="ic"><i class="bi bi-person-badge"></i></span>
          Kelola Akun PPK
        </a>
        <a class="dash-sub-link {{ request()->routeIs('superadmin.kelola.akun.unit') ? 'active' : '' }}"
           href="{{ route('superadmin.kelola.akun.unit') }}">
          <span class="ic"><i class="bi bi-people"></i></span>
          Kelola Akun Unit
        </a>
      </div>
    </nav>

    <div class="dash-side-actions">
      <a class="dash-side-btn" href="{{ route('home') }}">
        <i class="bi bi-house-door"></i> Kembali
      </a>
      <a class="dash-side-btn" href="{{ url('/logout') }}">
        <i class="bi bi-box-arrow-right"></i> Keluar
      </a>
    </div>
  </aside>

  {{-- ======= MAIN ======= --}}
  <main class="dash-main">
    <header class="dash-header">
      <h1>Kelola Akun</h1>
      <p>Perbarui informasi akun Super Admin kamu (nama, email, dan password) dengan aman.</p>
    </header>

    @if (session('success'))
      <div class="a-alert a-alert--ok">
        <i class="bi bi-check-circle"></i>
        <div>{{ session('success') }}</div>
      </div>
    @endif

    @if ($errors->any())
      <div class="a-alert a-alert--err">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
          Ada input yang perlu diperbaiki:
          <ul class="a-errlist">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    <section class="a-grid">
      {{-- Card kiri: info akun --}}
      <div class="a-card">
        <div class="a-card-head">
          <div class="a-head-left">
            <div class="a-ico"><i class="bi bi-person-badge"></i></div>
            <div class="a-head-text">
              <div class="t1">Informasi Akun</div>
              <div class="t2">Ringkasan identitas & status login</div>
            </div>
          </div>
        </div>
        <div class="a-card-body">
          <div class="a-profile">
            <div class="a-avatar">{{ $initials }}</div>
            <div class="a-meta">
              <div class="a-name">{{ $name }}</div>
              <div class="a-pills">
                <span class="a-pill"><i class="bi bi-envelope"></i> {{ $email }}</span>
                <span class="a-pill"><i class="bi bi-shield-lock"></i> {{ $roleText }}</span>
              </div>
            </div>
          </div>
          <div class="a-tips">
            <div class="a-tip-title"><i class="bi bi-info-circle"></i> Tips keamanan</div>
            <ul>
              <li>Gunakan password minimal 8 karakter (lebih aman 12+).</li>
              <li>Hindari password yang sama dengan akun lain.</li>
              <li>Jika pernah login di perangkat umum, disarankan ganti password.</li>
            </ul>
          </div>
        </div>
      </div>

      {{-- Card kanan: form pengaturan --}}
      <div class="a-card">
        <div class="a-card-head">
          <div class="a-head-left">
            <div class="a-ico"><i class="bi bi-sliders"></i></div>
            <div class="a-head-text">
              <div class="t1">Pengaturan</div>
              <div class="t2">Ubah nama, email, dan password</div>
            </div>
          </div>
        </div>
        <div class="a-card-body">
          <form class="a-form" action="{{ route('superadmin.akun.update') }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="a-row">
              <div class="a-field">
                <label class="a-label"><i class="bi bi-person"></i> Nama</label>
                <input type="text" name="name" value="{{ old('name', $name) }}" placeholder="Masukkan nama" required>
                <div class="a-hint">Nama yang tampil di sistem.</div>
              </div>
              <div class="a-field">
                <label class="a-label"><i class="bi bi-envelope"></i> Email / Akun</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Masukkan email" required>
                <div class="a-hint">Email ini dipakai untuk login.</div>
              </div>
            </div>

            <div class="a-sep"></div>

            <div class="a-field">
              <label class="a-label"><i class="bi bi-key"></i> Password Saat Ini</label>
              <div class="a-pass">
                <input id="curPw" type="password" name="current_password" placeholder="Wajib jika ingin mengganti password">
                <button class="a-eye" type="button" data-eye="curPw" aria-label="Tampilkan password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="a-hint">Kosongkan jika tidak mengganti password.</div>
            </div>

            <div class="a-row">
              <div class="a-field">
                <label class="a-label"><i class="bi bi-lock"></i> Password Baru</label>
                <div class="a-pass">
                  <input id="newPw" type="password" name="password" placeholder="Password baru">
                  <button class="a-eye" type="button" data-eye="newPw" aria-label="Tampilkan password">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="a-hint">Minimal 8 karakter.</div>
              </div>
              <div class="a-field">
                <label class="a-label"><i class="bi bi-lock-fill"></i> Konfirmasi Password</label>
                <div class="a-pass">
                  <input id="cnfPw" type="password" name="password_confirmation" placeholder="Ulangi password baru">
                  <button class="a-eye" type="button" data-eye="cnfPw" aria-label="Tampilkan password">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="a-hint">Harus sama dengan password baru.</div>
              </div>
            </div>

            <div class="a-actions">
              <button type="submit" class="a-btn a-btn--primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>
</div>

<style>
/* ── Accordion sidebar ── */
.dash-link-accordion {
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  cursor: pointer;
  color: rgba(255,255,255,.92);
  font-family: 'Nunito', sans-serif;
  font-size: 16px;
  font-weight: 400;
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 12px 12px;
  border-radius: 10px;
  transition: background .15s;
}
.dash-link-accordion:hover { background: rgba(255,255,255,.08); }
.dash-link-accordion.active { background: #f6c100; color: #0f172a; }

.dash-chevron {
  margin-left: auto;
  font-size: 12px;
  transition: transform .2s ease;
}
.dash-link-accordion.is-open .dash-chevron { transform: rotate(180deg); }

.dash-sub {
  display: none;
  flex-direction: column;
  gap: 4px;
  padding-left: 14px;
  margin-top: 2px;
}
.dash-sub.is-open { display: flex; }

.dash-sub-link {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255,255,255,.80);
  text-decoration: none;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 400;
  transition: background .15s, color .15s;
}
.dash-sub-link .ic { width: 16px; text-align: center; }
.dash-sub-link:hover { background: rgba(255,255,255,.08); color: #fff; }
.dash-sub-link.active { background: #f6c100; color: #0f172a; }

/* ── Header ── */
.dash-header { display: flex; flex-direction: column; gap: 4px; margin-bottom: 20px; }
.dash-header h1 { margin: 0; font-size: 26px; font-weight: 600; color: #184f61; }
.dash-header p  { margin: 0; font-size: 15px; color: #64748b; font-weight: 400; }

/* ── Alerts ── */
.a-alert { margin-bottom: 16px; border-radius: 14px; border: 1px solid #e6eef2; background: #fff; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start; }
.a-alert--ok  { border-left: 4px solid #16a34a; }
.a-alert--err { border-left: 4px solid #dc2626; }
.a-errlist { margin: 6px 0 0; padding-left: 18px; }

/* ── Grid ── */
.a-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 18px; }

/* ── Card ── */
.a-card { background: #fff; border: 1px solid #e6eef2; border-radius: 22px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,.05); min-height: 580px; }
.a-card-head { background: #1f5872; color: #fff; padding: 16px 18px; }
.a-head-left { display: flex; align-items: center; gap: 14px; }
.a-ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.18); font-size: 22px; }
.a-head-text .t1 { font-size: 18px; font-weight: 700; }
.a-head-text .t2 { font-size: 14px; opacity: .9; margin-top: 3px; }
.a-card-body { padding: 18px; }

/* ── Profile ── */
.a-profile { display: flex; align-items: center; gap: 14px; }
.a-avatar { width: 72px; height: 72px; border-radius: 20px; display: grid; place-items: center; background: #d9edf3; color: #184d66; font-size: 34px; }
.a-name { font-size: 20px; font-weight: 700; color: #0f172a; }
.a-pills { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
.a-pill { display: inline-flex; align-items: center; gap: 8px; padding: 12px 14px; border-radius: 999px; background: #f8fafc; border: 1px solid #e5e7eb; color: #475569; }

/* ── Tips ── */
.a-tips { margin-top: 18px; border: 1px dashed #cfe2ea; border-radius: 18px; padding: 14px 16px; background: #fbfeff; }
.a-tip-title { font-size: 15px; font-weight: 700; color: #184d66; margin-bottom: 10px; }
.a-tips ul { margin: 0; padding-left: 18px; line-height: 1.8; }

/* ── Form ── */
.a-form { display: flex; flex-direction: column; gap: 16px; }
.a-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.a-field { display: flex; flex-direction: column; gap: 8px; }
.a-label { font-size: 15px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px; }

.a-field input[type="text"],
.a-field input[type="email"] {
  height: 58px; border-radius: 16px; border: 1px solid #d7dee7;
  padding: 0 16px; font-size: 16px; font-family: inherit; outline: none;
}

.a-pass { height: 58px; border: 1px solid #d7dee7; border-radius: 16px; display: flex; align-items: center; overflow: hidden; }
.a-pass input { flex: 1; height: 100%; border: 0; outline: none; padding: 0 16px; font-size: 16px; font-family: inherit; }
.a-eye { width: 58px; height: 100%; border: 0; border-left: 1px solid #d7dee7; background: #f8fafc; cursor: pointer; font-size: 20px; }

.a-hint { font-size: 14px; color: #64748b; }
.a-sep  { height: 1px; background: #e6eef2; margin: 4px 0; }

.a-actions { display: flex; justify-content: flex-end; margin-top: 6px; }
.a-btn { border: 0; height: 52px; padding: 0 24px; border-radius: 16px; font-size: 16px; font-family: inherit; cursor: pointer; }
.a-btn--primary { background: #f6c100; color: #111827; font-weight: 700; }

.dash-sidebar {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.dash-nav {
  flex: 1;
  overflow-y: auto;
}

.dash-side-actions {
  margin-top: auto;
}

.dash-nav::-webkit-scrollbar {
  width: 6px;
}

.dash-nav::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.3);
  border-radius: 10px;
}

input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear { display: none !important; }

/* ── Responsive ── */
@media (max-width: 1100px) { .a-grid { grid-template-columns: 1fr; } }
@media (max-width: 860px)  { .a-row { grid-template-columns: 1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  /* Toggle show/hide password */
  document.querySelectorAll('[data-eye]').forEach(btn => {
    btn.addEventListener('click', function(){
      const input = document.getElementById(btn.dataset.eye);
      if(!input) return;
      const isPw = input.type === 'password';
      input.type = isPw ? 'text' : 'password';
      const ico = btn.querySelector('i');
      if(ico) ico.className = isPw ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  /* Accordion Kelola Akun */
  const parent = document.getElementById('kelolaAkunParent');
  const sub    = document.getElementById('kelolaAkunSub');
  if(parent && sub){
    parent.addEventListener('click', function(){
  sub.classList.toggle('is-open');
  parent.classList.toggle('is-open');
});
  }
});
</script>
</body>
</html>