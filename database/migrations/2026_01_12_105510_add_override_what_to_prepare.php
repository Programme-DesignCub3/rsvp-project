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
        Schema::table('event_details', function (Blueprint $table) {
            $table->boolean('override_what_to_prepare')->default(false);
            $table->text('what_to_prepare')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prepare', function (Blueprint $table) {
            $table->dropColumn('override_what_to_prepare');
            $table->dropColumn('what_to_prepare');
        });
    }
};
