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
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('shift_start')->nullable()->after('attendance_date');
            $table->time('shift_end')->nullable()->after('shift_start');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0)->after('shift_end');
            $table->unsignedSmallInteger('overtime_minutes')->default(0)->after('time_out');
            $table->string('attendance_source', 30)->default('manual')->index()->after('status');
            $table->string('import_batch')->nullable()->index()->after('attendance_source');
            $table->text('notes')->nullable()->after('import_batch');

            $table->index(['attendance_date', 'attendance_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['attendance_date', 'attendance_source']);
            $table->dropIndex(['attendance_source']);
            $table->dropIndex(['import_batch']);
            $table->dropColumn([
                'shift_start',
                'shift_end',
                'late_tolerance_minutes',
                'overtime_minutes',
                'attendance_source',
                'import_batch',
                'notes',
            ]);
        });
    }
};
