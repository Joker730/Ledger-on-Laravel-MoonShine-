<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description', 500);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();

            $table->index('date');
            $table->index('is_posted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
