<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transaction_type_id')->constrained('transaction_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->string('reference')->unique();
            $table->timestamps();

            $table->comment('Stores all transactions made by users.');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
