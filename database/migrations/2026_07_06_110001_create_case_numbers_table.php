<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('system_ids')->cascadeOnDelete();
            $table->string('code');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['system_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_numbers');
    }
};
