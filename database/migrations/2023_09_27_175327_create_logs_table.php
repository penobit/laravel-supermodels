<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create(config('supermodels.tables.log'), function(Blueprint $table): void {
            $table->id();

            $table->morphs('model');
            $table->foreignIdFor(config('supermodels.models.user'))->nullable();
            $table->string('type')->default('log');
            $table->string('action');
            $table->longText('content');
            $table->boolean('notified')->nullable()->default(false);
            $table->json('params')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists(config('supermodels.tables.log'));
    }
};
