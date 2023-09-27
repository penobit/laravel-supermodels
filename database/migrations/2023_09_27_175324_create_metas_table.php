<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create(config('supermodels.tables.meta'), function(Blueprint $table): void {
            $table->id();

            $table->morphs('model');
            $table->string('name')->index('metadata_name');
            $table->longText('value');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists(config('supermodels.tables.meta'));
    }
};
