<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_request_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_request_id')->constrained('cancellation_requests')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cancellation_request_id', 'order_id'], 'cxl_req_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_request_orders');
    }
};
