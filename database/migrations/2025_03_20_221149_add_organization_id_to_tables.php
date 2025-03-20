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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()->after('id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()->after('id');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()->after('id');
        });

        Schema::table('category_rules', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('category_rules', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
