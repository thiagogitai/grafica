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
            $table->decimal('width', 8, 2)->nullable()->after('price');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->boolean('is_duplex')->default(false)->after('height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'is_duplex']);
        });
    }
};
