<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Akun tetap untuk pengurus koperasi (password: password)
        User::factory()->role('admin')->create([
            'name' => 'Admin Koperasi',
            'email' => 'admin@simkopdes.test',
            'nik' => '3201000000000001',
            'alamat' => 'Kantor Koperasi Desa Sukamaju',
            'no_wa' => '081200000001',
        ]);

        User::factory()->role('bendahara')->create([
            'name' => 'Bendahara Koperasi',
            'email' => 'bendahara@simkopdes.test',
            'nik' => '3201000000000002',
            'alamat' => 'Kantor Koperasi Desa Sukamaju',
            'no_wa' => '081200000002',
        ]);

        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'anggota@simkopdes.test',
            'nik' => '3201000000000003',
            'alamat' => 'Dusun Krajan RT 02 RW 01, Desa Sukamaju',
            'no_wa' => '081200000003',
        ]);

        // Anggota dummy untuk data uji
        User::factory(10)->create();
    }
}
