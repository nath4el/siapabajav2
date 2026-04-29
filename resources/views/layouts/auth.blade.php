<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SIAPABAJA')</title>

  {{-- Google reCAPTCHA --}}
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  {{-- Nunito --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="auth-body">
  @yield('content')

  {{-- ✅ CHATBOT --}}
  @include('Partials.chatbot')
</body>
</html>