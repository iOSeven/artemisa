<?php
namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        $faker = Faker::create();

        //Crear usuario por default
        \DB::table('users')->insert([
            [
                'role_id' => 1,
                'name' => 'Admin',
                'last_name' => $faker->lastName,
                'email' => 'soporte@artemisa.com',
                'password' => Hash::make('Artemisa2022$')
            ]
        ]);

        //Crear usuarios de prueba
        /*for ($i=0; $i < 50; $i++) {
            \DB::table('users')->insert([
                [
                    'role_id' => 2,
                    'name' => $faker->firstName("male"),
                    'last_name' => $faker->lastName,
                    'email' => $faker->email,
                    'password' => Hash::make('admin')
                ]
            ]);
        }*/
    }
}
