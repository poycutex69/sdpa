<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('priority')->index();
            $table->string('category')->index();
            $table->string('status')->index()->default('new');
            $table->timestamp('due_at')->nullable()->index();
            $table->text('summary')->nullable();
            $table->string('suggested_next_action', 500)->nullable();
            $table->string('summary_source')->default('rules');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
