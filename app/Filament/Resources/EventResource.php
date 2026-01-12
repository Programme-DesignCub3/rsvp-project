<?php

namespace App\Filament\Resources;

use App\Enums\FoodType;
use App\Enums\VisitorType;
use App\Filament\Component\EventTable;
use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\Pages\ManageVisitor;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                SpatieMediaLibraryFileUpload::make('banner')
                    ->collection('banner')
                    ->imageEditor()
                    ->imageCropAspectRatio('2.56:1')
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('thumbnail')
                    ->collection('thumbnail')
                    ->imageEditor()
                    ->imageCropAspectRatio('2.56:1')
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->label(__('Name')),

                CheckboxList::make('session')
                    ->options([
                        'offline' => 'Offline',
                        'online' => 'Online',
                    ])
                    ->required(),

                Toggle::make('checkable')
                    ->live(),
                Toggle::make('checkable_one')
                    ->hidden(fn (Get $get) => ! $get('checkable')),

                Toggle::make('hide'),

                Section::make(__('Event Date'))
                    ->description('Please fill in the event date.')
                    ->columns(12)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->timezone('Asia/Jakarta')
                            ->columnSpan(6)
                            ->live()
                            ->afterStateUpdated(
                                fn (Set $set, ?string $state) => self::startDateSelected($set, $state)
                            )
                            ->required(),
                        DatePicker::make('registration_date')
                            ->label(__('Registration Date'))
                            ->timezone('Asia/Jakarta')
                            ->columnSpan(6)
                            ->required(),

                        DateTimePicker::make('registration_end')
                            ->label(__('Registration End date'))
                            ->timezone('Asia/Jakarta')
                            ->seconds(false)
                    // ->native(false)
                            ->columnSpan(6)
                            ->required(),
                    ]),
                Toggle::make('coming_soon')
                    ->default(false),

                Group::make()
                    ->columnSpanFull()
                    ->relationship(
                        'detail'
                    )
                    ->schema(
                        [
                            Toggle::make('enable_registration')
                                ->default(true),
                            Grid::make(12)
                                ->schema([
                                    Section::make('Online Event Detail')
                                        ->columnSpan(6)
                                        ->schema([
                                            DateTimePicker::make('online_time')
                                                ->timezone('Asia/Jakarta')
                                                ->displayFormat('H:i')
                                                ->default('09:00')
                                                ->date(false)
                                                ->seconds(false)
                                                ->columnSpanFull(),
                                            TextInput::make('online_link')
                                                ->label(__('Online Link'))
                                                ->required()
                                                ->columnSpanFull(),
                                            TextInput::make('online_password')
                                                ->label(__('Online Password'))
                                                ->required()
                                                ->columnSpanFull(),
                                        ]),

                                    Section::make('Offline Event Detail')
                                        ->columnSpan(6)
                                        ->schema([
                                            DateTimePicker::make('offline_time')
                                                ->label(__('Offline Time'))
                                                ->timezone('Asia/Jakarta')
                                                ->displayFormat('H:i')
                                                ->default('14:00')
                                                ->date(false)
                                                ->seconds(false)
                                                ->columnSpanFull(),
                                            RichEditor::make('offline_address')
                                                ->label(__('Offline Address'))
                                                ->required()
                                                ->columnSpanFull(),
                                            TextInput::make('offline_location')
                                                ->label(__('Offline Location URL'))
                                                ->required()
                                                ->url()
                                                ->columnSpanFull(),

                                            Toggle::make('show_invoice_upload'),

                                            Toggle::make('override_offline_food_price_text')
                                                ->live(),

                                            RichEditor::make('offline_food_price_text')
                                                ->label(__('Offline Food Price Text'))
                                                ->hidden(fn (Get $get): bool => ! $get('override_offline_food_price_text'))
                                                ->required(fn (Get $get): bool => $get('override_offline_food_price_text')),

                                            TextInput::make('offline_food_price')
                                                ->label(__('Offline Food Price'))
                                                ->hidden(fn (Get $get): bool => $get('override_offline_food_price_text'))
                                                ->required(fn (Get $get): bool => ! $get('override_offline_food_price_text'))
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->columnSpanFull(),

                                            Toggle::make('food_required')
                                                ->default(false),

                                            Select::make('food_type')
                                                ->options(FoodType::class)
                                                ->default(FoodType::BUFFET)
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set) => $set('offline_foods', null))
                                                ->selectablePlaceholder(false),

                                            Repeater::make('offline_foods')
                                                ->label(__('Foods Items'))
                                                // ? This is not really an elegant solution, but it works for now.
                                                ->maxItems(fn (Get $get) => in_array((is_string($get('food_type')) ? FoodType::tryFrom($get('food_type')) : $get('food_type')), [FoodType::ALA_CARTE], true) ? 1 : 999)
                                                ->collapsible()
                                                ->schema(
                                                    fn (Get $get): array => match (is_string($get('food_type')) ? FoodType::tryFrom($get('food_type')) : $get('food_type')) {
                                                        FoodType::BUFFET => [
                                                            TextInput::make('food')
                                                                ->label(__('Food'))
                                                                ->required()
                                                                ->columnSpan(6),
                                                        ],

                                                        FoodType::ALA_CARTE => [
                                                            Repeater::make('food')
                                                                ->simple(
                                                                    TextInput::make('food')
                                                                        ->label(__('Food'))
                                                                        ->required()
                                                                ),
                                                            Repeater::make('drink')
                                                                ->simple(
                                                                    TextInput::make('drink')
                                                                        ->label(__('Drinks'))
                                                                        ->required()
                                                                ),
                                                        ],

                                                        FoodType::FIXED => [
                                                            Select::make('visitor_type')
                                                                ->options(
                                                                    VisitorType::class
                                                                )->required(),
                                                            TextInput::make('food')
                                                                ->label(__('Food')),
                                                            TextInput::make('drink')
                                                                ->label(__('Drinks')),
                                                            TextInput::make('price')
                                                                ->label(__('Price')),
                                                            TextInput::make('custom')
                                                                ->label(__('Custom field')),
                                                        ],
                                                        default => [],
                                                    }
                                                ),
                                        ]),
                                ]),
                            Section::make('Event Detail Override')
                                ->schema([
                                    Toggle::make('override_deadline_text')
                                        ->live(),
                                    RichEditor::make('deadline_text')
                                        ->hidden(fn (Get $get): bool => ! $get('override_deadline_text'))
                                        ->required(fn (Get $get): bool => $get('override_deadline_text'))
                                        ->columnSpanFull(),
                                    Select::make('event_type')
                                        ->options([
                                            'soft launch' => 'SOFT LAUNCH',
                                            'grand launch' => 'GRAND LAUNCH',
                                        ])
                                        ->default('soft launch'),

                                    Toggle::make('override_online_visitor_type')
                                        ->live(),
                                    Select::make('online_visitor_type_list')
                                        ->multiple()
                                        ->options(
                                            VisitorType::class
                                        )
                                        ->hidden(fn (Get $get): bool => ! $get('override_online_visitor_type'))
                                        ->required(fn (Get $get): bool => $get('override_online_visitor_type'))
                                        ->hintActions(
                                            [
                                                fn (Select $component) => Action::make('select all')
                                                    ->action(
                                                        fn () => $component->state(array_column(VisitorType::cases(), 'value'))
                                                    ),
                                                fn (Select $component) => Action::make('deselect all')
                                                    ->action(
                                                        fn () => $component->state([])
                                                    ),
                                            ]
                                        ),

                                    Toggle::make('override_offline_visitor_type')
                                        ->live(),
                                    Select::make('offline_visitor_type_list')
                                        ->multiple()
                                        ->options(
                                            VisitorType::class
                                        )
                                        ->hidden(fn (Get $get): bool => ! $get('override_offline_visitor_type'))
                                        ->required(fn (Get $get): bool => $get('override_offline_visitor_type'))
                                        ->hintActions(
                                            [
                                                fn (Select $component) => Action::make('select all')
                                                    ->action(
                                                        fn () => $component->state(array_column(VisitorType::cases(), 'value'))
                                                    ),

                                                fn (Select $component) => Action::make('deselect all')
                                                    ->action(
                                                        fn () => $component->state([])
                                                    ),
                                            ]
                                        ),

                                    Toggle::make('override_what_to_prepare')
                                        ->live(),
                                    RichEditor::make('what_to_prepare')
                                        ->hidden(fn (Get $get): bool => ! $get('override_what_to_prepare'))
                                        ->required(fn (Get $get): bool => $get('override_what_to_prepare'))
                                        ->columnSpanFull(),

                                    Toggle::make('override_description_1')
                                        ->live(),
                                    RichEditor::make('description_1')
                                        ->hidden(fn (Get $get): bool => ! $get('override_description_1'))
                                        ->required(fn (Get $get): bool => $get('override_description_1'))
                                        ->columnSpanFull(),

                                    Toggle::make('override_description_2')
                                        ->live(),
                                    RichEditor::make('description_2')
                                        ->hidden(fn (Get $get): bool => ! $get('override_description_2'))
                                        ->required(fn (Get $get): bool => $get('override_description_2'))
                                        ->columnSpanFull(),

                                    Toggle::make('override_video')
                                        ->live(),
                                    SpatieMediaLibraryFileUpload::make('video')
                                        ->hidden(fn (Get $get): bool => ! $get('override_video'))
                                        ->required(fn (Get $get): bool => $get('override_video'))
                                        ->collection('video')
                                        ->acceptedFileTypes(['video/*']),
                                ]),
                        ],

                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(EventTable::Event())
            ->defaultSort('start_date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('visitors')
                    ->label('Manage Visitors')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Event $record) => ManageVisitor::getUrl(['record' => $record->id])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'visitors' => Pages\ManageVisitor::route('/{record}/visitors'),
        ];
    }

    protected static function startDateSelected(Set $set, $date)
    {
        $date = Carbon::parse($date);
        $reg_date = $date->subDays(1)->format('Y-m-d');

        $set('registration_date', $reg_date);
        self::registrationDateChanged($set, $reg_date);
    }

    protected static function registrationDateChanged(Set $set, $date)
    {
        $date = Carbon::parse($date);

        $set('registration_end', $date->addDays(1)->format('Y-m-d H:i'));
    }
}
