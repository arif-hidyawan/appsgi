<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran #{{ $record->payment_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 16px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 150px;
        }
        .amount-box {
            border: 2px solid #333;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
        .signature {
            margin-top: 60px;
            border-top: 1px solid #333;
            display: inline-block;
            width: 200px;
            text-align: center;
            padding-top: 5px;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>KWITANSI PEMBAYARAN</h1>
        <p>{{ $record->company->name ?? 'Perusahaan' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">No. Kwitansi</td>
            <td>: {{ $record->payment_number }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ $record->date->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Telah Terima Dari</td>
            <td>: {{ $record->invoice->customer->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Untuk Pembayaran</td>
            <td>: Pelunasan Invoice No. <strong>{{ $record->invoice->invoice_number ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Metode Bayar</td>
            <td>: {{ $record->payment_method }}</td>
        </tr>
        @if($record->reference_number)
        <tr>
            <td class="label">No. Referensi</td>
            <td>: {{ $record->reference_number }}</td>
        </tr>
        @endif
        @if($record->notes)
        <tr>
            <td class="label">Catatan</td>
            <td>: {{ $record->notes }}</td>
        </tr>
        @endif
    </table>

    <div class="amount-box">
        Rp {{ number_format($record->amount, 0, ',', '.') }}
    </div>

    <div class="footer">
        <p>{{ $record->company->city ?? 'Surabaya' }}, {{ now()->format('d F Y') }}</p>
        <div class="signature">
            Bagian Keuangan
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>