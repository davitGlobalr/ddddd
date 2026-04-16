<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BooksSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('books')->count() > 0) {
            // Prevent duplicates when running `db:seed` multiple times.
            return;
        }

        $faker = fake();

        $now = now();

        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $rows[] = [
                'name' => $faker->sentence(3),
                'author' => $faker->name(),
                'description' => $faker->paragraph(2),
                // Keep the column name as requested: `quntity`.
                'quntity' => $faker->numberBetween(1, 50),
                'img' => sprintf('https://picsum.photos/seed/book-%d/300/450', $i),
                'price' => (string) $faker->randomFloat(2, 10, 300),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('books')->insert($rows);
    }
}
