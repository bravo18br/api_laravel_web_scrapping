<?php

namespace Database\Seeders;

use App\Http\Controllers\AlvoController;
use App\Models\Alvo;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $users = [
            [
                'name' => 'bravo18br',
                'email' => 'bravo18br@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $alvos = [
            [
                'nome'=>'Câmara SJP',
                'url'=>'https://cmsjp.pr.gov.br/concurso-publico-2023',
                'elemento'=>'post-100',
            ],
            [
                'nome'=>'CPNU Cesgranrio',
                'url'=>'https://cpnu.cesgranrio.org.br/editais',
                'elemento'=>'main',
            ],
            [
                'nome'=>'DRH-SEAP',
                'url'=>'https://www.institutoaocp.org.br/concursos/596',
                'elemento'=>'main',
            ],
            [
                'nome'=>'Câmara-SFS',
                'url'=>'https://www.institutounivida.org.br/concurso/cmsfs2024',
                'elemento'=>'main',
            ],
            [
                'nome'=>'COREN',
                'url'=>'https://www.quadrix.org.br/todos-os-concursos/em-andamento/corenpr_2024.aspx',
                'elemento'=>'main',
            ]
            ];

            foreach ($alvos as $alvo) {
                $alvo = Alvo::create($alvo);
                $alvoController = new AlvoController();
                $alvo->conteudo = $alvoController->geraConteudo($alvo);
                $alvo->save();
            }
    }
}
