<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_email_recipients', function (Blueprint $table) {
            $table->unsignedBigInteger('order_column')->nullable()->after('email');
        });

        DB::table('admin_email_recipients')
            ->whereNull('order_column')
            ->update(['order_column' => DB::raw('id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_email_recipients', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });
    }
};
