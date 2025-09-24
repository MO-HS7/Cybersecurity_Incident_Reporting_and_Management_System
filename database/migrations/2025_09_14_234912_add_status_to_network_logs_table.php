<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('network_logs', 'status')) {
                $table->enum('status', ['pending', 'processing', 'processed', 'failed'])
                      ->default('pending')
                      ->after('upload_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('network_logs', function (Blueprint $table) {
          $table->enum('status', ['new', 'processing', 'completed'])->default('new');
        });
    }
};
