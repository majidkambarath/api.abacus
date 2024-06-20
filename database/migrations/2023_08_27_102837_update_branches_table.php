<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBranchesTable extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            // Set branch_manager_id and state columns to NULL
            $table->unsignedBigInteger('branch_manager_id')->nullable()->change();
            $table->string('state')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            // If needed, you can define the reverse operation here
            // For example, setting nullable back to false
            $table->unsignedBigInteger('branch_manager_id')->nullable(false)->change();
            $table->string('state')->nullable(false)->change();
        });
    }
}
