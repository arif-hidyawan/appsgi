@extends('pdf.layout')

@section('content')
<style>
    /* CSS UTAMA - MENGGUNAKAN STANDAR CSS 2.1 (DOMPDF FRIENDLY) */
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 10pt;
        color: #333;
        line-height: 1.3;
    }
    
    /* Helper Classes */
    .w-100 { width: 100%; }
    .w-50 { width: 50%; }
    .w-60 { width: 60%; }
    .w-40 { width: 40%; }
    
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    
    .valign-top { vertical-align: top; }
    .bold { font-weight: bold; }
    
    /* Colors */
    .bg-dark { background-color: #2c3e50; color: white; }
    .text-dark { color: #2c3e50; }
    .bg-gray { background-color: #f8f9fa; }

    /* Tables */
    table { border-collapse: collapse; width: 100%; border-spacing: 0; }
    td, th { padding: 5px; }

    /* Header Styling */
    .header-line { border-bottom: 2px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
    .doc-title { font-size: 20pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50; }
    
    /* Info Box */
    .client-box {
        background-color: #f4f6f7;
        border-left: 5px solid #2c3e50;
        padding: 10px;
    }

    /* Items Table */
    .items-table { margin-top: 20px; margin-bottom: 20px; font-size: 9pt; }
    .items-table th { background-color: #2c3e50; color: white; padding: 8px; text-transform: uppercase; }
    .items-table td { border-bottom: 1px solid #ddd; padding: 8px; }
    
    /* Footer Styling */
    .footer-table { margin-top: 20px; page-break-inside: avoid; }
    .sign-line { border-bottom: 1px solid #333; width: 80%; margin: 0 auto; margin-top: 60px; }
    .notes-ul { margin: 0; padding-left: 20px; font-size: 9pt; }
    .notes-ul li { margin-bottom: 3px; }
</style>

{{-- ================= HEADER ================= --}}
<table class="header-line">
    <tr>
        <td width="55%" class="valign-top">
            @if($record->company->logo)
                <img src="{{ public_path('storage/' . $record->company->logo) }}" height="60" style="display: block; margin-bottom: 5px;">
            @else
                <h2 style="margin:0; color:#2c3e50;">{{ $record->company->code }}</h2>
            @endif
            
            <div style="font-size: 14pt; font-weight: 800; color: #2c3e50; text-transform: uppercase;">
                {{ $record->company->name }}
            </div>
            <div style="font-size: 9pt; color: #555;">
                {{ $record->company->address }}<br>
                @if($record->company->phone) Telp: {{ $record->company->phone }} @endif
                @if($record->company->email) | Email: {{ $record->company->email }} @endif
            </div>
        </td>
        <td width="45%" class="valign-top text-right">
    <div class="doc-title">QUOTATION</div>
    
    {{-- Tambahkan border-collapse agar jarak lebih rapat --}}
    <table width="100%" style="margin-top: 5px; font-size: 9pt; border-collapse: collapse;">
        <tr>
            {{-- Tambahkan style="padding: 2px 0;" di setiap TD untuk mengurangi tinggi baris --}}
            <td class="text-right bold" width="60%" style="padding: 2px 0;">No. Penawaran:</td>
            <td class="text-right" style="padding: 2px 0;">{{ $record->quotation_number }}</td>
        </tr>
        <tr>
            <td class="text-right bold" style="padding: 2px 0;">Tanggal:</td>
            <td class="text-right" style="padding: 2px 0;">{{ $record->date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="text-right bold" style="padding: 2px 0;">Masa Berlaku:</td>
            <td class="text-right" style="padding: 2px 0;">14 Hari</td>
        </tr>
        <tr>
            <td class="text-right bold" style="padding: 2px 0;">Sales Rep:</td>
            <td class="text-right" style="padding: 2px 0;">{{ $record->sales->name ?? '-' }}</td>
        </tr>
        @if($record->quotation && $record->quotation->rfq)
        <tr>
            <td class="text-right bold" style="padding: 2px 0;">Ref RFQ:</td>
            <td class="text-right" style="padding: 2px 0;">{{ $record->quotation->rfq->rfq_number }}</td>
        </tr>
        @endif
    </table>
</td>
    </tr>
</table>

{{-- ================= CUSTOMER INFO ================= --}}
<table style="margin-bottom: 20px;">
    <tr>
        <td width="60%" class="valign-top" style="padding: 0;">
            <div class="client-box">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    DITUJUKAN KEPADA:
                </div>
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->customer->name }}
                </div>
                <div style="font-size: 9pt; color: #444;">
                    {{ $record->customer->address ?? '-' }}<br>
                    @if($record->contact)
                        <strong>UP: {{ $record->contact->pic_name }}</strong><br>
                        Telp: {{ $record->contact->phone ?? '-' }}
                    @else
                        Telp: {{ $record->customer->phone ?? '-' }}
                    @endif
                </div>
            </div>
        </td>
        <td width="40%" class="valign-top" style="padding-left: 20px;">
            @if($record->notes)
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    CATATAN:
                </div>
                <div style="font-style: italic; font-size: 9pt;">
                    {{ $record->notes }}
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- ================= ITEMS TABLE ================= --}}
<table class="items-table">
    <thead>
        <tr>
            <th width="5%" class="text-center">NO</th>
            <th width="45%">DESKRIPSI PRODUK</th>
            <th width="10%" class="text-center">QTY</th>
            <th width="20%" class="text-right">HARGA SATUAN</th>
            <th width="20%" class="text-right">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($record->items as $index => $item)
        <tr class="{{ $index % 2 == 0 ? '' : 'bg-gray' }}">
            <td class="text-center valign-top">{{ $index + 1 }}</td>
            <td class="valign-top">
                <span class="bold">{{ $item->product->name }}</span>
                <br>
                <span style="font-size: 8pt; color: #666;">Kode: {{ $item->product->item_code ?? '-' }}</span>
                @if($item->notes)
                    <br><span style="font-size: 8pt; font-style: italic; color: #555;">Note: {{ $item->notes }}</span>
                @endif
            </td>
            <td class="text-center valign-top">{{ $item->qty }}</td>
            <td class="text-right valign-top">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right valign-top">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ================= TOTALS & FOOTER (LAYOUT TABEL 2 KOLOM) ================= --}}
{{-- PENTING: Gunakan TABLE untuk layout ini agar tidak overlapping di DomPDF --}}
<table class="footer-table">
    <tr>
        {{-- KOLOM KIRI: Syarat & Ketentuan --}}
        <td width="60%" class="valign-top">
            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 5px; border-bottom: 1px solid #ccc; display: inline-block;">
                SYARAT & KETENTUAN
            </div>
            <ul class="notes-ul">
                <li>Harga belum termasuk PPN (kecuali disebutkan lain).</li>
                <li>Penawaran berlaku <strong>14 hari</strong> sejak tanggal terbit.</li>
                <li>Pembayaran ditransfer ke rekening resmi:</li>
                <li style="list-style: none; margin-top: 5px;">
                    <strong>A/N: {{ $record->company->name }}</strong>
                </li>
            </ul>
        </td>

        {{-- KOLOM KANAN: Total & Tanda Tangan --}}
        <td width="40%" class="valign-top">
            {{-- Tabel Total --}}
            <table width="100%" style="margin-bottom: 20px;">
                <tr>
                    <td class="text-right bold" style="padding: 2px;">Subtotal:</td>
                    <td class="text-right" style="padding: 2px;">Rp {{ number_format($record->grand_total, 0, ',', '.') }}</td>
                </tr>
                {{-- Placeholder PPN --}}
                {{-- 
                <tr>
                    <td class="text-right bold" style="padding: 2px;">PPN 11%:</td>
                    <td class="text-right" style="padding: 2px;">Rp 0</td>
                </tr> 
                --}}
                <tr>
                    <td class="bg-dark text-right bold" style="padding: 8px;">TOTAL ESTIMASI:</td>
                    <td class="bg-dark text-right bold" style="padding: 8px;">Rp {{ number_format($record->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>

            {{-- Tanda Tangan --}}
            <div class="text-center">
                <div style="margin-bottom: 5px;">Hormat Kami,</div>
                <div class="bold" style="margin-bottom: 5px;">{{ $record->company->name }}</div>
                
                {{-- Space Tanda Tangan --}}
                <div class="sign-line"></div>
                
                <div class="bold">{{ $record->sales->name ?? 'Sales Admin' }}</div>
            </div>
        </td>
    </tr>
</table>

@endsection