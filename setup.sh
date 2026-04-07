#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

echo "🚀 Iniciando setup do projeto Incremental Log Monitor..."

if ! command -v docker >/dev/null 2>&1; then
    echo "❌ Docker não encontrado. Instale o Docker antes de continuar."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "❌ Docker Compose não está disponível."
    exit 1
fi

if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ Arquivo .env criado a partir do .env.example"
    else
        echo "❌ Arquivo .env.example não encontrado."
        exit 1
    fi
fi

set -a
source .env
set +a

echo "🐳 Subindo os containers..."
docker compose up -d --build

echo "⏳ Aguardando o MySQL ficar disponível..."
for i in {1..30}; do
    if docker compose exec -T db mysqladmin ping -h localhost -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; then
        echo "✅ MySQL está pronto"
        break
    fi

    if [ "$i" -eq 30 ]; then
        echo "❌ O MySQL não ficou disponível a tempo."
        exit 1
    fi

    sleep 2
done

echo "📦 Instalando dependências PHP..."
docker compose exec -T app composer install --no-interaction --prefer-dist

echo "🔑 Gerando APP_KEY..."
docker compose exec -T app php artisan key:generate --force

echo "🛠️ Ajustando permissões do Laravel..."
docker compose exec -T app chmod -R 777 storage bootstrap/cache

echo "🗄️ Executando migrations..."
docker compose exec -T app php artisan migrate --force

echo "\n✅ Setup inicial concluído com sucesso!"
echo "🌐 Acesse: http://localhost:8080"
echo "▶️ Para executar novamente: ./setup.sh"
