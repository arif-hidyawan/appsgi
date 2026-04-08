<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\DeliveryOrderController;
use App\Models\SalesPayment;

use App\Http\Controllers\CustomerRegistrationController;

Route::get('/', fn () => redirect()->away('https://app.saputragroupindo.com/admin'));



Route::get('/print/quotation/{record}', [PdfController::class, 'quotation'])->name('print.quotation');
Route::get('/print/so/{record}', [PdfController::class, 'salesOrder'])->name('print.so');
Route::get('/print/do/{record}', [PdfController::class, 'deliveryOrder'])->name('print.do');
Route::get('/print/invoice/{record}', [PdfController::class, 'salesInvoice'])->name('print.invoice');
Route::get('/print/po/{record}', [PdfController::class, 'purchaseOrder'])->name('print.po');
Route::get('/goods-receives/{record}/print', [PdfController::class, 'goodsReceive'])->name('print.gr');

Route::get('/delivery-orders/{record}/print-bast', [PdfController::class, 'deliveryOrderBast'])
    ->name('print.bast');

Route::get('/print/payment/{record}', function (SalesPayment $record) {
        // Pastikan user punya akses ke company record ini (Security Check)
        $userCompanyIds = auth()->user()->companies()->pluck('companies.id')->toArray();
        
        if (!in_array($record->company_id, $userCompanyIds)) {
            abort(403, 'Unauthorized');
        }
    
        // Tampilkan View PDF
        // Pastikan Bapak sudah membuat view 'pdf.payment' atau sesuaikan namanya
        return view('pdf.payment', ['record' => $record]);
        
    })->name('print.payment')->middleware('auth');

Route::get('/register-customer', [CustomerRegistrationController::class, 'create'])->name('customer.register.form');
Route::post('/register-customer', [CustomerRegistrationController::class, 'store'])->name('customer.register.submit');