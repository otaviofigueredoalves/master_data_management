<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdm_entity_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parent_id')->index();
            $table->unsignedBigInteger('child_id')->index();
            $table->string('relation_type')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'parent_id', 'child_id', 'relation_type']);
            $table->index(['tenant_id', 'child_id', 'relation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_entity_relations');
    }
};
