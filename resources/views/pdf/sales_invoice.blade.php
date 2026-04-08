@extends('pdf.layout')

@section('content')
    <div class="title" style="text-align: center; margin-bottom: 20px; font-size: 24px; font-weight: bold;">
        {{ $record->is_dp ? 'INVOICE DOWN PAYMENT (DP)' : 'INVOICE' }}
    </div>

    <table class="details-table" style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
        <tr>
            <td width="15%" style="vertical-align: top;"><strong>Ditagihkan ke:</strong></td>
            <td width="40%" style="vertical-align: top;">
                <strong>{{ $record->customer->name }}</strong><br>
                {{ $record->customer->billing_address ?? $record->customer->address ?? '-' }}<br>
                @if($record->customer->tax_id)
                    <br><strong>NPWP:</strong> {{ $record->customer->tax_id }}
                @endif
            </td>
            <td width="18%" style="vertical-align: top;">
                <strong>No. Invoice:</strong><br>
                <strong>Tgl Invoice:</strong><br>
                <strong>Jatuh Tempo:</strong><br>
                <strong>PO Customer:</strong> </td>
            <td width="27%" style="vertical-align: top;">
                {{ $record->invoice_number }}<br>
                {{ $record->date->format('d F Y') }}<br>
                <span style="color: red; font-weight: bold;">{{ $record->due_date->format('d F Y') }}</span><br>
                
                @php
                    $poCustomers = $record->items
                        ->map(fn($item) => $item->salesOrder->customer_po_number ?? null)
                        ->filter()
                        ->unique()
                        ->implode(', ');
                @endphp
                <strong>{{ $poCustomers ?: '-' }}</strong>
            </td>
        </tr>
        
        @if($record->notes)
        <tr>
            <td style="vertical-align: top; padding-top: 10px;"><strong>Catatan:</strong></td>
            <td colspan="3" style="vertical-align: top; padding-top: 10px;">
                {{ $record->notes }}
            </td>
        </tr>
        @endif
    </table>

    <table class="items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" border="1" cellpadding="8">
        <thead style="background-color: #f3f4f6;">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th>Deskripsi Produk</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="20%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $index => $item)
                @php
                    // Logika Cerdas: Coba ambil custom_name dari tabel SO Item jika ini berasal dari SO
                    $customName = null;
                    if ($item->sales_order_id && $item->product_id) {
                        $soItem = \App\Models\SalesOrderItem::where('sales_order_id', $item->sales_order_id)
                                    ->where('product_id', $item->product_id)
                                    ->first();
                        $customName = $soItem ? $soItem->custom_name : null;
                    }
                    
                    // Fallback ke nama master product jika custom name kosong
                    $displayName = $customName ?? $item->product->name ?? 'Unknown Product';
                @endphp
                <tr>
                    <td class="text-center" style="vertical-align: top;">{{ $index + 1 }}</td>
                    <td style="vertical-align: top;">
                        <strong>{{ $displayName }}</strong><br>
                        <small style="color: #6b7280;">Kode: {{ $item->product->item_code ?? '-' }}</small>
                    </td>
                    <td class="text-center" style="vertical-align: top;">{{ $item->qty }}</td>
                    <td class="text-right" style="vertical-align: top;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right" style="vertical-align: top;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="padding-top: 15px;"><strong>Subtotal (DPP)</strong></td>
                <td class="text-right" style="padding-top: 15px;">Rp {{ number_format($record->subtotal_amount, 0, ',', '.') }}</td>
            </tr>
            @if($record->tax_amount > 0)
            <tr>
                <td colspan="4" class="text-right"><strong>PPN (Pajak)</strong></td>
                <td class="text-right">Rp {{ number_format($record->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="4" class="text-right" style="background-color: #f3f4f6;"><strong>TOTAL TAGIHAN</strong></td>
                <td class="text-right" style="background-color: #f3f4f6; font-size: 16px;"><strong>Rp {{ number_format($record->grand_total, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <table style="width: 100%; margin-top: 30px; border: none;">
        <tr>
            <td width="60%" style="vertical-align: top;">
                <div style="border: 1px solid #d1d5db; padding: 15px; border-radius: 5px;">
                    <strong style="font-size: 14px;">Instruksi Pembayaran:</strong><br><br>
                    Harap melakukan pembayaran penuh (Full Amount) ke rekening berikut:<br>
                    <strong>BCA:</strong> 123-456-7890<br>
                    <strong>Mandiri:</strong> 987-654-3210<br>
                    A.n. <strong>PT SAPUTRA GROUP INDONESIA</strong><br><br>
                    <small style="color: #6b7280;"><em>*Cantumkan No. Invoice ({{ $record->invoice_number }}) pada berita transfer.</em></small>
                </div>
            </td>
            <td width="40%" style="vertical-align: bottom; text-align: center;">
                <div class="signature">
                    <p style="margin-bottom: 70px;">Hormat Kami,<br><strong>Finance Department</strong></p>
                    <div style="border-bottom: 1px solid black; width: 60%; margin: 0 auto;"></div>
                    <p style="margin-top: 5px;">Authorized Signature</p>
                </div>
            </td>
        </tr>
    </table>
@endsection