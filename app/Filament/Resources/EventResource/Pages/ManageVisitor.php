<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Enums\VisitorType;
use App\Filament\Component\EventTable;
use App\Filament\Exports\VisitorExporter;
use App\Filament\Resources\EventResource;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageVisitor extends ManageRelatedRecords
{
    protected static string $resource = EventResource::class;

    protected static string $relationship = 'visitors';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected $userType = [];

    public static function getNavigationLabel(): string
    {
        return 'Visitor';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('business'),
                Forms\Components\TextInput::make('company'),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('invited_by')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Checkbox::make('is_online'),
                Forms\Components\Checkbox::make('is_offline'),
                Forms\Components\TextInput::make('food')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('payment'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(EventTable::ManageVisitor())
            ->filters([
                SelectFilter::make('Type')
                    ->multiple()
                    ->options(VisitorType::class),
                TernaryFilter::make('payment_verified')
                    ->label('Payment Verified'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                ExportAction::make()
                    ->exporter(VisitorExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                    ])
                    ->fileName(fn (Export $export): string => substr("{$this->record->slug}", 0, 25)." visitor - {$export->getKey()}")
                    ->modifyQueryUsing(fn (Builder $query, array $options) => $query->when(! empty($options['visitorType']), function (Builder $query) use ($options) {
                        return $query->whereIn('type', $options['visitorType']);
                    }))
                    ->columnMapping(false),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                EventTable::paymentVerificationAction(),
                EventTable::resendPaymentEmailAction(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Are you sure you want to delete this visitor? This action cannot be undone, and all data will be lost (including payment proof).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
