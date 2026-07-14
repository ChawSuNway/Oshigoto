<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_comings', function (Blueprint $table) {
            $table->json('to_emails')->nullable()->after('minutes');
            $table->json('cc_emails')->nullable()->after('to_emails');
            $table->string('subject')->nullable()->after('cc_emails');
        });
    }

    public function down(): void
    {
        Schema::table('late_comings', function (Blueprint $table) {
            $table->dropColumn(['to_emails', 'cc_emails', 'subject']);
        });
    }
};
