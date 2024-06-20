<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastLoginToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the last_login column after the created_at column
            $table->timestamp('last_login')->nullable()->after('created_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse the migration by dropping the last_login column
            $table->dropColumn('last_login');
        });
    }
}

