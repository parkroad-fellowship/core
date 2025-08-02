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
            overflow-x: auto; /* Allow horizontal scrolling if needed */
        }

        /* Responsive improvements for screen */
        @media screen {
            .content-container {
                max-width: 1200px; /* Limit width on large screens */
                padding: 2rem;
            }
            
            /* Make tables horizontally scrollable on small screens */
            .overflow-x-auto {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Better responsive grid handling */
            @media (max-width: 768px) {
                .md\\:grid-cols-2 {
                    grid-template-columns: 1fr !important;
                }
            }
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
                font-size: 10px; /* Smaller font for print */
            }

            /* Ensure text wraps in table cells */
            td, th {
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
                max-width: 0; /* Forces table-layout: fixed to work */
            }

            /* Smaller padding for print */
            @media print {
                td, th {
                    padding: 1px 2px !important;
                    font-size: 8px !important;
                    line-height: 1.1 !important;
                }

                /* Make grid responsive for print */
                .grid-cols-2 {
                    display: block !important;
                }
                
                .grid-cols-2 > div {
                    display: block !important;
                    width: 100% !important;
                    margin-bottom: 0.25rem !important;
                }

                /* Reduce font sizes for better fit */
                h1 { font-size: 16px !important; }
                h2 { font-size: 12px !important; }
                h3 { font-size: 10px !important; }
                
                /* Force break words everywhere for print */
                * {
                    word-break: break-word !important;
                    overflow-wrap: break-word !important;
                }

                /* Reduce margins for print */
                .mb-8 { margin-bottom: 1rem !important; }
                .mb-6 { margin-bottom: 0.75rem !important; }
                .mb-4 { margin-bottom: 0.5rem !important; }
                .mb-2 { margin-bottom: 0.25rem !important; }

                /* Smaller padding in containers */
                .content-container {
                    padding: 0.25rem !important;
                    margin: 1.5cm 1cm !important;
                }

                /* Make tables more compact */
                table {
                    font-size: 7px !important;
                }

                /* Reduce executive summary padding */
                .bg-blue-50 {
                    padding: 0.5rem !important;
                }
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-gray-800">
    @yield('content')
</body>

</html>
