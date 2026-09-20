<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE exams ADD CONSTRAINT check_pass_percentage CHECK (pass_percentage >= 0 AND pass_percentage <= 100)');
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('completed')->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE exams DROP CONSTRAINT IF EXISTS check_pass_percentage');
        }
    }
};
