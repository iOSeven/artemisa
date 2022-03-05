<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        //Crear modulos de prueba
        for ($i=0; $i < 9; $i++) {
            \DB::table('modules')->insert([
                [
                    'name' => $faker->firstName("male"),
                    'description' => $faker->text,
                    'image_url' => $faker->image(storage_path('app/public/modules'), 450, 300, 'bussines', false),
                    'url' => $faker->url,

                ]
            ]);
        }
    }
}
