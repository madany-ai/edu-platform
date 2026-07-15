<?php

namespace App\Filament\Resources\QA;

use App\Filament\Resources\QA\Pages\ManageQA;
use App\Models\QuestionsPost;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QAResource extends Resource
{
    protected static ?string $model = QuestionsPost::class;

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'الأسئلة والاستفسارات';

    protected static ?string $pluralLabel = 'الأسئلة';

    protected static ?string $modelLabel = 'سؤال';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lecture.title')
                    ->label('المحاضرة')
                    ->searchable(),

                TextColumn::make('body')
                    ->label('السؤال')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('replies_count')
                    ->label('الردود')
                    ->counts('replies'),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('15s')
            ->recordActions([
                Action::make('reply')
                    ->label('رد')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->modalHeading('الرد على السؤال')
                    ->form([
                        Textarea::make('body')
                            ->label('الرد')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (array $data, QuestionsPost $record): void {
                        $record->replies()->create([
                            'user_id' => auth()->id(),
                            'body' => $data['body'],
                        ]);

                        Notification::make()
                            ->title('تم إضافة الرد')
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('السؤال والردود')
                    ->modalContent(function (QuestionsPost $record): \Illuminate\Support\HtmlString {
                        $html = '<div class="space-y-4">';
                        $html .= '<div class="p-4 bg-gray-50 rounded-lg"><strong>السؤال:</strong><br>' . e($record->body) . '</div>';
                        foreach ($record->replies as $reply) {
                            $html .= '<div class="p-4 bg-blue-50 rounded-lg mr-8">';
                            $html .= '<strong>' . e($reply->user->name) . ':</strong><br>';
                            $html .= e($reply->body);
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canEdit(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()->hasRole('assistant');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQA::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = request()->user();

        return parent::getEloquentQuery()
            ->with(['student.user', 'lecture', 'replies.user'])
            ->withCount('replies')
            ->when(
                $user && $user->hasRole('instructor') && ! $user->hasRole('super_admin'),
                fn (Builder $query) => $query->whereHas('lecture.section.course', fn (Builder $q) => $q->where('instructor_id', $user->id))
            );
    }
}
