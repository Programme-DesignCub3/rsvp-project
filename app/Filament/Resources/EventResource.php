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
use Filament\Forms\Components\Tabs;
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
            Tabs::make('Event Configuration')
                ->persistTabInQueryString('event-tab')
                ->columnSpanFull()
                ->tabs([
                    self::overviewTab(),
                    self::eventDetailsTab(),
                ]),
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
                ->label(__('Event Banner'))
                ->helperText('Wide image used as the main event banner. Recommended ratio: 2.56:1.')
                ->collection('banner')
                ->imageEditor()
                ->imageCropAspectRatio('2.56:1'),

            SpatieMediaLibraryFileUpload::make('thumbnail')
                ->label(__('Event Thumbnail'))
                ->helperText('Preview image shown in event lists. Recommended ratio: 2.56:1.')
                ->collection('thumbnail')
                ->imageEditor()
                ->imageCropAspectRatio('2.56:1'),
        ];
    }

    protected static function overviewTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Overview')
            ->icon('heroicon-m-information-circle')
            ->schema([
                Section::make('Media')
                    ->description('Upload the banner and thumbnail used on the public event page.')
                    ->columns(2)
                    ->collapsible()
                    ->schema(self::mediaUploads()),

                Section::make('Event Information')
                    ->description('Name the event and choose the attendance channels available to registrants.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Event Name'))
                            ->columnSpanFull(),

                        CheckboxList::make('session')
                            ->label(__('Available Sessions'))
                            ->helperText('Only selected session types will be offered to registrants.')
                            ->options([
                                'offline' => 'Offline',
                                'online' => 'Online',
                            ])
                            ->columns(2)
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('checkable')
                            ->label(__('Allow Session Selection'))
                            ->helperText('Let registrants choose which available session they will attend.')
                            ->live(),

                        Toggle::make('checkable_one')
                            ->label(__('Limit to One Session'))
                            ->helperText('Registrants may select only one attendance session.')
                            ->hidden(fn (Get $get): bool => ! $get('checkable')),
                    ]),

                self::eventDateSection(),

                Section::make('Visibility')
                    ->description('Control whether the event is visible and ready for registration.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('hide')
                            ->label(__('Hide Event'))
                            ->helperText('Removes this event from the public event list.'),

                        Toggle::make('coming_soon')
                            ->label(__('Coming Soon'))
                            ->helperText('Shows the event as upcoming while keeping registration unavailable.')
                            ->default(false),
                    ]),
            ]);
    }

    protected static function eventDateSection(): Section
    {
        return Section::make(__('Schedule & Registration Window'))
            ->description('Set the event date and when registration opens and closes.')
            ->columns(3)
            ->schema([
                DatePicker::make('start_date')
                    ->label(__('Start Date'))
                    ->helperText('The date when the event takes place.')
                    ->timezone('Asia/Jakarta')
                    ->live()
                    ->afterStateUpdated(
                        fn (Set $set, ?string $state) => self::startDateSelected($set, $state)
                    )
                    ->required(),

                DatePicker::make('registration_date')
                    ->label(__('Registration Opens'))
                    ->helperText('Registrants can access the registration form from this date.')
                    ->timezone('Asia/Jakarta')
                    ->required(),

                DateTimePicker::make('registration_end')
                    ->label(__('Registration Closes'))
                    ->helperText('Registration is rejected after this date and time.')
                    ->timezone('Asia/Jakarta')
                    ->seconds(false)
                    ->required(),
            ]);
    }

    protected static function eventDetailsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Event Details')
            ->icon('heroicon-m-adjustments-horizontal')
            ->schema([
                Group::make()
                    ->columnSpanFull()
                    ->relationship('detail')
                    ->schema([
                        Tabs::make('Event Detail Categories')
                            ->persistTabInQueryString('detail-tab')
                            ->columnSpanFull()
                            ->tabs([
                                self::sessionsTab(),
                                self::registrationTab(),
                                self::foodTab(),
                                self::contentTab(),
                            ]),
                    ]),
            ]);
    }

    protected static function sessionsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Sessions')
            ->icon('heroicon-m-calendar-days')
            ->schema([
                Grid::make(12)
                    ->schema([
                        self::onlineEventDetailSection(),
                        self::offlineVenueSection(),
                    ]),
            ]);
    }

    protected static function onlineEventDetailSection(): Section
    {
        return Section::make('Online Session')
            ->description('Configure the meeting time and access credentials.')
            ->columnSpan(6)
            ->columns(2)
            ->schema([
                DateTimePicker::make('online_time')
                    ->label(__('Start Time'))
                    ->timezone('Asia/Jakarta')
                    ->displayFormat('H:i')
                    ->default('09:00')
                    ->date(false)
                    ->seconds(false)
                    ->columnSpanFull(),

                TextInput::make('online_link')
                    ->label(__('Meeting Link'))
                    ->helperText('Full URL opened by online attendees, including https://.')
                    ->required(),

                TextInput::make('online_password')
                    ->label(__('Meeting Password'))
                    ->helperText('Password displayed to registered online attendees.')
                    ->required(),
            ]);
    }

    protected static function offlineVenueSection(): Section
    {
        return Section::make('Offline Session')
            ->description('Configure the venue, directions, and start time.')
            ->columnSpan(6)
            ->schema([
                DateTimePicker::make('offline_time')
                    ->label(__('Start Time'))
                    ->timezone('Asia/Jakarta')
                    ->displayFormat('H:i')
                    ->default('14:00')
                    ->date(false)
                    ->seconds(false)
                    ->columnSpanFull(),

                RichEditor::make('offline_address')
                    ->label(__('Venue Address'))
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('offline_location')
                    ->label(__('Map URL'))
                    ->helperText('Full Google Maps or other navigation URL for the venue.')
                    ->required()
                    ->url()
                    ->columnSpanFull(),
            ]);
    }

    protected static function registrationTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Registration & Payment')
            ->icon('heroicon-m-credit-card')
            ->schema([
                Section::make('Registration Access')
                    ->description('Control registration availability and visitor types for each session.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enable_registration')
                            ->label(__('Enable Registration'))
                            ->helperText('Turn this off to keep the event visible without accepting registrations.')
                            ->default(true)
                            ->columnSpanFull(),

                        Toggle::make('override_online_visitor_type')
                            ->label(__('Customize Online Visitor Types'))
                            ->helperText('Restrict online registration to selected visitor types.')
                            ->columnSpanFull()
                            ->live(),

                        Select::make('online_visitor_type_list')
                            ->label(__('Online Visitor Types'))
                            ->multiple()
                            ->options(VisitorType::class)
                            ->hidden(fn (Get $get): bool => ! $get('override_online_visitor_type'))
                            ->required(fn (Get $get): bool => $get('override_online_visitor_type'))
                            ->hintActions(self::visitorTypeBulkActions())
                            ->columnSpanFull(),

                        Toggle::make('override_offline_visitor_type')
                            ->label(__('Customize Offline Visitor Types'))
                            ->helperText('Restrict offline registration to selected visitor types.')
                            ->columnSpanFull()
                            ->live(),

                        Select::make('offline_visitor_type_list')
                            ->label(__('Offline Visitor Types'))
                            ->multiple()
                            ->options(VisitorType::class)
                            ->hidden(fn (Get $get): bool => ! $get('override_offline_visitor_type'))
                            ->required(fn (Get $get): bool => $get('override_offline_visitor_type'))
                            ->hintActions(self::visitorTypeBulkActions())
                            ->columnSpanFull(),
                    ]),

                Section::make('Payment')
                    ->description('Configure proof of payment and registration fees.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_invoice_upload')
                            ->label(__('Require Payment Proof'))
                            ->helperText('The upload is skipped automatically when the calculated total is free.')
                            ->columnSpanFull()
                            ->live(),

                        Select::make('excluded_payment_list')
                            ->label(__('Visitor Types Excluded from Payment'))
                            ->helperText('Selected visitor types will not be charged a registration fee or asked for payment proof.')
                            ->multiple()
                            ->options(VisitorType::class)
                            ->hidden(fn (Get $get): bool => ! $get('show_invoice_upload'))
                            ->hintActions(self::visitorTypeBulkActions())
                            ->columnSpanFull(),

                        self::idrPriceInput('default_registration_fee', __('Default Registration Fee'))
                            ->helperText('Used when the selected visitor type has no override below. Enter 0 for free; leave empty for no default fee.')
                            ->hidden(fn (Get $get): bool => ! $get('show_invoice_upload'))
                            ->columnSpanFull(),

                        self::registrationPaymentPricesRepeater(),
                    ]),
            ]);
    }

    protected static function foodTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Food')
            ->icon('heroicon-m-shopping-bag')
            ->schema([
                Section::make('Public Food Price')
                    ->description('Configure the general price copy shown on the event page.')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Toggle::make('override_offline_food_price_text')
                            ->label(__('Use Custom Price Text'))
                            ->helperText('Replace the formatted general food price with custom public text.')
                            ->columnSpanFull()
                            ->live(),

                        RichEditor::make('offline_food_price_text')
                            ->label(__('Custom Food Price Text'))
                            ->hidden(fn (Get $get): bool => ! $get('override_offline_food_price_text'))
                            ->required(fn (Get $get): bool => $get('override_offline_food_price_text'))
                            ->columnSpanFull(),

                        TextInput::make('offline_food_price')
                            ->label(__('General Food Price'))
                            ->helperText('Public fallback price. Item-specific food prices are configured below.')
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
                    ]),

                Section::make('Food Selection')
                    ->description('Choose the menu structure and optional prices included in payment detail.')
                    ->schema([
                        Toggle::make('food_required')
                            ->label(__('Food Selection Required'))
                            ->helperText('Registrants must choose food before submitting the registration form.')
                            ->default(false),

                        Select::make('food_type')
                            ->label(__('Food Type'))
                            ->helperText('Changing this option clears existing menu items because each type uses a different structure.')
                            ->options(FoodType::class)
                            ->default(FoodType::BUFFET)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('offline_foods', null))
                            ->selectablePlaceholder(false),

                        self::offlineFoodsRepeater(),
                    ]),
            ]);
    }

    protected static function offlineFoodsRepeater(): Repeater
    {
        return Repeater::make('offline_foods')
            ->label(__('Menu Items'))
            ->addActionLabel(__('Add Menu Item'))
            ->maxItems(fn (Get $get): int => self::maxFoodItems($get))
            ->collapsible()
            ->schema(fn (Get $get): array => self::foodItemSchema($get));
    }

    protected static function registrationPaymentPricesRepeater(): Repeater
    {
        return Repeater::make('registration_payment_prices')
            ->label(__('Visitor Type Fee Overrides'))
            ->helperText('Optional. Overrides the default registration fee for selected visitor types.')
            ->hidden(fn (Get $get): bool => ! $get('show_invoice_upload'))
            ->defaultItems(0)
            ->addActionLabel(__('Add Visitor Type Fee'))
            ->collapsible()
            ->collapsed()
            ->itemLabel(fn (array $state): ?string => VisitorType::tryFrom($state['visitor_type'] ?? '')?->getLabel())
            ->columnSpanFull()
            ->grid(2)
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
                self::idrPriceInput('price', __('Food Price'))
                    ->helperText('Optional. Added to payment detail only when this food is selected.')
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
                self::idrPriceInput('price', __('Package Price'))
                    ->helperText('Optional. Applies to the selected food and drink combination.')
                    ->columnSpanFull(),
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

    protected static function contentTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Content')
            ->icon('heroicon-m-document-text')
            ->schema([
                Section::make('Event Copy')
                    ->description('Customize the public text shown on the event and registration pages.')
                    ->schema([
                        Select::make('event_type')
                            ->label(__('Event Type'))
                            ->helperText('Controls the event type wording shown on the public page.')
                            ->options([
                                'soft launch' => 'SOFT LAUNCH',
                                'grand launch' => 'GRAND LAUNCH',
                            ])
                            ->default('soft launch'),

                        Toggle::make('override_deadline_text')
                            ->label(__('Use Custom Deadline Text'))
                            ->helperText('Replace the default registration deadline message.')
                            ->live(),

                        RichEditor::make('deadline_text')
                            ->label(__('Deadline Text'))
                            ->hidden(fn (Get $get): bool => ! $get('override_deadline_text'))
                            ->required(fn (Get $get): bool => $get('override_deadline_text'))
                            ->columnSpanFull(),

                        Toggle::make('override_what_to_prepare')
                            ->label(__('Use Custom Preparation Text'))
                            ->helperText('Replace the default instructions shown before the event.')
                            ->live(),

                        RichEditor::make('what_to_prepare')
                            ->label(__('What to Prepare'))
                            ->hidden(fn (Get $get): bool => ! $get('override_what_to_prepare'))
                            ->required(fn (Get $get): bool => $get('override_what_to_prepare'))
                            ->columnSpanFull(),

                        Toggle::make('override_description_1')
                            ->label(__('Use Custom Primary Description'))
                            ->helperText('Replace the main event description shown publicly.')
                            ->live(),

                        RichEditor::make('description_1')
                            ->label(__('Primary Description'))
                            ->hidden(fn (Get $get): bool => ! $get('override_description_1'))
                            ->required(fn (Get $get): bool => $get('override_description_1'))
                            ->columnSpanFull(),

                        Toggle::make('override_description_2')
                            ->label(__('Use Custom Secondary Description'))
                            ->helperText('Add or replace the supporting event description.')
                            ->live(),

                        RichEditor::make('description_2')
                            ->label(__('Secondary Description'))
                            ->hidden(fn (Get $get): bool => ! $get('override_description_2'))
                            ->required(fn (Get $get): bool => $get('override_description_2'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Event Video')
                    ->description('Optionally replace the default event video.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('override_video')
                            ->label(__('Use Custom Video'))
                            ->helperText('Use an event-specific video instead of the default video.')
                            ->live(),

                        SpatieMediaLibraryFileUpload::make('video')
                            ->label(__('Event Video'))
                            ->hidden(fn (Get $get): bool => ! $get('override_video'))
                            ->required(fn (Get $get): bool => $get('override_video'))
                            ->collection('video')
                            ->acceptedFileTypes(['video/*']),
                    ]),
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
        if ($state === null || $state === '') {
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
