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
    .doc-title { font-size: 16pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50; }
    
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

        {{-- Kanan: Judul & Meta Data BAST --}}
        <td width="45%" class="valign-top text-right">
            <div class="doc-title">BERITA ACARA</div>
            <div style="font-size: 10pt; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">SERAH TERIMA BARANG (BAST)</div>
            
            <table width="100%" style="font-size: 9pt; border-collapse: collapse;">
                <tr>
                    <td class="text-right bold" width="60%" style="padding: 2px 0;">No. BAST:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->bast_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Ref Surat Jalan:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->do_number }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Tanggal Serah Terima:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="text-right bold" style="padding: 2px 0;">Ref Sales Order:</td>
                    <td class="text-right" style="padding: 2px 0;">{{ $record->salesOrder->so_number ?? '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ================= INFO PIHAK TERKAIT ================= --}}
<table style="margin-bottom: 20px;">
    <tr>
        <td width="55%" class="valign-top" style="padding: 0; padding-right: 15px;">
            <div class="info-box">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    PIHAK PERTAMA (PENGIRIM):
                </div>
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->company->name }}
                </div>
                <div style="font-size: 9pt; color: #444;">
                    Driver/Kurir: <strong>{{ $record->driver_name ?? '-' }}</strong><br>
                    Kendaraan: {{ $record->vehicle_number ?? '-' }}
                </div>
            </div>
        </td>

        <td width="45%" class="valign-top" style="padding: 0;">
            <div class="info-box" style="border-left-color: #95a5a6; background-color: #fff; border: 1px solid #ddd; border-left: 5px solid #95a5a6;">
                <div style="font-size: 8pt; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">
                    PIHAK KEDUA (PENERIMA):
                </div>
                <div style="font-size: 10pt; font-weight: bold; margin-bottom: 3px;">
                    {{ $record->customer->name }}
                </div>
                <div style="font-size: 9pt; color: #444;">
                    {{ $record->customer->address ?? '-' }}
                </div>
            </div>
        </td>
    </tr>
</table>

<div style="margin-bottom: 10px; font-size: 9pt;">
    Telah diserahterimakan barang-barang dalam keadaan baik dan lengkap dengan rincian sebagai berikut:
</div>

{{-- ================= ITEMS TABLE ================= --}}
<table class="items-table">
    <thead>
        <tr>
            <th width="5%" class="text-center">NO</th>
            <th width="50%">NAMA BARANG / DESKRIPSI</th>
            <th width="15%" class="text-center">QTY</th>
            <th width="15%" class="text-center">SATUAN</th>
            <th width="15%" class="text-center">KONDISI</th>
        </tr>
    </thead>
    <tbody>
        @forelse($record->items as $index => $item)
        <tr class="{{ $index % 2 == 0 ? '' : 'bg-gray' }}">
            <td class="text-center valign-top">{{ $index + 1 }}</td>
            <td class="valign-top">
                <span class="bold">{{ $item->product->name }}</span>
                <br>
                <span style="font-size: 8pt; color: #666;">Kode: {{ $item->product->item_code ?? '-' }}</span>
            </td>
            <td class="text-center valign-top bold">{{ $item->qty_delivered }}</td>
            <td class="text-center valign-top">{{ $item->product->unit->name ?? 'Pcs' }}</td>
            <td class="text-center valign-top" style="font-size: 8pt;">Baik / Rusak / Kurang</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center" style="padding: 20px;">Tidak ada data barang.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ================= FOOTER / SIGNATURES ================= --}}
<table class="sign-table">
    <tr>
        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Pihak Pertama (Pengirim),</div>
            <div style="font-size: 8pt; color: #666;">(Driver / Ekspedisi)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">{{ $record->driver_name ?? '......................' }}</div>
        </td>

        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Mengetahui,</div>
            <div style="font-size: 8pt; color: #666;">(Admin Gudang)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">{{ $record->creator->name ?? '......................' }}</div>
        </td>

        <td width="33%" class="valign-top sign-box">
            <div style="font-size: 9pt; margin-bottom: 5px;">Pihak Kedua (Penerima),</div>
            <div style="font-size: 8pt; color: #666;">(Customer / Gudang Penerima)</div>
            
            <div class="sign-line"></div>
            <div class="sign-label">Nama Jelas & Stempel</div>
        </td>
    </tr>
</table>

<div style="margin-top: 30px; font-size: 8pt; font-style: italic; color: #555; text-align: center;">
    Dokumen ini merupakan bukti sah serah terima barang. Harap disimpan dengan baik.
</div>

@endsection