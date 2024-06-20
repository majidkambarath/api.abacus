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
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('lead_id');
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->text('note');
            $table->integer('followup_status')->default(1);
            $table->integer('status')->default(1);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('job_id')->references('id')->on('jobs');
            $table->foreign('lead_id')->references('id')->on('leads');
            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
