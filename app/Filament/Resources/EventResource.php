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
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            ...self::mediaUploads(),
            ...self::eventFields(),
            self::eventDateSection(),
            Toggle::make('coming_soon')
                ->default(false),
            self::eventDetailGroup(),
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

    /**
     * @return array<int, SpatieMediaLibraryFileUpload>
     */
    protected static function mediaUploads(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function eventFields(): array
    {
        return [
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
                ->hidden(fn (Get $get): bool => ! $get('checkable')),

            Toggle::make('hide'),
        ];
    }

    protected static function eventDateSection(): Section
    {
        return Section::make(__('Event Date'))
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
                    ->columnSpan(6)
                    ->required(),
            ]);
    }

    protected static function eventDetailGroup(): Group
    {
        return Group::make()
            ->columnSpanFull()
            ->relationship('detail')
            ->schema([
                Toggle::make('enable_registration')
                    ->default(true),
                self::eventScheduleGrid(),
                self::eventDetailOverrideSection(),
            ]);
    }

    protected static function eventScheduleGrid(): Grid
    {
        return Grid::make(12)
            ->schema([
                self::onlineEventDetailSection(),
                self::offlineEventDetailSection(),
            ]);
    }

    protected static function onlineEventDetailSection(): Section
    {
        return Section::make('Online Event Detail')
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
            ]);
    }

    protected static function offlineEventDetailSection(): Section
    {
        return Section::make('Offline Event Detail')
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

                Toggle::make('show_invoice_upload')
                    ->live(),

                Select::make('excluded_payment_list')
                    ->multiple()
                    ->options(VisitorType::class)
                    ->hidden(fn (Get $get): bool => ! $get('show_invoice_upload'))
                    ->hintActions(self::visitorTypeBulkActions()),

                self::registrationPaymentPricesRepeater(),

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
                    ->mask(self::idrMoneyMask())
                    ->inputMode('numeric')
                    ->extraInputAttributes(self::idrInputAttributes(), true)
                    ->stripCharacters('.')
                    ->dehydrateStateUsing(fn (mixed $state): ?string => self::normalizeIdrAmount($state))
                    ->formatStateUsing(fn (mixed $state): ?string => self::formatIdrAmountForAdmin($state))
                    ->prefix('IDR')
                    ->columnSpanFull(),

                Toggle::make('food_required')
                    ->default(false),

                Select::make('food_type')
                    ->options(FoodType::class)
                    ->default(FoodType::BUFFET)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('offline_foods', null))
                    ->selectablePlaceholder(false),

                self::offlineFoodsRepeater(),
            ]);
    }

    protected static function offlineFoodsRepeater(): Repeater
    {
        return Repeater::make('offline_foods')
            ->label(__('Foods Items'))
            ->maxItems(fn (Get $get): int => self::maxFoodItems($get))
            ->collapsible()
            ->schema(fn (Get $get): array => self::foodItemSchema($get));
    }

    protected static function registrationPaymentPricesRepeater(): Repeater
    {
        return Repeater::make('registration_payment_prices')
            ->label(__('Registration Payment Prices'))
            ->helperText('Optional registration fee list shown before payment proof upload. Food prices are configured separately in Foods Items.')
            ->hidden(fn (Get $get): bool => ! $get('show_invoice_upload'))
            ->collapsible()
            ->columns(2)
            ->schema([
                Select::make('visitor_type')
                    ->label(__('Visitor Type'))
                    ->options(VisitorType::class)
                    ->required(),

                self::idrPriceInput('price', __('Registration Price'))
                    ->helperText('Use numbers for IDR formatting, e.g. 150000.')
                    ->required(),

                TextInput::make('label')
                    ->label(__('Display Label'))
                    ->helperText('Optional label shown under the total payment, e.g. MAGNITUDE package.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    protected static function foodItemSchema(Get $get): array
    {
        return match (self::foodType($get)) {
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
                    ->options(VisitorType::class)
                    ->helperText('Controls which fixed food package is stored for each visitor type.')
                    ->required(),
                TextInput::make('food')
                    ->label(__('Food')),
                TextInput::make('drink')
                    ->label(__('Drinks')),
                self::idrPriceInput('price', __('Food Price'))
                    ->helperText('Shown as a separate food cost line in the payment detail.'),
                TextInput::make('custom')
                    ->label(__('Custom field')),
            ],

            default => [],
        };
    }

    protected static function eventDetailOverrideSection(): Section
    {
        return Section::make('Event Detail Override')
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
                    ->options(VisitorType::class)
                    ->hidden(fn (Get $get): bool => ! $get('override_online_visitor_type'))
                    ->required(fn (Get $get): bool => $get('override_online_visitor_type'))
                    ->hintActions(self::visitorTypeBulkActions()),

                Toggle::make('override_offline_visitor_type')
                    ->live(),

                Select::make('offline_visitor_type_list')
                    ->multiple()
                    ->options(VisitorType::class)
                    ->hidden(fn (Get $get): bool => ! $get('override_offline_visitor_type'))
                    ->required(fn (Get $get): bool => $get('override_offline_visitor_type'))
                    ->hintActions(self::visitorTypeBulkActions()),

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
            ]);
    }

    /**
     * @return array<int, callable>
     */
    protected static function visitorTypeBulkActions(): array
    {
        return [
            fn (Select $component) => Action::make('select all')
                ->action(
                    fn () => $component->state(array_column(VisitorType::cases(), 'value'))
                ),
            fn (Select $component) => Action::make('deselect all')
                ->action(
                    fn () => $component->state([])
                ),
        ];
    }

    protected static function idrPriceInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->mask(self::idrMoneyMask())
            ->inputMode('numeric')
            ->extraInputAttributes(self::idrInputAttributes(), true)
            ->stripCharacters('.')
            ->dehydrateStateUsing(fn (mixed $state): ?string => self::normalizeIdrAmount($state))
            ->formatStateUsing(fn (mixed $state): ?string => self::formatIdrAmountForAdmin($state))
            ->prefix('IDR');
    }

    protected static function idrMoneyMask(): RawJs
    {
        return RawJs::make('$money($input, ",", ".", 0)');
    }

    /**
     * @return array<string, string>
     */
    protected static function idrInputAttributes(): array
    {
        return [
            'x-on:keydown' => 'if ([".", ",", "e", "E", "+", "-"].includes($event.key)) $event.preventDefault()',
            'x-on:input' => <<<'JS'
                const digits = $event.target.value.replace(/\D/g, '');
                $event.target.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            JS,
            'x-on:paste' => <<<'JS'
                $event.preventDefault();
                const pastedDigits = ($event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                document.execCommand('insertText', false, pastedDigits);
            JS,
        ];
    }

    protected static function normalizeIdrAmount(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $state);

        return $digits === '' ? null : $digits;
    }

    protected static function formatIdrAmountForAdmin(mixed $state): ?string
    {
        $amount = self::normalizeIdrAmount($state);

        return $amount === null ? null : number_format((int) $amount, 0, ',', '.');
    }

    protected static function maxFoodItems(Get $get): int
    {
        return self::foodType($get) === FoodType::ALA_CARTE ? 1 : 999;
    }

    protected static function foodType(Get $get): ?FoodType
    {
        $foodType = $get('food_type');

        return is_string($foodType) ? FoodType::tryFrom($foodType) : $foodType;
    }

    protected static function startDateSelected(Set $set, ?string $date): void
    {
        $date = Carbon::parse($date);
        $registrationDate = $date->subDays(1)->format('Y-m-d');

        $set('registration_date', $registrationDate);
        self::registrationDateChanged($set, $registrationDate);
    }

    protected static function registrationDateChanged(Set $set, string $date): void
    {
        $date = Carbon::parse($date);

        $set('registration_end', $date->addDays(1)->format('Y-m-d H:i'));
    }
}
