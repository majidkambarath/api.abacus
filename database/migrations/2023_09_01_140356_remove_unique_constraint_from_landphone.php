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
        Schema::table('business', function (Blueprint $table) {
            $table->dropUnique('business_landphone_unique'); // Replace 'business_landphone_unique' with the actual unique constraint name
        });
    }

    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->unique('landphone'); // Restore the unique constraint if needed
        });
    }
};
