<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payrolls')
            ->where('gross_salary', 0)
            ->update([
                'gross_salary' => DB::raw('basic_salary + allowance'),
            ]);

        DB::table('payrolls')
            ->where('other_deduction', 0)
            ->where('deduction', '>', 0)
            ->update([
                'other_deduction' => DB::raw('deduction'),
            ]);

        DB::table('payrolls')
            ->where('take_home_pay', 0)
            ->update([
                'take_home_pay' => DB::raw('net_salary'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfilled derived values should remain available after rollback.
    }
};
