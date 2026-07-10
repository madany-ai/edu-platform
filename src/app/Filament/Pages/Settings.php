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

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $title = 'الإعدادات';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'instance_name' => config('app.name'),
            'instance_logo' => config('app.instance_logo'),
            'instance_favicon' => config('app.instance_favicon'),
            'instance_primary_color' => config('app.instance_primary_color', '#2563eb'),
            'bunny_stream_api_key' => config('services.bunny_stream_api_key'),
            'bunny_stream_library_id' => config('services.bunny_stream_library_id'),
            'paymob_api_key' => config('services.paymob_api_key'),
            'paymob_hmac' => config('services.paymob_hmac'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('العلامة التجارية')
                    ->description('اسم المنصة، الشعار، والألوان')
                    ->schema([
                        TextInput::make('instance_name')
                            ->label('اسم المنصة')
                            ->required()
                            ->maxLength(255),

                        FileUpload::make('instance_logo')
                            ->label('الشعار')
                            ->image()
                            ->directory('branding'),

                        FileUpload::make('instance_favicon')
                            ->label('أيقونة الموقع')
                            ->image()
                            ->directory('branding'),

                        ColorPicker::make('instance_primary_color')
                            ->label('اللون الأساسي'),
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $envFile = base_path('.env');
        $replacements = [
            'INSTANCE_NAME' => $data['instance_name'] ?? '',
            'INSTANCE_LOGO' => $data['instance_logo'] ?? '',
            'INSTANCE_FAVICON' => $data['instance_favicon'] ?? '',
            'INSTANCE_PRIMARY_COLOR' => $data['instance_primary_color'] ?? '#2563eb',
            'BUNNY_STREAM_API_KEY' => $data['bunny_stream_api_key'] ?? '',
            'BUNNY_STREAM_LIBRARY_ID' => $data['bunny_stream_library_id'] ?? '',
            'PAYMOB_API_KEY' => $data['paymob_api_key'] ?? '',
            'PAYMOB_HMAC' => $data['paymob_hmac'] ?? '',
        ];

        $envContent = file_get_contents($envFile);
        foreach ($replacements as $key => $value) {
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        }
        file_put_contents($envFile, $envContent);

        Notification::make()
            ->title('تم حفظ الإعدادات')
            ->success()
            ->send();
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
