<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="{{ asset('css/landing.css') }}">

  @stack('head')
</head>

<body class="has-nav">

  @include('Partials.navbar')

  <main>
    @yield('content')
  </main>

  @include('Partials.footer')

  {{-- ✅ CHATBOT --}}
  @include('Partials.chatbot')

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  {{-- ✅ SCROLLSPY (FINAL: JALAN SELAMA SECTION ADA, KONTAK SELALU WORK) --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {

    const allLinks = Array.from(document.querySelectorAll('.nav-links a.nav-link'));
    if (!allLinks.length) return;

    const hashLinks = allLinks.filter(a => (a.getAttribute('href') || '').includes('#'));
    if (!hashLinks.length) return;

    const sectionIds = ['regulasi','statistika','kontak'];

    const elements = sectionIds
      .map(id => document.getElementById(id))
      .filter(Boolean);

    if (elements.length < 2) return;

    const getHashFromHref = (a) => {
      const href = a.getAttribute('href') || '';
      try {
        return new URL(href, window.location.origin).hash;
      } catch (e) {
        const idx = href.indexOf('#');
        return idx >= 0 ? href.slice(idx) : '';
      }
    };

    const setActiveById = (id) => {
      const wanted = '#' + id;

      allLinks.forEach(a => a.classList.remove('active'));

      const match = allLinks.find(a => {
        const href = a.getAttribute('href') || '';
        const h = getHashFromHref(a);
        return h === wanted || href.endsWith(wanted) || href.includes(wanted);
      });

      if (match) match.classList.add('active');
    };

    const setActiveByHash = () => {
      const hash = window.location.hash;
      if (!hash) return;

      const id = hash.replace('#','');
      if (sectionIds.includes(id)) setActiveById(id);
    };

    hashLinks.forEach(a => {
      a.addEventListener('click', () => {
        const hash = getHashFromHref(a);
        const id = hash.replace('#','');
        if (!sectionIds.includes(id)) return;
        setActiveById(id);
        setTimeout(() => onScrollBottomCheck(), 80);
      });
    });

    const onScrollBottomCheck = () => {
      const nearBottom =
        window.innerHeight + window.scrollY >= document.body.scrollHeight - 8;

      if (nearBottom) setActiveById('kontak');
    };

    window.addEventListener('scroll', onScrollBottomCheck, { passive: true });

    if (!('IntersectionObserver' in window)) {
      window.addEventListener('hashchange', () => {
        setActiveByHash();
        onScrollBottomCheck();
      });

      setActiveByHash();
      onScrollBottomCheck();
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      const nearBottom =
        window.innerHeight + window.scrollY >= document.body.scrollHeight - 8;

      if (nearBottom) {
        setActiveById('kontak');
        return;
      }

      const visible = entries
        .filter(e => e.isIntersecting)
        .sort((a,b) => (b.intersectionRatio - a.intersectionRatio))[0];

      if (visible?.target?.id) {
        setActiveById(visible.target.id);
      }
    }, {
      root: null,
      threshold: [0.12, 0.2, 0.35, 0.5],
      rootMargin: '-140px 0px -55% 0px'
    });

    elements.forEach(el => observer.observe(el));

    setActiveByHash();
    onScrollBottomCheck();
  });
  </script>

  @stack('scripts')
</body>
</html>