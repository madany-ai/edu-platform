<?php

namespace App\Filament\Pages;

use App\Models\GradeLevel;
use App\Models\Group;
use App\Services\RankingService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RankingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationLabel = 'ترتيب الطلاب والأوائل';

    protected static ?string $title = 'ترتيب الطلاب والأوائل بالسنتر';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة السنتر الأوفلاين';

    protected string $view = 'filament.pages.rankings-page';

    public ?string $selectedGroup = null;
    public ?string $selectedGrade = null;
    public array $rankings = [];

    public function mount(): void
    {
        $firstGroup = Group::where('is_active', true)->first();
        if ($firstGroup) {
            $this->selectedGroup = $firstGroup->id;
            $this->loadRankings();
        }
    }

    public function updatedSelectedGroup(): void
    {
        $this->selectedGrade = null;
        $this->loadRankings();
    }

    public function updatedSelectedGrade(): void
    {
        $this->selectedGroup = null;
        $this->loadRankings();
    }

    public function loadRankings(): void
    {
        if ($this->selectedGroup) {
            $this->rankings = RankingService::getGroupRankings($this->selectedGroup)->toArray();
        } elseif ($this->selectedGrade) {
            $this->rankings = RankingService::getGradeRankings($this->selectedGrade)->toArray();
        } else {
            $this->rankings = [];
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('selectedGroup')
                    ->label('ترتيب حسب المجموعة الدراسية')
                    ->options(Group::where('is_active', true)->pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadRankings()),

                Select::make('selectedGrade')
                    ->label('أو ترتيب حسب الصف الدراسي الكامل')
                    ->options(GradeLevel::pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadRankings()),
            ]);
    }
}
