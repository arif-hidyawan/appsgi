@extends('pdf.layout')

@section('content')
<style>
    /* --- CSS UTAMA (Sama dengan Quotation untuk Konsistensi) --- */
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 10pt;
        color: #333;
        line-height: 1.3;
    }
    
    /* Helper Classes */
    .w-100 { width: 100%; }
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
    .info-box {
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

{{-- ================= HEADER (MULTI-TENANCY) ================= --}}
<table class="header-line">
    <tr>
        {{-- Kiri: Logo & Info Perusahaan Pembuat PO --}}
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

        {{-- Kanan: Judul & Meta Data PO --}}
        <td width="45%" class="valign-top text-right">
            <div class="doc-title">PURCHASE ORDER</div>
            <table width="100%" style="margin-top: 10px; font-size: 9pt; border-collapse: collapse;">
                <tr>
                    <td class="text-right bold" width="60%" style="padding: 2px 0;">No. PO:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->po_number }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Tanggal Order:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->date->format('d/m/Y') }}</td>
                </tr>
                @if($record->salesOrder)
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Ref SO Internal:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->salesOrder->so_number }}</td>
                </tr>
                @endif
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Status:</td>
                    <td class="text-right" style="padding: 2px 0; text-transform: uppercase;">{{ $record->status }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ================= VENDOR & SHIPPING INFO ================= --}}
<table style="margin-bottom: 20px;">
    <tr>
        {{-- Info Vendor --}}
        <td width="55%" class="valign-top" style="padding: 0; padding-right: 15px;">
            <div class="info-box">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    KEPADA VENDOR:
                </div>
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->vendor->name }}
                </div>
                <div style="font-size: 9pt; color: #444;">
                    {{ $record->vendor->address ?? '-' }}<br>
                    @if($record->contact)
                        <strong>UP: {{ $record->contact->pic_name }}</strong> ({{ $record->contact->phone ?? '-' }})
                    @else
                        Telp: {{ $record->vendor->phone ?? '-' }}
                    @endif
                </div>
            </div>
        </td>

        {{-- Info Pengiriman (Opsional / Default ke Kantor) --}}
        <td width="45%" class="valign-top" style="padding: 0;">
            <div class="info-box" style="border-left-color: #95a5a6; background-color: #fff; border: 1px solid #ddd; border-left: 5px solid #95a5a6;">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    ALAMAT PENGIRIMAN:
                </div>
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->company->name }} 
                </div>
                <div style="font-size: 9pt; color: #444;">
                    {{ $record->company->address }}<br>
                    Mohon cantumkan No. PO pada Surat Jalan.
                </div>
            </div>
        </td>
    </tr>
</table>

<div style="margin-bottom: 10px; font-style: italic; font-size: 9pt;">
    Mohon disediakan/dikirimkan barang-barang berikut ini sesuai dengan spesifikasi yang telah disepakati:
</div>

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
                <span style="font-size: 8pt; color: #666;">SKU: {{ $item->product->item_code ?? '-' }}</span>
            </td>
            <td class="text-center valign-top">{{ $item->qty }}</td>
            <td class="text-right valign-top">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right valign-top">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ================= TOTALS & FOOTER ================= --}}
<table class="footer-table">
    <tr>
        {{-- KOLOM KIRI: Catatan / Instruksi --}}
        <td width="60%" class="valign-top">
            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 5px; border-bottom: 1px solid #ccc; display: inline-block;">
                CATATAN & INSTRUKSI
            </div>
            <ul class="notes-ul">
                <li>Harap konfirmasi ketersediaan stok segera.</li>
                <li>Faktur tagihan harus melampirkan salinan PO ini.</li>
                <li>Barang yang tidak sesuai pesanan akan dikembalikan (Retur).</li>
            </ul>
        </td>

        {{-- KOLOM KANAN: Total & Tanda Tangan --}}
        <td width="40%" class="valign-top">
            {{-- Tabel Total --}}
            <table width="100%" style="margin-bottom: 20px;">
                <tr>
                    <td class="bg-dark text-right bold" style="padding: 8px;">GRAND TOTAL:</td>
                    <td class="bg-dark text-right bold" style="padding: 8px;">Rp {{ number_format($record->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>

            {{-- Tanda Tangan --}}
            <div class="text-center">
                <div style="margin-bottom: 5px;">Authorized Signature,</div>
                <div class="bold" style="margin-bottom: 5px;">{{ $record->company->name }}</div>
                
                {{-- Space Tanda Tangan --}}
                <div class="sign-line"></div>
                
                <div class="bold">Purchasing Department</div>
            </div>
        </td>
    </tr>
</table>
@endsection