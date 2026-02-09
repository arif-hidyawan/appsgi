<div class="p-4 flex flex-col items-center">
    <h3 class="text-lg font-bold mb-4">Bukti Kirim</h3>
    
    @if(isset($imageUrl))
        <img src="{{ $imageUrl }}" alt="Bukti Pengiriman" style="max-width: 100%; height: auto; border-radius: 0.5rem;">
    @else
        <p>Gambar tidak ditemukan.</p>
    @endif
</div>