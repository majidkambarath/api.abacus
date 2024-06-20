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
        Schema::table('followups', function (Blueprint $table) {
            // Remove the 'note' column
            $table->dropColumn('note');

            // Add 'followup_reason_id' as a foreign key
            $table->unsignedBigInteger('followup_reason_id');
            $table->foreign('followup_reason_id')->references('id')->on('followup_reasons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('followups', function (Blueprint $table) {
            // Add 'note' column back (you can define its type and attributes)
            $table->text('note')->nullable();

            // Remove the foreign key constraint
            $table->dropForeign(['followup_reason_id']);

            // Remove 'followup_reason_id' column
            $table->dropColumn('followup_reason_id');
        });
    }
};
