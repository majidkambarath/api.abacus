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
        Schema::table('lead_service', function (Blueprint $table) {
            $table->decimal('price_from', 10, 2)->nullable()->after('service_id');
            $table->decimal('price_to', 10, 2)->nullable()->after('price_from');
            $table->decimal('incentive_amount', 10, 2)->nullable()->after('price_to');
            $table->string('incentive_type')->nullable()->after('incentive_amount');
            $table->tinyInteger('status')->default(1)->after('incentive_type');
            $table->date('status_changed_date')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_service', function (Blueprint $table) {
            $table->dropColumn(['price_from', 'price_to', 'incentive_amount', 'incentive_type', 'status', 'status_changed_date']);
        });
    }
};
