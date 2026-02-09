@extends('pdf.layout')

@section('content')
    <div class="title">INVOICE (TAGIHAN)</div>

    <table class="details-table">
        <tr>
            <td width="15%"><strong>Ditagihkan ke:</strong></td>
            <td width="40%">{{ $record->customer->name }}<br>{{ $record->customer->address }}</td>
            <td width="15%"><strong>No. Invoice:</strong></td>
            <td>{{ $record->invoice_number }}</td>
        </tr>
        <tr>
            <td><strong>Ref PO Cust:</strong></td>
            <td>{{ $record->salesOrder->customer_po_number ?? '-' }}</td>
            <td><strong>Tgl Invoice:</strong></td>
            <td>{{ $record->date->format('d F Y') }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td><strong>Jatuh Tempo:</strong></td>
            <td style="color: red;">{{ $record->due_date->format('d F Y') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Deskripsi Produk</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="20%" class="text-right">Harga</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL TAGIHAN</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($record->grand_total, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 20px;">
        <strong>Instruksi Pembayaran:</strong><br>
        BCA: 123-456-7890 (PT SAPUTRA GROUP INDONESIA)<br>
        Mandiri: 987-654-3210
    </div>

    <div class="signature">
        <div class="sign-box">
            Finance,<br><br><br>
            <div class="sign-line">Stempel & Ttd</div>
        </div>
    </div>
@endsection