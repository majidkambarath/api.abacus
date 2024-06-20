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
        Schema::table('jobs', function (Blueprint $table) {
            // Add the branch_id column
            $table->unsignedBigInteger('branch_id')->after('lead_id')->nullable(false);
            // Define the foreign key constraint
            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['branch_id']);

            // Drop the branch_id column
            $table->dropColumn('branch_id');
        });
    }
};
