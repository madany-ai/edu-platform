<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupManager extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';
    
    protected static string | \UnitEnum | null $navigationGroup = 'الإعدادات';
    
    protected static ?string $navigationLabel = 'النسخ الاحتياطية';

    protected static ?string $title = 'إدارة النسخ الاحتياطية (Backups)';

    protected string $view = 'filament.pages.backup-manager';

    public $backups = [];

    public function mount()
    {
        $this->loadBackups();
    }

    public function loadBackups()
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $name = config('backup.backup.name');
        
        $files = Storage::disk($disk)->files($name);
        
        $this->backups = collect($files)
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->map(function ($file) use ($disk) {
                return [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => round(Storage::disk($disk)->size($file) / 1048576, 2) . ' MB',
                    'date' => date('Y-m-d H:i:s', Storage::disk($disk)->lastModified($file))
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    public function createBackup()
    {
        try {
            $exitCode = Artisan::call('backup:run', ['--only-db' => true]);
            
            if ($exitCode === 0) {
                Notification::make()
                    ->title('تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('حدث خطأ أثناء إنشاء النسخة الاحتياطية')
                    ->body('Exit code: ' . $exitCode)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث استثناء أثناء إنشاء النسخة الاحتياطية')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
            
        $this->loadBackups();
    }

    public function downloadBackup($path)
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        return Storage::disk($disk)->download($path);
    }
    
    public function deleteBackup($path)
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        Storage::disk($disk)->delete($path);
        
        Notification::make()
            ->title('تم حذف النسخة بنجاح')
            ->success()
            ->send();
            
        $this->loadBackups();
    }
}
