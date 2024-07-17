<?php

namespace Database\Seeders;

use App\Http\Controllers\AlvoController;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco de dados da aplicação.
     */
    public function run(): void
    {
        // Cria os usuários de exemplo
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

        // Define os alvos de exemplo
        $alvos = [
            [
                'nome' => 'Câmara SJP',
                'url' => 'https://cmsjp.pr.gov.br/concurso-publico-2023',
                'elemento' => 'post-100',
            ],
            [
                'nome' => 'CPNU Cesgranrio',
                'url' => 'https://cpnu.cesgranrio.org.br/editais',
                'elemento' => 'main',
            ],
            [
                'nome' => 'DRH-SEAP',
                'url' => 'https://www.institutoaocp.org.br/concursos/596',
                'elemento' => 'main',
            ],
            [
                'nome' => 'Câmara-SFS',
                'url' => 'https://www.institutounivida.org.br/concurso/cmsfs2024',
                'elemento' => 'main',
            ],
            [
                'nome' => 'COREN',
                'url' => 'https://www.quadrix.org.br/todos-os-concursos/em-andamento/corenpr_2024.aspx',
                'elemento' => 'taba-publicacoes',
            ]
        ];

        // Cria os alvos usando o controlador
        $alvoController = new AlvoController();
        foreach ($alvos as $alvoData) {
            $alvoController->createAlvo($alvoData);
        }
    }
}
