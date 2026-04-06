# Incremental Log Monitor

Sistema desenvolvido em `Laravel + MySQL` para processamento incremental de logs do API Gateway e futura geração de relatórios em CSV, conforme os requisitos do teste técnico.

## 📌 Descrição inicial

O projeto tem como objetivo ler arquivos de log no formato `NDJSON`, persistir os dados relevantes em banco de dados com rastreabilidade temporal e servir de base para exportação de relatórios analíticos.

O ambiente da aplicação foi estruturado com Docker, PHP 8.4, Nginx e MySQL 8, garantindo padronização na execução e facilidade na configuração local.

## ✅ Requisitos para execução

Antes de iniciar, é necessário ter instalado na máquina:

- `Docker`
- `Docker Compose`
- `Git`

## 🚀 Passos para instalação e execução

### 1. Clonar o repositório

```text
git clone https://github.com/brunavitoria/incremental-log-monitor.git
cd incremental-log-monitor
```

### 2. Configurar o arquivo de ambiente

Copie o arquivo de exemplo:

```text
cp .env.example .env
```

> O projeto já está preparado para rodar com MySQL em Docker usando o host `db`.

### 3. Subir os containers

```text
docker compose up -d --build
```

### 4. Gerar a chave da aplicação

```text
docker exec ilm-app php artisan key:generate --force
```

### 5. Ajustar permissões das pastas do Laravel

```text
docker exec ilm-app chmod -R 777 storage bootstrap/cache
```

### 6. Executar as migrations

```text
docker exec ilm-app php artisan migrate --force
```

### 7. Acessar a aplicação

Abra no navegador:

```text
http://localhost:8080
```

## 🐳 Estrutura inicial do ambiente

- `app`: container PHP 8.4 com Laravel
- `web`: container Nginx
- `db`: container MySQL 8
