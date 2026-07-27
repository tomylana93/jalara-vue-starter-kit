<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_uploads', function (Blueprint $table): void {
            $table->string('branding_field')->nullable()->index()->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_uploads', function (Blueprint $table): void {
            $table->dropColumn('branding_field');
        });
    }
};
