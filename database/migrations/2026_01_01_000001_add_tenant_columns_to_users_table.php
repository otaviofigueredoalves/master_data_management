<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('current_tenant_id')->nullable();
            $table->string('timezone')->nullable()->default('UTC');
            $table->string('locale')->nullable()->default('en');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn([
                'tenant_id',
                'current_tenant_id',
                'timezone',
                'locale',
                'last_login_at',
            ]);
        });
    }
};
