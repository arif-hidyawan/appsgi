<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;

class Backups extends Page
{
    protected static ?int $navigationSort = 95;
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Backup Database';
    protected static ?string $title = 'Backup Database';
    protected static string $view = 'filament.pages.backups';

    public $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()->can('backup.view');
    }

    protected function getViewData(): array
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name'); // 'app-sgi'
        $disk = Storage::disk($diskName);

        // Ambil list backup dari Spatie
        $backupDestinations = BackupDestination::create($diskName, $backupName);
        
        // --- PERBAIKAN UTAMA: Ubah Object jadi Array ---
        // Kita hitung size manual pakai $disk->size() agar tidak error method not found
        $backups = $backupDestinations->backups()
            ->sortByDesc(fn ($backup) => $backup->date())
            ->map(function ($backup) use ($disk) {
                return [
                    'path' => $backup->path(),
                    'name' => basename($backup->path()),
                    'date' => $backup->date(),
                    'size' => $disk->exists($backup->path()) ? $disk->size($backup->path()) : 0, // Manual Size Check
                ];
            });

        // Filter Search
        if ($this->search) {
            $backups = $backups->filter(function ($item) {
                return str_contains(strtolower($item['name']), strtolower($this->search));
            });
        }

        return [
            'backups' => $backups,
        ];
    }

    public function download(string $path)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        if ($disk->exists($path)) {
            return $disk->download($path);
        }
        
        // Fallback untuk Laravel 11 (private folder)
        $privatePath = 'private/' . $path;
        if ($disk->exists($privatePath)) {
            return $disk->download($privatePath);
        }

        Notification::make()->title('Gagal')->body("File tidak ditemukan.")->danger()->send();
    }

    public function delete(string $path)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        if ($disk->exists($path)) $disk->delete($path);
        
        Notification::make()->title('File berhasil dihapus')->success()->send();
    }

    public function createBackupAction(): Action
    {
        return Action::make('createBackup')
            ->label('Buat Backup Baru')
            ->button()
            ->action(function () {
                Artisan::call('backup:run --only-db --disable-notifications');
                Notification::make()->title('Backup Selesai')->success()->send();
                $this->redirect(static::getUrl());
            });
    }
}