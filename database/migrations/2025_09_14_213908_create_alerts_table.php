<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_log_id')->constrained('network_logs')->onDelete('cascade');
            $table->foreignId('ml_model_id')->constrained('ml_models')->onDelete('cascade');
            $table->string('attack_type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->string('source_ip')->nullable();
            $table->string('destination_ip')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->enum('status', ['new', 'investigating', 'resolved', 'false_positive'])->default('new');
            $table->timestamp('detected_at');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
