<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('student_activity');
        Schema::dropIfExists('media');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
