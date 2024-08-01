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
                $table->boolean('enabled')->default(0)->comment('1=Yes, 0=No');
                $table->string('title');
                $table->tinyInteger('shipping_rate')->comment('1=Per Item, 2=Per Order');
                $table->tinyInteger('shipping_rate_calculation')->comment('1=Sum of Rate 2=Maximum Value 3=Minimum Value');
                $table->string('method_name')->nullable();
                $table->boolean('product_shipping_cost')->comment('1=Yes 0=No');
                $table->decimal('rate_per_item', 8, 2)->nullable();
                $table->decimal('handling_fee', 8, 2)->nullable();
                $table->boolean('applicable_countries')->comment('0=All Allowed Countries 1=Specific Countries');
                $table->longText('countries')->nullable();
                $table->boolean('method_if_not_applicable')->comment('1=Yes 0=No')->nullable();
                $table->text('displayed_error_message')->nullable();
                $table->boolean('show_method_for_admin')->nullable()->comment('1=Yes 0=No');
                $table->decimal('min_order_amount', 8, 2)->nullable();
                $table->decimal('max_order_amount', 8, 2)->nullable();
                $table->string('sort_order')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
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
