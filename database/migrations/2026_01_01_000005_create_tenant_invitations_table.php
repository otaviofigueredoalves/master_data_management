<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('email');
            $table->string('token');
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
