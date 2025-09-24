<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    Schema::create('ml_models', function (Blueprint $table) {
        $table->id(); // عمود id
        $table->string('name');
        $table->string('description')->nullable();
        $table->timestamps();
    });
}

    public function down(): void {
        Schema::dropIfExists('ml_models');
    }
};
