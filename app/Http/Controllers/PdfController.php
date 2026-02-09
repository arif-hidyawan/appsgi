<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\GoodsReceive;

class PdfController extends Controller
{
    // 1. Cetak Quotation
    public function quotation(Quotation $record)
    {
        $pdf = Pdf::loadView('pdf.quotation', ['record' => $record]);
        return $pdf->stream('Quotation-' . $record->quotation_number . '.pdf');
    }

    // 2. Cetak Sales Order (Internal)
    public function salesOrder(SalesOrder $record)
    {
        $pdf = Pdf::loadView('pdf.sales_order', ['record' => $record]);
        return $pdf->stream('SO-' . $record->so_number . '.pdf');
    }

    // 3. Cetak Surat Jalan (Tanpa Harga)
    public function deliveryOrder(DeliveryOrder $record)
    {
        $pdf = Pdf::loadView('pdf.delivery_order', ['record' => $record]);
        return $pdf->stream('DO-' . $record->do_number . '.pdf');
    }

    // 4. Cetak Invoice Customer
    public function salesInvoice(SalesInvoice $record)
    {
        $pdf = Pdf::loadView('pdf.sales_invoice', ['record' => $record]);
        return $pdf->stream('INV-' . $record->invoice_number . '.pdf');
    }

    // 5. Cetak PO ke Vendor
    public function purchaseOrder(PurchaseOrder $record)
    {
        $pdf = Pdf::loadView('pdf.purchase_order', ['record' => $record]);
        return $pdf->stream('PO-' . $record->po_number . '.pdf');
    }

    // 6. Cetak BAST (Berita Acara Serah Terima) - BARU
    public function deliveryOrderBast(DeliveryOrder $record)
    {
        // Pastikan file view 'resources/views/pdf/bast.blade.php' sudah dibuat
        $pdf = Pdf::loadView('pdf.bast', ['record' => $record]);
        
        // Set ukuran kertas A4 Portrait (Opsional, tapi disarankan agar rapi)
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('BAST-' . $record->do_number . '.pdf');
    }

    // 7. Cetak Bukti Penerimaan Barang (GR)
    public function goodsReceive(GoodsReceive $record)
    {
        $pdf = Pdf::loadView('pdf.goods_receive', ['record' => $record]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('GR-' . $record->gr_number . '.pdf');
    }
}