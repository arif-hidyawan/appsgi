@extends('pdf.layout')

@section('content')
<style>
    /* --- CSS UTAMA (Konsisten dengan Dokumen Lain) --- */
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
    .bg-gray { background-color: #f8f9fa; }

    /* Tables */
    table { border-collapse: collapse; width: 100%; border-spacing: 0; }
    td, th { padding: 5px; }

    /* Header Styling */
    .header-line { border-bottom: 2px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
    .doc-title { font-size: 18pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50; }
    
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
    
    /* Signature Styling */
    .sign-table { margin-top: 40px; page-break-inside: avoid; width: 100%; }
    .sign-box { text-align: center; padding: 0 10px; }
    .sign-line { border-bottom: 1px solid #333; margin-top: 70px; width: 80%; margin-left: auto; margin-right: auto; }
    .sign-label { font-weight: bold; font-size: 9pt; margin-top: 5px; }
</style>

{{-- ================= HEADER (MULTI-TENANCY) ================= --}}
<table class="header-line">
    <tr>
        {{-- Kiri: Logo & Info Perusahaan Pengirim --}}
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

        {{-- Kanan: Judul & Meta Data DO --}}
        <td width="45%" class="valign-top text-right">
            <div class="doc-title">DELIVERY ORDER</div>
            <div style="font-size: 9pt; letter-spacing: 1px; margin-bottom: 10px;">(SURAT JALAN)</div>
            
            <table width="100%" style="font-size: 9pt; border-collapse: collapse;">
                <tr>
                    <td class="text-right bold" width="60%" style="padding: 2px 0;">No. Surat Jalan:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->do_number }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Tanggal Kirim:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->date->format('d/m/Y') }}</td>
                </tr>
                @if($record->salesOrder)
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Ref Sales Order:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->salesOrder->so_number }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Ref PO Customer:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->salesOrder->customer_po_number ?? '-' }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ================= CUSTOMER & LOGISTICS INFO ================= --}}
<table style="margin-bottom: 20px;">
    <tr>
        {{-- Info Penerima (Customer) --}}
        <td width="55%" class="valign-top" style="padding: 0; padding-right: 15px;">
            <div class="info-box">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    DIKIRIM KEPADA:
                </div>
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->customer->name }}
                </div>
                <div style="font-size: 9pt; color: #444;">
                    {{ $record->customer->address ?? '-' }}<br>
                    @if($record->customer->phone) Telp: {{ $record->customer->phone }} @endif
                </div>
            </div>
        </td>

        {{-- Info Kendaraan --}}
        <td width="45%" class="valign-top" style="padding: 0;">
            <div class="info-box" style="border-left-color: #95a5a6; background-color: #fff; border: 1px solid #ddd; border-left: 5px solid #95a5a6;">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    INFO PENGIRIMAN:
                </div>
                <table width="100%" style="font-size: 9pt;">
                    <tr>
                        <td width="40%" style="padding: 2px 0;"><strong>Kendaraan:</strong></td>
                        <td style="padding: 2px 0;">{{ $record->vehicle_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;"><strong>Sopir / Driver:</strong></td>
                        <td style="padding: 2px 0;">{{ $record->driver_name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- ================= ITEMS TABLE ================= --}}
<table class="items-table">
    <thead>
        <tr>
            <th width="5%" class="text-center">NO</th>
            <th width="15%">KODE BARANG</th>
            <th width="45%">NAMA PRODUK / DESKRIPSI</th>
            <th width="10%" class="text-center">QTY</th>
            <th width="10%" class="text-center">SATUAN</th>
            <th width="15%" class="text-center">CEKLIST</th>
        </tr>
    </thead>
    <tbody>
        @foreach($record->items as $index => $item)
        <tr class="{{ $index % 2 == 0 ? '' : 'bg-gray' }}">
            <td class="text-center valign-top">{{ $index + 1 }}</td>
            <td class="valign-top">{{ $item->product->item_code ?? '-' }}</td>
            <td class="valign-top">
                <span class="bold">{{ $item->product->name }}</span>
            </td>
            <td class="text-center valign-top bold">{{ $item->qty_delivered }}</td>
            <td class="text-center valign-top">{{ $item->product->unit->name ?? 'Pcs' }}</td>
            <td class="text-center valign-top" style="color: #ccc;">[ &nbsp;&nbsp;&nbsp; ]</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ================= FOOTER / SIGNATURES ================= --}}
<div style="margin-top: 10px; font-size: 9pt; font-style: italic; color: #555;">
    Catatan: Mohon barang dicek kembali. Komplain kerusakan/kekurangan barang maksimal 1x24 jam setelah barang diterima.
</div>

<table class="sign-table">
    <tr>
        {{-- Tanda Tangan Penerima --}}
        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Penerima / Customer,</div>
            <div style="font-size: 8pt; color: #666;">(Nama Jelas & Stempel)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">Tgl: .......................</div>
        </td>

        {{-- Tanda Tangan Sopir --}}
        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Pengirim / Sopir,</div>
            <div style="font-size: 8pt; color: #666;">(Driver)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">{{ $record->driver_name ?? '......................' }}</div>
        </td>

        {{-- Tanda Tangan Gudang --}}
        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Hormat Kami,</div>
            <div style="font-size: 8pt; color: #666;">(Bagian Gudang)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">{{ $record->company->name }}</div>
        </td>
    </tr>
</table>

{{-- Halaman --}}
<div style="position: fixed; bottom: 0; right: 0; font-size: 8pt; color: #aaa;">
    Dicetak pada: {{ now()->format('d/m/Y H:i') }}
</div>

@endsection