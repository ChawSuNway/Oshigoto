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
        Schema::table('users', function (Blueprint $table) {
            // admin: manages users | manager: receives reports | employee: writes reports
            $table->enum('role', ['admin', 'manager', 'employee'])->default('employee')->after('email');
            // The manager an employee reports to (self-referencing).
            $table->foreignId('manager_id')->nullable()->after('role')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['role', 'manager_id']);
        });
    }
};
