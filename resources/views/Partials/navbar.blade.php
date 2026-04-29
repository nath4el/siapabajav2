<header class="nav">
  <div class="container nav-inner">

    {{-- BRAND --}}
    <a href="{{ route('landing') }}" class="brand">
      <img class="brand-logo"
           src="{{ asset('image/Logo_Unsoed.png') }}"
           alt="Logo Universitas Jenderal Soedirman">
      <span class="brand-name">SIAPABAJA</span>
    </a>

    {{-- NAVIGATION --}}
    <nav class="nav-links">

      {{-- REGULASI --}}
      <a
        href="{{ request()->routeIs('landing') ? '#regulasi' : route('landing').'#regulasi' }}"
        class="nav-link"
      >
        Regulasi
      </a>

      {{-- ARSIP PBJ --}}
      <a
        href="{{ route('ArsipPBJ') }}"
        class="nav-link {{ request()->routeIs('ArsipPBJ') ? 'active' : '' }}"
      >
        Arsip PBJ
      </a>

      {{-- KONTAK --}}
      <a
        href="{{ request()->routeIs('landing') ? '#kontak' : route('landing').'#kontak' }}"
        class="nav-link"
      >
        Kontak
      </a>

      {{-- ✅ SELALU TAMPIL LOGIN (FIX FINAL) --}}
      <a class="btn btn-white" href="{{ route('login') }}">
        Masuk
      </a>

    </nav>

  </div>
</header>