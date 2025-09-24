<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_models', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة
            if (!Schema::hasColumn('ml_models', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('ml_models', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('ml_models', 'file_path')) {
                $table->string('file_path')->after('description');
            }
            if (!Schema::hasColumn('ml_models', 'trained_at')) {
                $table->timestamp('trained_at')->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ml_models', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'file_path', 'trained_at']);
        });
    }
};
