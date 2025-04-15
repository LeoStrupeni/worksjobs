<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Leo Strupeni',
            'email' => 'leonardo.strupeni@gmail.com',
            'password' => Hash::make('1234')
        ]);
        $admin->assignRole('sistema');

        $admin1 = User::create([
            'name' => 'Federico Strupeni',
            'email' => 'federico@strupeni.com.ar',
            'password' => Hash::make('fstrupeni')
        ]);

        $admin1->assignRole('admin');

        $admin2 = User::create([
            'name' => 'Matias Frino',
            'email' => 'matias@strupeni.com.ar',
            'password' => Hash::make('mfrino')
        ]);

        $admin2->assignRole('admin');

        $assistant = User::create([
            'name' => 'tecnico',
            'email' => 'tecnico',
            'password' => Hash::make('1234')
        ]);

        $assistant->assignRole('tecnico');
    }
}
