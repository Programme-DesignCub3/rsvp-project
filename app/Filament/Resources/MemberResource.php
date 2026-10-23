<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Members';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                SpatieMediaLibraryFileUpload::make('profile_photo')
                    ->collection('profile_photo')
                    ->columnSpanFull(),
                Toggle::make('hide'),
                TextInput::make('name'),
                TextInput::make('email')
                    ->email(),
                TextInput::make('phone'),
                TextInput::make('industry'),
                Select::make('categories')
                    ->multiple()
                    ->relationship('categories', 'name')
                    ->preload()
                    ->searchable(),
                TextInput::make('company'),
                SpatieMediaLibraryFileUpload::make('company_logo')
                    ->collection('company_logo')
                    ->columnSpanFull(),
                Section::make('Featured Social Media')
                    ->description('Add social media links')
                    ->schema([
                        TextInput::make('social')
                            ->label('Social Media link'),
                        TextInput::make('social_label'),
                    ]),

                Section::make('Member website')
                    ->description('Add website links')
                    ->schema([
                        TextInput::make('website')
                            ->label('Website link'),
                        TextInput::make('website_label'),
                    ]),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('profile_photo')
                    ->collection('profile_photo'),
                IconColumn::make('hide')
                    ->label('hidden status')
                    ->icon(fn (string $state): string => match ($state) {
                        '1' => 'heroicon-o-eye-slash',
                        '0' => 'heroicon-o-eye',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
            ])
            ->defaultSort('name')

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMembers::route('/'),
        ];
    }
}
