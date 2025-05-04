<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add phone column with validation for unique constraint
            $table->string('phone')->unique()->nullable()->after('email'); // Or place this column where it fits your table structure
            
            // Add balance column to store user balance
            $table->decimal('balance', 10, 2)->default(0)->after('phone'); // Default balance is 0
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the phone and balance columns if we roll back the migration
            $table->dropColumn(['phone', 'balance']);
        });
    }
};
