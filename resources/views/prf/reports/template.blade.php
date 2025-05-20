<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mission Report - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Logo -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Only apply background to the page-background div, not the body */
        body {
            background-color: white;
            position: relative;
            /* A4 dimensions for screen preview */
            max-width: 210mm;
            margin: 0 auto;
        }

        /* Fixed background for screen display */
        .page-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url("{{ url('/PDF_background.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 1;
            pointer-events: none;
            /* Prevents the background from capturing clicks */
        }

        /* Content container for better readability */
        .content-container {
            position: relative;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem auto;
            max-width: 100%;
            z-index: 1;
            /* Ensure content is above background */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            /* Optional: adds subtle shadow */
        }

        @media print {

            /* Ensure background images are printed */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html,
            body {
                width: 210mm;
                /* A4 width */
                height: auto !important;
                /* Allow content to determine height */
                margin: 0;
                padding: 0;
                background: white;
            }

            /* Remove body background for print to avoid duplication */
            body {
                background: none !important;
                max-width: none;
                overflow: visible !important;
            }

            /* For print, keep the background visible */
            .page-background {
                display: block;
                /* Show the fixed background when printing */
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: -1;
                background-image: url("{{ url('/PDF_background.png') }}");
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                opacity: 1;
            }

            .page-break-before {
                page-break-before: always;
            }

            .page-break-after {
                page-break-after: always;
            }

            /* Add this new class for elements that come after a page break */
            .after-page-break {
                padding-top: 3.5cm; /* Add top padding to prevent overlap */
            }

            .avoid-break {
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                /* Explicitly set A4 size in portrait orientation */
                margin: 0;
                /* Remove all page margins to allow background to extend to edges */
                /* Remove background from @page to avoid duplication */
                background: none;
            }

            /* Make content container semi-transparent for print with internal margins */
            .content-container {
                box-shadow: none;
                margin: 2cm 1.5cm;
                /* Apply margins to the content container instead of the page */
                max-width: calc(100% - 3cm);
                /* Account for left and right margins */
                padding: 0.5rem;
                border-radius: 0;
            }

            /* Ensure tables fit within page */
            table {
                max-width: 100%;
                width: 100%;
                table-layout: fixed;
            }

            /* Ensure text wraps in table cells */
            td {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-gray-800">
    @yield('content')
</body>

</html>
