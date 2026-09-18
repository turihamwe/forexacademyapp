<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYoPaymentRefsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('yo_transaction_ref')->nullable()->after('payment_gateway_ref')->index();
            $table->string('network_ref')->nullable()->after('yo_transaction_ref')->index();
            $table->string('payer_msisdn', 20)->nullable()->after('network_ref');

            $table->index(['network_ref', 'payer_msisdn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['network_ref', 'payer_msisdn']);
            $table->dropColumn(['yo_transaction_ref', 'network_ref', 'payer_msisdn']);
        });
    }
}
