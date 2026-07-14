<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unused work_items / progress_items columns. Guarded with hasColumn
     * so it is a no-op on fresh installs where they were never created.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            foreach (['work_items', 'progress_items'] as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Recreate the columns (nullable JSON) if the change is rolled back.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'work_items')) {
                $table->json('work_items')->nullable()->after('cases');
            }
            if (! Schema::hasColumn('reports', 'progress_items')) {
                $table->json('progress_items')->nullable()->after('work_items');
            }
        });
    }
};
