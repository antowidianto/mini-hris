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
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('supervisor_status', 20)->default('pending')->after('status');
            $table->text('supervisor_notes')->nullable()->after('supervisor_status');
            $table->foreignId('supervisor_approved_by')->nullable()->after('supervisor_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_approved_by');
            $table->string('hr_status', 20)->default('pending')->after('supervisor_approved_at');
            $table->text('hr_notes')->nullable()->after('hr_status');
            $table->foreignId('hr_approved_by')->nullable()->after('hr_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');

            $table->index(['supervisor_status', 'hr_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['supervisor_status', 'hr_status']);
            $table->dropConstrainedForeignId('hr_approved_by');
            $table->dropConstrainedForeignId('supervisor_approved_by');
            $table->dropColumn([
                'supervisor_status',
                'supervisor_notes',
                'supervisor_approved_at',
                'hr_status',
                'hr_notes',
                'hr_approved_at',
            ]);
        });
    }
};
