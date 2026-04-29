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

  @include('Partials.navbar-home')

  <main>
    @yield('content')
  </main>

  @include('Partials.footer')

  {{-- ✅ CHATBOT --}}
  @include('Partials.chatbot')

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  {{-- dropdown user --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const btn = document.getElementById('homeUserBtn');
      const dd  = document.getElementById('homeUserDropdown');
      if (!btn || !dd) return;

      dd.style.display = 'none';
      const toggle = () => dd.style.display = (dd.style.display === 'block') ? 'none' : 'block';

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggle();
      });

      document.addEventListener('click', () => dd.style.display = 'none');
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') dd.style.display = 'none'; });
    });
  </script>

  {{-- ✅ NAV ACTIVE + SMOOTH (HOME) --}}
  <script>
document.addEventListener('DOMContentLoaded', () => {
  const links = Array.from(document.querySelectorAll('.nav-links a.nav-link'));
  if (!links.length) return;

  const targets = Array.from(document.querySelectorAll('[id]'))
    .filter(el => ['regulasi','kontak','statistika','arsip'].includes(el.id));

  if (targets.length < 2) return;

  const setActiveById = (id) => {
    links.forEach(a => a.classList.remove('active'));
    const match = links.find(a => (a.getAttribute('href') || '').includes('#' + id));
    if (match) match.classList.add('active');
  };

  const getCurrent = () => {
    const offset = 140;
    let current = null;

    targets.forEach(el => {
      const top = el.getBoundingClientRect().top;
      if (top - offset <= 0) current = el.id;
    });

    return current;
  };

  const onScroll = () => {
    const nearBottom = window.innerHeight + window.scrollY >= document.body.scrollHeight - 20;
    if (nearBottom) return setActiveById('kontak');

    const current = getCurrent();
    if (current) setActiveById(current);
  };

  links.forEach(a => {
    a.addEventListener('click', () => {
      const href = a.getAttribute('href') || '';
      const hashIndex = href.indexOf('#');
      if (hashIndex !== -1) {
        const id = href.substring(hashIndex + 1);
        if (id) setActiveById(id);
      }
    });
  });

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('hashchange', onScroll);

  onScroll();
});
</script>

  @stack('scripts')
</body>
</html>