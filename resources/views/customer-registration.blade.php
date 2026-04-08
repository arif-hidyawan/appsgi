<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Registrasi Customer Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-10">

<div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">FORMULIR REGISTRASI CUSTOMER BARU</h2>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('customer.register.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Informasi Perusahaan</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Perusahaan *</label>
                <input type="text" name="company_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Perusahaan *</label>
                <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Usaha *</label>
                <select name="business_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
                    <option value="">Pilih Jenis Usaha...</option>
                    <option value="Perdagangan">Perdagangan</option>
                    <option value="Industri">Industri</option>
                    <option value="Pemerintahan">Pemerintahan</option>
                    <option value="Pendidikan">Pendidikan</option>
                    <option value="Rumah Sakit">Rumah Sakit</option>
                    <option value="BUMN">BUMN</option>
                    <option value="Pribadi">Pribadi</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">No Telp Perusahaan *</label>
                <input type="text" name="company_phone" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Alamat Lengkap Perusahaan *</label>
            <textarea name="address" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Kecamatan</label>
                <input type="text" name="district" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Kota/Kabupaten *</label>
                <input type="text" name="city" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Provinsi *</label>
                <input type="text" name="province" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mt-8">Informasi Contact Person (PIC)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama PIC *</label>
                <input type="text" name="pic_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jabatan *</label>
                <input type="text" name="pic_position" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">No Telp PIC (WA) *</label>
                <input type="text" name="pic_phone" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mt-8">Detail Kerja Sama</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Lingkup Usaha *</label>
                <select name="business_scope" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
                    <option value="">Pilih Lingkup Usaha...</option>
                    <option value="Sub Con">Sub Con</option>
                    <option value="End User">End User</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Rencana Term Pembayaran PO *</label>
                <select name="payment_terms" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
                    <option value="">Pilih Term Pembayaran...</option>
                    <option value="DP/Cash Before Delivery (CBD)">DP/Cash Before Delivery (CBD)</option>
                    <option value="Cash On Delivery (COD)">Cash On Delivery (COD)</option>
                    <option value="7 Hari">7 Hari</option>
                    <option value="14 Hari">14 Hari</option>
                    <option value="30 Hari">30 Hari</option>
                </select>
            </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mt-8">Lampiran</h3>
        <div>
            <label class="block text-sm font-medium text-gray-700">File Pendukung (KTP/NPWP/SIUP/TDP/NIB/AKTE)</label>
            <p class="text-xs text-gray-500 mb-2">Pilih maksimal 5 file (Format: PDF, DOC, JPG, PNG). Gunakan tombol CTRL/CMD untuk memilih banyak file sekaligus.</p>
            <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md p-1">
        </div>

        <div class="pt-6">
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-bold text-lg">
                Kirim Pendaftaran
            </button>
        </div>
    </form>
</div>

</body>
</html>