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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->string('type', 30)->default('branch');
            $table->string('area')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('position_id')->constrained('employees')->nullOnDelete();
            $table->index(['branch_id', 'employment_status']);
            $table->index(['supervisor_id', 'employment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['supervisor_id', 'employment_status']);
            $table->dropIndex(['branch_id', 'employment_status']);
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::dropIfExists('branches');
    }
};
