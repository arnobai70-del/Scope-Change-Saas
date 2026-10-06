<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->default('UTC')->after('password');
            $table->string('locale', 12)->default('en')->after('timezone');
            $table->unsignedBigInteger('current_workspace_id')->nullable()->after('locale')->index();
            $table->boolean('is_admin')->default(false)->after('current_workspace_id');
            $table->string('status', 20)->default('active')->after('is_admin');
            $table->json('notification_preferences')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['current_workspace_id']);
            $table->dropColumn(['timezone', 'locale', 'current_workspace_id', 'is_admin', 'status', 'notification_preferences', 'last_login_at']);
        });
    }
};
