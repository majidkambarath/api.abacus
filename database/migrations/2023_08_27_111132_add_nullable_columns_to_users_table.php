<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullableColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add nullable date of birth (dob) column
            $table->date('dob')->nullable()->after('email');

            // Add nullable address column
            $table->string('address')->nullable()->after('dob');

            // Add nullable join date (join_date) column
            $table->date('join_date')->nullable()->after('address');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse the changes made in the 'up' method when rolling back the migration
            $table->dropColumn(['dob', 'address', 'join_date']);
        });
    }
}

