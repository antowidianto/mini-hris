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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('module', 60);
            $table->morphs('approvable');
            $table->unsignedSmallInteger('step_order');
            $table->string('role', 60);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['approvable_type', 'approvable_id', 'step_order']);
            $table->index(['company_id', 'module', 'status', 'step_order']);
            $table->index(['company_id', 'module', 'role', 'status']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('pending')->after('generated_at');
            $table->text('approval_notes')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approval_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            $table->index(['company_id', 'approval_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'approval_status']);
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approval_status',
                'approval_notes',
                'approved_at',
                'rejected_at',
            ]);
        });

        Schema::dropIfExists('approval_steps');
    }
};
