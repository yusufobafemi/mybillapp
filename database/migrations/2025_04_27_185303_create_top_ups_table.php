<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_top_ups_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopUpsTable extends Migration
{
    public function up()
    {
        Schema::create('top_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('gateway');
            $table->string('transaction_reference')->unique();
            $table->enum('status', ['Successful', 'Pending', 'Failed']);
            $table->timestamps();

            $table->comment('Tracks the top-up transactions made by users.');
        });
    }

    public function down()
    {
        Schema::dropIfExists('top_ups');
    }
}
