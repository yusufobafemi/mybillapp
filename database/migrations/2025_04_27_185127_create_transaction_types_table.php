<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_transaction_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTypesTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->comment('Defines the various transaction types like Airtime, Data, Cable, etc.');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_types');
    }
}
