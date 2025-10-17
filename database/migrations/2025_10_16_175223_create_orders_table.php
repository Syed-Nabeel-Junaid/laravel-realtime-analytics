<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->decimal('total_cost', 10, 2);
        $table->json('items')->nullable(); // store order items
        $table->timestamp('order_time')->useCurrent();
        $table->enum('status', ['pending', 'in-progress', 'completed', 'cancelled'])->default('pending');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
