<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type')->index();
            $table->string('source_system')->nullable();
            $table->string('external_id')->nullable();
            $table->json('data')->nullable();
            $table->json('normalized_data')->nullable();
            $table->boolean('is_master')->default(false)->index();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type', 'is_master']);
            $table->index(['tenant_id', 'source_system']);
            $table->unique(['tenant_id', 'type', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_entities');
    }
};
