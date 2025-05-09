<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionType;

class TransactionTypeSeeder extends Seeder
{
    public function run()
    {
        foreach (TransactionType::TYPES as $id => $name) {
            TransactionType::updateOrCreate(
                ['id' => $id],
                ['name' => $name]
            );
        }
    }
}
