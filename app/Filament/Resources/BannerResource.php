<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make(
                    [
                        Section::make([
                            Group::make([
                                SpatieMediaLibraryFileUpload::make('desktop')
                                    ->required()
                                    ->maxFiles(1)
                                    ->image()
                                    ->collection('banner_desktop'),
                                SpatieMediaLibraryFileUpload::make('mobile')
                                    ->required()
                                    ->maxFiles(1)
                                    ->image()
                                    ->required(fn (Get $get): bool => $get('has_mobile_asset'))
                                    ->disabled(fn (Get $get): bool => ! $get('has_mobile_asset'))
                                    ->hidden(fn (Get $get): bool => ! $get('has_mobile_asset'))
                                    ->collection('banner_mobile'),
                                TextInput::make('title')
                                    ->maxLength(255)
                                    ->helperText('optional'),
                                TextInput::make('link')
                                    ->maxLength(255)
                                    ->helperText('optional'),
                            ]),
                        ]),
                        Section::make([

                            Radio::make('has_mobile_asset')
                                ->boolean()
                                ->default(0)
                                ->live()
                                ->required(),

                            Toggle::make('enable')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger'),
                        ])
                            ->grow(false),
                    ]
                ),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('desktop')
                    ->collection('banner_desktop'),
                SpatieMediaLibraryImageColumn::make('mobile')
                    ->collection('banner_mobile'),
                TextColumn::make('type'),
                TextColumn::make('link'),
                ToggleColumn::make('enable')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column')
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
