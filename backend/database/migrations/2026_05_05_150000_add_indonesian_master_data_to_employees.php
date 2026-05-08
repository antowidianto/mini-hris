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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nik_ktp', 20)->nullable()->unique()->after('email');
            $table->string('npwp', 30)->nullable()->unique()->after('nik_ktp');
            $table->string('bpjs_kesehatan_number', 30)->nullable()->unique()->after('npwp');
            $table->string('bpjs_ketenagakerjaan_number', 30)->nullable()->unique()->after('bpjs_kesehatan_number');
            $table->string('tax_marital_status', 2)->nullable()->index()->after('bpjs_ketenagakerjaan_number');
            $table->unsignedTinyInteger('tax_dependents')->default(0)->after('tax_marital_status');
            $table->string('bank_name')->nullable()->after('tax_dependents');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_account_holder_name')->nullable()->after('bank_account_number');
            $table->string('employment_type', 20)->default('pkwtt')->index()->after('employment_status');
            $table->date('contract_start_date')->nullable()->after('employment_type');
            $table->date('contract_end_date')->nullable()->index()->after('contract_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['contract_end_date']);
            $table->dropIndex(['employment_type']);
            $table->dropIndex(['tax_marital_status']);
            $table->dropUnique(['bpjs_ketenagakerjaan_number']);
            $table->dropUnique(['bpjs_kesehatan_number']);
            $table->dropUnique(['npwp']);
            $table->dropUnique(['nik_ktp']);
            $table->dropColumn([
                'nik_ktp',
                'npwp',
                'bpjs_kesehatan_number',
                'bpjs_ketenagakerjaan_number',
                'tax_marital_status',
                'tax_dependents',
                'bank_name',
                'bank_account_number',
                'bank_account_holder_name',
                'employment_type',
                'contract_start_date',
                'contract_end_date',
            ]);
        });
    }
};
