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
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price_cents', 'price');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
        });
        \Illuminate\Support\Facades\DB::statement('UPDATE products SET price = price / 100.0');

        Schema::table('bundles', function (Blueprint $table) {
            $table->renameColumn('price_cents', 'price');
        });
        Schema::table('bundles', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
        });
        \Illuminate\Support\Facades\DB::statement('UPDATE bundles SET price = price / 100.0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('UPDATE products SET price = price * 100.0');
        Schema::table('products', function (Blueprint $table) {
            $table->integer('price')->unsigned()->change();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price', 'price_cents');
        });

        \Illuminate\Support\Facades\DB::statement('UPDATE bundles SET price = price * 100.0');
        Schema::table('bundles', function (Blueprint $table) {
            $table->integer('price')->unsigned()->change();
        });
        Schema::table('bundles', function (Blueprint $table) {
            $table->renameColumn('price', 'price_cents');
        });
    }
};
