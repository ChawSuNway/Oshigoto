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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Manager who receives the report (nullable in case the user was unassigned).
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('report_date');
            $table->string('user_name');
            $table->string('manager_name');

            $table->time('work_in');
            $table->time('work_out');
            $table->decimal('total_hours', 5, 2)->default(0);

            // Repeatable entries stored as JSON so the dynamic form maps 1:1.
            $table->json('cases')->nullable();          // [{system_id, case_no, time_h}]
            $table->json('tomorrow_plans')->nullable(); // [string]

            $table->text('problems')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
