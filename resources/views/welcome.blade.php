<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitora Sites API</title>
</head>

<body>
    <h1>Monitora Sites API</h1>
    <p>Este projeto é uma aplicação Laravel que monitora sites e envia notificações via WhatsApp quando detecta alterações. A aplicação é executada em contêineres Docker para facilitar a configuração e a execução.</p>

    <h2>Instruções para Rodar a Aplicação</h2>

    <h3>1. Copiar todos os arquivos para uma pasta no servidor Docker</h3>
    <ul>
        <li>Faça um clone do projeto para uma pasta no servidor Docker:
            <pre><code>sh
gh repo clone bravo18br/docker_api_laravel_web_scrapping chris_api_laravel_web_scrapping
</code></pre>
        </li>
    </ul>

    <h3>2. Editar o <code>.env</code></h3>
    <ul>
        <li>Renomeie o arquivo <code>.env.example</code> para <code>.env</code> na pasta do projeto.</li>
        <li>Preencha a linha <code>GITHUB_TOKEN=&lt;seu_github_token&gt;</code>.</li>
        <li>Edite as linhas <code>MAIL_USERNAME='usuario@gmail.com'</code> e <code>MAIL_PASSWORD='senha para aplicaçoes do Google'</code>.</li>
    </ul>

    <h3>3. Criar a imagem</h3>
    <ul>
        <li>Navegue até a pasta do projeto no terminal.</li>
        <li>Execute o comando:
            <pre><code>sh
docker build -t chris_api_laravel_web_scrapping:alpha .
</code></pre>
        </li>
    </ul>

    <h3>4. Rodar o Docker Compose</h3>
    <ul>
        <li>Navegue até a pasta do projeto no terminal.</li>
        <li>Execute o comando:
            <pre><code>sh
docker compose up -d
</code></pre>
        </li>
        <li>Sempre execute o "docker compose up -d" sem o hífen no comando, pois o "docker-compose" é antigo e gera vários problemas de conflitos de imagens/volumes.</li>
    </ul>

    <h3>5. Caso precise parar a execução</h3>
    <ul>
        <li>Navegue até a pasta do projeto no terminal.</li>
        <li>Execute o comando:
            <pre><code>sh
docker compose down
</code></pre>
        </li>
    </ul>

    <h3>6. Caso precise acessar os containers em execução</h3>
    <ul>
        <li>Execute o comando:
            <pre><code>sh
docker exec -it chris_api_laravel_web_scrapping /bin/bash
docker exec -it wppconnect /bin/sh
</code></pre>
        </li>
    </ul>

    <h3>7. Para verificar os logs</h3>
    <ul>
        <li>Execute o comando:
            <pre><code>sh
tail -f storage/logs/jobs.log
</code></pre>
        </li>
    </ul>

    <h2>Conseguir o GITHUB_TOKEN</h2>

    <p>Para gerar um token de acesso pessoal (Personal Access Token) no GitHub, siga os passos abaixo:</p>

    <ol>
        <li><strong>Acesse o GitHub:</strong>
            <ul>
                <li>Abra seu navegador e vá para <a href="https://github.com">GitHub</a>.</li>
                <li>Faça login com sua conta do GitHub.</li>
            </ul>
        </li>
        <li><strong>Navegue até as Configurações:</strong>
            <ul>
                <li>Clique no ícone do seu perfil no canto superior direito.</li>
                <li>Selecione "Settings" (Configurações) no menu suspenso.</li>
            </ul>
        </li>
        <li><strong>Acesse a seção de Tokens:</strong>
            <ul>
                <li>No menu lateral esquerdo, clique em "Developer settings" (Configurações de desenvolvedor).</li>
                <li>Em seguida, clique em "Personal access tokens" (Tokens de acesso pessoal).</li>
                <li>Selecione "Tokens (classic)" para criar um token de acesso clássico.</li>
            </ul>
        </li>
        <li><strong>Gerar um novo token:</strong>
            <ul>
                <li>Clique no botão "Generate new token" (Gerar novo token).</li>
                <li>Dê um nome ao token para referência futura.</li>
                <li>Selecione as permissões necessárias. Para acessar repositórios privados, selecione <code>repo</code>.</li>
                <li>Desça até o final da página e clique em "Generate token" (Gerar token).</li>
            </ul>
        </li>
        <li><strong>Copiar o token:</strong>
            <ul>
                <li>O token será exibido uma única vez. Copie-o e armazene-o em um lugar seguro.</li>
                <li>Adicione o token copiado ao arquivo <code>.env</code> da sua aplicação.</li>
            </ul>
        </li>
    </ol>

    <h2>Conseguir Credenciais do GMAIL</h2>

    <p>Para obter as credenciais do GMAIL, siga os passos abaixo:</p>

    <ol>
        <li><strong>Acesse a conta do Google:</strong>
            <ul>
                <li>Abra seu navegador e vá para <a href="https://accounts.google.com">Google</a>.</li>
                <li>Faça login com sua conta do Google.</li>
            </ul>
        </li>
        <li><strong>Navegue até as Configurações de Segurança:</strong>
            <ul>
                <li>Clique na sua foto de perfil no canto superior direito e selecione "Gerenciar sua Conta do Google".</li>
                <li>No menu lateral esquerdo, clique em "Segurança".</li>
            </ul>
        </li>
        <li><strong>Ative a verificação em duas etapas:</strong>
            <ul>
                <li>Em "Como fazer login no Google", clique em "Verificação em duas etapas" e siga as instruções para ativá-la.</li>
            </ul>
        </li>
        <li><strong>Crie uma senha de aplicativo:</strong>
            <ul>
                <li>Após ativar a verificação em duas etapas, vá para a seção "Senhas de app".</li>
                <li>Selecione "Correio" e "Computador Windows" (ou outro dispositivo se preferir) e clique em "Gerar".</li>
                <li>Uma senha será gerada. Copie essa senha.</li>
            </ul>
        </li>
        <li><strong>Adicionar as credenciais ao arquivo <code>.env</code>:</strong>
            <ul>
                <li>No arquivo <code>.env</code>, defina <code>MAIL_USERNAME='usuario@gmail.com'</code> e <code>MAIL_PASSWORD='senha_gerada'</code>.</li>
            </ul>
        </li>
    </ol>

    <h2>Acessar a Aplicação</h2>

    <ol>
        <li><strong>Acesse o navegador:</strong>
            <ul>
                <li>Abra o navegador e acesse:
                    <pre><code>plaintext
http://localhost:8077
</code></pre>
                </li>
                <li>Ou, se estiver acessando de outro dispositivo na mesma rede, substitua <code>localhost</code> pelo endereço IP do servidor.
                    <pre><code>plaintext
http://172.20.10.37:8077/
</code></pre>
                </li>
            </ul>
        </li>
    </ol>

    <h2>Documentação Swagger</h2>

    <ul>
        <li>Para acessar a documentação Swagger da API, abra o navegador e acesse:
            <pre><code>plaintext
http://localhost:8077/api/docs
</code></pre>
        </li>
        <li>Ou, se estiver acessando de outro dispositivo na mesma rede, substitua <code>localhost</code> pelo endereço IP do servidor.
            <pre><code>plaintext
http://172.20.10.37:8077/api/docs
</code></pre>
        </li>
    </ul>

    <p>Seguindo essas instruções, você deverá ser capaz de rodar a aplicação Laravel usando Docker e Docker Compose e acessar a documentação da API gerada pelo Swagger.</p>
</body>

</html>