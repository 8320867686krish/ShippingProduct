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
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->boolean('enabled');
                $table->string('title', 100);
                $table->string('shipping_rate', 20);
                $table->string('shipping_rate_calculation', 20);
                $table->string('method_name', 100);
                $table->boolean('product_shipping_cost');
                $table->integer('rate_per_item');
                $table->integer('handling_fee');
                $table->string('applicable_countries', 25);
                $table->boolean('method_if_not_applicable');
                $table->string('displayed_error_message');
                $table->boolean('show_method_for_admin');
                $table->integer('minimum_order_amount');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'country_id')) {
                    $table->dropForeign(['country_id']);
                }
            });
        }
        Schema::dropIfExists('settings');
    }
};
