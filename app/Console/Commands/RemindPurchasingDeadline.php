<?php

namespace App\Console\Commands;

use App\Models\Rfq;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RemindPurchasingDeadline extends Command
{
    protected $signature = 'remind:purchasing-deadline';
    protected $description = 'Kirim reminder deadline RFQ ke role Purchasing';

    public function handle()
    {
        // Cari RFQ yang deadline-nya besok (H-1) atau hari ini (Hari H)
        $rfqs = Rfq::whereIn('deadline', [
            Carbon::today()->toDateString(),
            Carbon::tomorrow()->toDateString()
        ])
        ->where('status', '!=', 'Selesai') // Pastikan hanya yang belum selesai
        ->get();

        if ($rfqs->isEmpty()) return;

        // Ambil semua user dengan role 'Purchasing'
        // Asumsi menggunakan Spatie Permission atau cek kolom role
        $purchasingUsers = User::role('Purchasing')->get(); 

        foreach ($rfqs as $rfq) {
            $statusHari = $rfq->deadline == Carbon::today()->toDateString() ? 'HARI INI' : 'BESOK';
            
            Notification::make()
                ->title('Reminder Deadline RFQ')
                ->body("RFQ #{$rfq->number} akan jatuh tempo {$statusHari}!")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(fn() => "/admin/rfqs/{$rfq->id}/edit")
                ])
                ->sendToDatabase($purchasingUsers);
        }

        $this->info('Reminder berhasil dikirim ke role Purchasing.');
    }
}