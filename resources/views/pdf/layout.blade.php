<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dokumen PDF</title>
    <style>
        /* --- GLOBAL PDF SETTINGS --- */
        
        /* Margin Kertas Global */
        @page {
            margin: 1.5cm 2cm; 
        }

        /* Reset Body */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }

        /* Utility Classes Dasar */
        table {
            border-collapse: collapse;
            width: 100%;
            border-spacing: 0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .w-100 { width: 100%; }
        
        /* Helper untuk Halaman Baru (jika perlu) */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    {{-- 
        HEADER GLOBAL DIHAPUS.
        Sekarang Header dikendalikan oleh masing-masing View (Quotation, Invoice, dll)
        menggunakan data dari database (Company Relation).
    --}}

    @yield('content')

</body>
</html>