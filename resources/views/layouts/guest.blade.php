<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Logo -->
        <link rel="icon" href="/favicon.ico" type="image/x-icon" />

        <style>
          @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

          :root{
            --navy:#1a2253;
            --navy-dim:#414b7d;
            --lime:#9de35d;
            --lime-dim:#7bc53f;
            --bg:#f6f7fb;
            --ink:#12173a;
            --muted:#5a6186;
            --card:#ffffff;
            --line:#e3e6f0;
          }
          *{box-sizing:border-box;}
          html,body{margin:0;padding:0;}
          body{
            background:var(--bg);
            background-image:
              radial-gradient(circle at 15% 0%, rgba(26,34,83,0.05), transparent 45%),
              radial-gradient(circle at 85% 100%, rgba(157,227,93,0.10), transparent 50%);
            color:var(--ink);
            font-family:'Inter',sans-serif;
            min-height:100vh;
            line-height:1.5;
          }
          .wrap{max-width:640px;margin:0 auto;padding:36px 20px 80px;}
          .eyebrow{
            font-family:'JetBrains Mono',monospace;
            font-size:12px;
            letter-spacing:0.14em;
            text-transform:uppercase;
            color:var(--navy-dim);
            display:flex;align-items:center;gap:10px;
            margin-bottom:18px;
          }
          .eyebrow::before{content:"";width:22px;height:1px;background:var(--navy-dim);display:inline-block;}
          h1{
            font-family:'Cormorant Garamond',serif;
            font-weight:600;
            font-size:clamp(32px,7vw,46px);
            line-height:1.08;
            margin:0 0 16px;
            color:var(--navy);
          }
          h1 em{font-style:italic;color:var(--lime-dim);}
          .lede{color:var(--muted);font-size:15.5px;max-width:54ch;margin:0 0 6px;}
          .verse{
            font-family:'Cormorant Garamond',serif;
            font-style:italic;
            font-size:18px;
            color:var(--navy-dim);
            border-left:2px solid var(--lime-dim);
            padding-left:16px;
            margin:28px 0 32px;
          }
          .verse span{display:block;font-family:'JetBrains Mono',monospace;font-style:normal;font-size:11px;letter-spacing:.08em;color:var(--muted);margin-top:6px;}
          .card{
            position:relative;
            background:var(--card);
            border:1px solid var(--line);
            border-radius:4px;
            padding:32px 28px 28px;
            margin-top:8px;
            box-shadow:0 1px 2px rgba(18,23,58,0.05), 0 8px 24px -18px rgba(26,34,83,0.25);
          }
          .card::before{
            content:"";
            position:absolute; top:0; left:0; right:0; height:6px;
            background-repeat:repeat-x;
            background-size:14px 6px;
            background-image:radial-gradient(circle at 7px 0, transparent 5px, var(--navy) 5px);
            transform:translateY(-1px);
          }
          .card-title{font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--navy-dim);margin-bottom:2px;}
          .card-sub{color:var(--muted);font-size:13px;margin-bottom:22px;}
        </style>

        <!-- Shared per-page styles (e.g. a pledge flow reusing this layout) -->
        @stack('styles')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body>
        <div class="font-sans text-gray-900 antialiased">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
