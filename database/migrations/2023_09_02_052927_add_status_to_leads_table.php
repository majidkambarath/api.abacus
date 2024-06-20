<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            // Add the 'status' column after 'branch_id' with a default value of 1
            $table->unsignedInteger('status')->default(1)->after('branch_id');
        });
    }

    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            // Reverse the migration by dropping the 'status' column
            $table->dropColumn('status');
        });
    }
};
