<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('status')->default('in_progress')->after('score');
        });

        // Set existing submitted attempts to completed
        DB::table('exam_attempts')
            ->whereNotNull('submitted_at')
            ->update(['status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
