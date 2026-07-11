<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $title = 'الإعدادات';

    protected string $view = 'filament.pages.settings';

    public static function canAccess(): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'instance_name' => config('app.name'),
            'bunny_stream_api_key' => config('services.bunny_stream_api_key'),
            'bunny_stream_library_id' => config('services.bunny_stream_library_id'),
            'paymob_api_key' => config('services.paymob_api_key'),
            'paymob_hmac' => config('services.paymob_hmac'),
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('العلامة التجارية')
                    ->description('اسم المنصة، الشعار، والألوان')
                    ->schema([
                        TextInput::make('instance_name')
                            ->label('اسم المنصة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('مفاتيح API')
                    ->description('بوابة الدفع، الفيديو، والتخزين')
                    ->schema([
                        TextInput::make('bunny_stream_api_key')
                            ->label('Bunny Stream API Key')
                            ->password()
                            ->revealable(),

                        TextInput::make('bunny_stream_library_id')
                            ->label('Bunny Stream Library ID'),

                        TextInput::make('paymob_api_key')
                            ->label('Paymob API Key')
                            ->password()
                            ->revealable(),

                        TextInput::make('paymob_hmac')
                            ->label('Paymob HMAC')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(2),
                Section::make('معلومات الحساب')
                    ->description('تفاصيل حسابك')
                    ->schema([
                        TextInput::make('user_name')
                            ->label('الاسم')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $envFile = base_path('.env');
        $appName = $data['instance_name'] ?? config('app.name');
        if (str_contains($appName, ' ')) {
            $appName = '"' . $appName . '"';
        }

        $replacements = [
            'APP_NAME' => $appName,
            'INSTANCE_NAME' => $appName,
            'BUNNY_STREAM_API_KEY' => $data['bunny_stream_api_key'] ?? '',
            'BUNNY_STREAM_LIBRARY_ID' => $data['bunny_stream_library_id'] ?? '',
            'PAYMOB_API_KEY' => $data['paymob_api_key'] ?? '',
            'PAYMOB_HMAC' => $data['paymob_hmac'] ?? '',
        ];

        $envContent = file_get_contents($envFile);
        foreach ($replacements as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }
        file_put_contents($envFile, $envContent);

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        Notification::make()
            ->title('تم حفظ الإعدادات')
            ->success()
            ->send();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('logout')
                ->label('تسجيل الخروج')
                ->color('danger')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->action(function () {
                    \Filament\Facades\Filament::auth()->logout();
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                    return redirect('/admin/login');
                }),
        ];
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
