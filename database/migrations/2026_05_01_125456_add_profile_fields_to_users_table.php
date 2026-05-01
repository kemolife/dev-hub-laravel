<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->unique()->nullable()->after('name');
            $table->text('bio')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('bio');
            $table->string('website_url')->nullable()->after('avatar_path');
            $table->timestamp('last_seen_at')->nullable()->after('website_url');
            $table->string('timezone')->nullable()->after('last_seen_at');
            $table->uuid('public_id')->unique()->nullable()->after('id');
            $table->string('role')->default('member')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'bio',
                'avatar_path',
                'website_url',
                'last_seen_at',
                'timezone',
                'public_id',
                'role',
            ]);
        });
    }
};
