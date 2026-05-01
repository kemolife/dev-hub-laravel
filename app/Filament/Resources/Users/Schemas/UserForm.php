<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at'),
                TextInput::make('username'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('avatar_path'),
                TextInput::make('website_url')
                    ->url(),
                DateTimePicker::make('last_seen_at'),
                TextInput::make('timezone'),
                TextInput::make('public_id'),
                Select::make('role')
                    ->options(Role::class)
                    ->default('member')
                    ->required(),
                TextInput::make('stripe_id'),
                TextInput::make('pm_type'),
                TextInput::make('pm_last_four'),
                DateTimePicker::make('trial_ends_at'),
                TextInput::make('plan')
                    ->default('free'),
                DateTimePicker::make('suspended_at'),
                DateTimePicker::make('suspended_until'),
                TextInput::make('suspension_reason'),
            ]);
    }
}
