<?php

namespace App\Http\Controllers;

use App\Models\TemporaryCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerRegistrationController extends Controller
{
    // Menampilkan halaman form
    public function create()
    {
        return view('customer-registration');
    }

    // Memproses data form
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'business_type'  => 'required|string|max:255',
            'address'        => 'required|string',
            'district'       => 'nullable|string|max:255',
            'city'           => 'required|string|max:255',
            'province'       => 'required|string|max:255',
            'company_phone'  => 'required|string|max:255',
            
            'pic_name'       => 'required|string|max:255',
            'pic_position'   => 'required|string|max:255',
            'pic_phone'      => 'required|string|max:255',
            
            'business_scope' => 'required|string|max:255',
            'payment_terms'  => 'required|string|max:255',
            
            // Validasi file (maksimal 5 file, per file maks 5MB)
            'documents'      => 'nullable|array|max:5',
            'documents.*'    => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // 2. Proses Upload File Pendukung
        $documentPaths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                // Simpan file ke folder storage/app/public/customer_documents
                $path = $file->store('customer_documents', 'public');
                $documentPaths[] = $path;
            }
        }

        // 3. Simpan ke Database
        TemporaryCustomer::create([
            'company_name'   => $validated['company_name'],
            'email'          => $validated['email'],
            'business_type'  => $validated['business_type'],
            'address'        => $validated['address'],
            'district'       => $validated['district'],
            'city'           => $validated['city'],
            'province'       => $validated['province'],
            'company_phone'  => $validated['company_phone'],
            
            'pic_name'       => $validated['pic_name'],
            'pic_position'   => $validated['pic_position'],
            'pic_phone'      => $validated['pic_phone'],
            
            'business_scope' => $validated['business_scope'],
            'payment_terms'  => $validated['payment_terms'],
            
            'supporting_documents' => $documentPaths, // Disimpan sebagai array JSON
            'status'         => 'pending', // Status default
        ]);

        // 4. Redirect dengan pesan sukses
        return redirect()->back()->with('success', 'Terima kasih! Pendaftaran perusahaan Anda berhasil dikirim dan sedang dalam proses peninjauan oleh tim kami.');
    }
}