<?php

use App\Enums\UserRole;
use App\Models\EventDetail;
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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('registration_counter')->default(0)->after('slug');
        });

        if (! Schema::hasColumn('event_details', 'show_invoice_upload')) {
            Schema::table('event_details', function (Blueprint $table) {
                $table->boolean('show_invoice_upload')->default(true);
            });
        }

        Schema::table('event_details', function (Blueprint $table) {
            $table->boolean('enable_unique_payment_code')->default(true);
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->unsignedInteger('registration_sequence')->nullable()->after('order_id');
            $table->unsignedBigInteger('payment_base_amount')->nullable()->after('registration_sequence');
            $table->unsignedBigInteger('payment_amount')->nullable()->after('payment_base_amount');
            $table->string('payment_berita')->nullable()->after('payment_amount');
            $table->boolean('payment_verified')->default(false)->after('payment_berita');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified');
            $table->unsignedBigInteger('payment_verified_by')->nullable()->after('payment_verified_at');

            $table->unique(['event_id', 'registration_sequence']);
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('invited_by')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Guest->value)->after('email');
        });

        DB::table('users')->where('email', 'dean@designcub3.com')->update(['role' => UserRole::Admin->value]);
        DB::table('users')->where('email', 'magnitude.bni@gmail.com')->update(['role' => UserRole::Guest->value]);

        $this->configureAnniversaryEvent();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'registration_sequence']);
            $table->dropColumn([
                'registration_sequence',
                'payment_base_amount',
                'payment_amount',
                'payment_berita',
                'payment_verified',
                'payment_verified_at',
                'payment_verified_by',
            ]);
        });

        Schema::table('event_details', function (Blueprint $table) {
            $table->dropColumn('enable_unique_payment_code');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('registration_counter');
        });
    }

    /**
     * Configure the live anniversary event when this migration reaches production.
     */
    private function configureAnniversaryEvent(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'bni-magnitude-2nd-anniversary')
            ->value('id');

        if ($eventId === null) {
            return;
        }

        $detail = EventDetail::query()->where('event_id', $eventId)->first();

        if ($detail === null) {
            return;
        }

        $detail->registration_payment_prices = [
            ['visitor_type' => 'magnitude', 'price' => 1000000, 'label' => null],
        ];
        $detail->default_registration_fee = 500000;
        $detail->excluded_payment_list = null;
        $detail->override_offline_visitor_type = true;
        $detail->offline_visitor_type_list = ['visitor', 'magnitude', 'observer'];
        $detail->show_invoice_upload = true;
        $detail->enable_unique_payment_code = true;
        $detail->save();
    }
};
