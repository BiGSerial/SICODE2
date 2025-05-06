```
         _____  _____   _____   ____   _____   ______
       / ____||_   _| / ____| / __ \ |  __ \ |  ____|
      | (___    | |  | |     | |  | || |  | || |__  
       \___ \   | |  | |     | |  | || |  | ||  __|
      ____) | _| |_ | |____ | |__| || |__| || |____
      |_____/ |_____| \_____| \____/ |_____/ |______|TM
```

[![Versão](https://img.shields.io/github/v/release/BiGSerial/SICODE2?label=vers%C3%A3o)](https://github.com/BiGSerial/SICODE2/releases)

# SICODE - Sistema de Controle de Demandas

## Índice

* [Descrição](#descrição)
* [Funcionalidades Principais](#funcionalidades-principais)
* [Como Usar](#como-usar)
* [Contribuições](#contribuições)
* [Licença](#licença)

---

## Descrição

SICODE é um sistema web para controle de demandas, projetado para ajudar equipes a gerenciar tarefas, atribuir responsabilidades e acompanhar o progresso de projetos de forma eficiente.

## Funcionalidades Principais

* **Cadastro de demandas**: Registre novas demandas, atribua prioridades e defina prazos.
* **Atribuição de tarefas**: Atribua tarefas a membros da equipe e acompanhe o status de cada uma.
* **Dashboard**: Visualize o andamento das demandas, tarefas pendentes e concluídas.
* **Comentários e interações**: Colabore com a equipe através de comentários nas demandas.
* **Notificações em tempo real**: Receba alertas sobre atualizações nas demandas.

## Como Usar

1. Clone este repositório:

   ```bash
   git clone https://github.com/BiGSerial/SICODE2.git
   ```
2. Acesse a pasta do projeto:

   ```bash
   cd SICODE2
   ```
3. Instale as dependências com Composer:

   ```bash
   composer install
   ```
4. Copie o `.env.example` para `.env` e configure sua conexão de banco de dados:

   ```bash
   cp .env.example .env
   ```
5. Gere a chave da aplicação:

   ```bash
   php artisan key:generate
   ```
6. Rode as migrações para criar as tabelas:

   ```bash
   php artisan migrate
   ```
7. Inicie o servidor de desenvolvimento:

   ```bash
   php artisan serve
   ```
8. Acesse a aplicação em `http://localhost:8000`.

## Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para enviar issues, pull requests ou sugestões.

## Licença

Este projeto está licenciado sob a [Licença MIT](https://opensource.org/licenses/MIT).

---

# SICODE - Demand Control System (English)

## Overview

SICODE is a web-based demand control system designed to help teams manage tasks, assign responsibilities, and track project progress efficiently.

## Main Features

* **Demand Registration**: Record new demands, set priorities, and deadlines.
* **Task Assignment**: Assign tasks to team members and monitor progress.
* **Dashboard**: Get an overview of pending and completed tasks.
* **Comments & Collaboration**: Communicate within the team on each demand.
* **Real-Time Notifications**: Stay informed about demand updates.

## Getting Started

1. Clone the repository:

   ```bash
   git clone https://github.com/BiGSerial/SICODE2.git
   ```
2. Navigate to the project folder:

   ```bash
   cd SICODE2
   ```
3. Install dependencies via Composer:

   ```bash
   composer install
   ```
4. Copy and configure the environment file:

   ```bash
   cp .env.example .env
   ```
5. Generate an application key:

   ```bash
   php artisan key:generate
   ```
6. Run database migrations:

   ```bash
   php artisan migrate
   ```
7. Start the development server:

   ```bash
   php artisan serve
   ```
8. Open your browser at `http://localhost:8000`.

## Contributing

Contributions are welcome! Please open issues or pull requests for enhancements and bug fixes.

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
