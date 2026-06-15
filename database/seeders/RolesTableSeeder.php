<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => '2025-06-15 21:26:45',
                'updated_at' => '2025-06-15 21:26:45',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'user',
                'guard_name' => 'web',
                'created_at' => '2025-06-15 21:27:04',
                'updated_at' => '2025-06-15 21:27:04',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'demo',
                'guard_name' => 'web',
                'created_at' => '2025-07-28 11:57:56',
                'updated_at' => '2025-07-28 11:57:56',
            ),
        ));
        
        
    }
}