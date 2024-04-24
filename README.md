             _____  _____   _____   ____   _____   ______
           / ____||_   _| / ____| / __ \ |  __ \ |  ____|
          | (___    | |  | |     | |  | || |  | || |__
           \___ \   | |  | |     | |  | || |  | ||  __|
           ____) | _| |_ | |____ | |__| || |__| || |____
          |_____/ |_____| \_____| \____/ |_____/ |______|TM

# SICODE - Sistema de Controle de Demandas

SICODE é um sistema web para controle de demandas, projetado para ajudar equipes a gerenciar tarefas, atribuir responsabilidades e acompanhar o progresso de projetos de forma eficiente.

## Funcionalidades Principais

-   Cadastro de demandas: Registre novas demandas, atribua prioridades e defina prazos.
-   Atribuição de tarefas: Atribua tarefas específicas a membros da equipe e acompanhe o status de cada uma.
-   Dashboard: Visualize de forma rápida e clara o andamento das demandas, tarefas pendentes e concluídas.
-   Comentários e interações: Colabore com a equipe através de comentários nas demandas, mantendo todos atualizados.
-   Notificações: Receba notificações em tempo real sobre atualizações nas demandas e tarefas.

## Como Usar

1. Faça o download ou clone o repositório para o seu ambiente local.
2. Instale as dependências do projeto utilizando o comando `composer install`.
3. Copie o arquivo `.env.example` para `.env` e configure as variáveis de ambiente, incluindo a conexão com o banco de dados.
4. Gere uma nova chave de aplicativo com o comando `php artisan key:generate`.
5. Execute as migrações do banco de dados com o comando `php artisan migrate` para criar as tabelas necessárias.
6. Inicie o servidor de desenvolvimento com o comando `php artisan serve`.
7. Acesse a aplicação em seu navegador usando o endereço `http://localhost:8000`.

## Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para enviar sugestões, correções de bugs ou novas funcionalidades através de pull requests.

## Licença

Este projeto está licenciado sob a [Licença MIT](https://opensource.org/licenses/MIT) - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

# SICODE - Sistema de Controle de Demandas

SICODE is a web-based system for demand management, designed to help teams manage tasks, assign responsibilities, and track project progress efficiently.

## Main Features

-   Demand registration: Register new demands, assign priorities, and set deadlines.
-   Task assignment: Assign specific tasks to team members and track the status of each.
-   Dashboard: Quickly and clearly visualize the progress of demands, pending and completed tasks.
-   Comments and interactions: Collaborate with the team through comments on demands, keeping everyone updated.
-   Notifications: Receive real-time notifications about updates on demands and tasks.

## How to Use

1. Download or clone the repository to your local environment.
2. Install project dependencies using the command `composer install`.
3. Copy the `.env.example` file to `.env` and configure the environment variables, including the database connection.
4. Generate a new application key with the command `php artisan key:generate`.
5. Run database migrations with the command `php artisan migrate` to create the necessary tables.
6. Start the development server with the command `php artisan serve`.
7. Access the application in your browser using the address `http://localhost:8000`.

## Contributions

Contributions are welcome! Feel free to submit suggestions, bug fixes, or new features through pull requests.

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT) - see the [LICENSE](LICENSE) file for more details.
