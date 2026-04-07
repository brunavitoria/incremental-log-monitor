# Incremental Log Monitor

Projeto desenvolvido em **Laravel 13 + MySQL 8**, com ambiente **Dockerizado**, para realizar o **processamento incremental de logs NDJSON** do API Gateway e a **geração de relatórios CSV** a partir dos dados persistidos.

---

## 📌 Objetivo do projeto

O sistema lê arquivos de log em formato `NDJSON`, extrai os campos relevantes de cada linha, armazena essas informações no banco de dados e mantém o controle da **última linha processada** para evitar reprocessamento em execuções futuras.

Além da importação incremental, o projeto também gera relatórios CSV com base nos dados salvos, permitindo análises por:

- **consumidor**
- **serviço**
- **latências médias por serviço**

---

## ✅ Funcionalidades implementadas

- Processamento incremental de logs via comando Artisan
- Persistência dos dados relevantes em banco MySQL
- Controle de progresso por arquivo em `processing_states`
- Geração de relatórios CSV em `storage/app/reports`
- Testes automatizados para processamento e relatórios
- Ambiente padronizado com `Docker + Nginx + PHP-FPM + MySQL`

---

## 🧱 Stack utilizada

| Camada | Tecnologia |
|---|---|
| Backend | `PHP 8.4` / `Laravel 13` |
| Banco de dados | `MySQL 8` |
| Servidor web | `Nginx` |
| Containerização | `Docker` / `Docker Compose` |
| Relatórios CSV | `league/csv` |
| Testes | `PHPUnit` |

---

## 🏗️ Resumo da arquitetura

O fluxo principal da aplicação é:

1. um arquivo `.log` / `.txt` em formato `NDJSON` é informado ao comando de importação;
2. o sistema lê o arquivo **linha a linha**, sem carregar tudo em memória;
3. cada entrada válida é normalizada e persistida na tabela `logs`;
4. a tabela `processing_states` registra a última linha já processada daquele arquivo;
5. comandos específicos geram os relatórios CSV a partir do banco.

### Estruturas principais

- `logs`: armazena consumidor, serviço, latências e timestamps
- `processing_states`: controla o progresso incremental por arquivo
- `app/Console/Commands`: comandos de processamento e geração de relatórios
- `app/Services/CsvReportService.php`: serviço responsável pela exportação dos CSVs

---

## 📂 Estrutura relevante do projeto

```text
app/
 ├── Console/Commands/
 │    ├── IncrementalLogProcessing.php
 │    ├── GenerateConsumersReport.php
 │    ├── GenerateServicesReport.php
 │    └── GenerateLatenciesReport.php
 ├── Models/
 │    ├── Log.php
 │    └── ProcessingState.php
 └── Services/
      └── CsvReportService.php

database/migrations/
tests/Feature/
storage/app/reports/
```

---

## ⚙️ Pré-requisitos

Antes de iniciar, é necessário ter instalado:

- `Docker`
- `Docker Compose`
- `Git`

---

## 🚀 Como executar o projeto

### 1. Clonar o repositório

```bash
git clone https://github.com/brunavitoria/incremental-log-monitor.git
cd incremental-log-monitor
```

### 2. Setup automático com script

A forma recomendada de inicializar o ambiente é usando o arquivo `setup.sh`:

```bash
chmod +x setup.sh
./setup.sh
```

Esse script executa automaticamente:

- criação do arquivo `.env` a partir do `.env.example`
- subida dos containers com `docker compose up -d --build`
- espera do MySQL ficar disponível
- instalação das dependências PHP com `composer install`
- geração da `APP_KEY`
- ajuste de permissões em `storage` e `bootstrap/cache`
- execução das migrations

### 3. Acessar a aplicação

Abra no navegador:

```text
http://localhost:8080
```

### 4. Setup manual (opcional)

Se preferir executar cada etapa manualmente:

```bash
cp .env.example .env
docker compose up -d --build
docker exec ilm-app php artisan key:generate --force
docker exec ilm-app chmod -R 777 storage bootstrap/cache
docker exec ilm-app php artisan migrate --force
```

> O projeto já está configurado para utilizar o serviço `db` do Docker como host do MySQL.

> A interface web está mínima, pois o foco desta entrega está no **processamento via comandos Artisan** e na **geração dos relatórios**.

---

## 📥 Processamento incremental dos logs

O comando principal de importação é:

```bash
docker exec ilm-app php artisan logs:processing {file_path}
```

### Exemplo prático

Se o arquivo de log estiver dentro do projeto, por exemplo em `storage/app/private/logs_sample_100.txt`:

```bash
docker exec ilm-app php artisan logs:processing storage/app/private/logs_sample_100.txt
```

### Comportamento esperado

- apenas **novas linhas** são processadas em execuções seguintes;
- linhas inválidas são ignoradas com aviso no terminal;
- o progresso do arquivo é salvo na tabela `processing_states`.

---

## 📊 Geração de relatórios CSV

Após importar os logs, os relatórios podem ser gerados pelos comandos abaixo.

### 1. Relatório por consumidor

```bash
docker exec ilm-app php artisan reports:consumers
```

Ou informando um nome de arquivo:

```bash
docker exec ilm-app php artisan reports:consumers consumers_report.csv
```

### 2. Relatório por serviço

```bash
docker exec ilm-app php artisan reports:services
```

### 3. Relatório de latências médias por serviço

```bash
docker exec ilm-app php artisan reports:latencies
```

### Local dos arquivos gerados

Todos os CSVs são salvos em:

```text
storage/app/reports/
```

---

## 🧪 Execução dos testes

Para rodar a suíte de testes automatizados:

```bash
docker exec ilm-app php artisan test
```

### Cobertura dos testes

**Processamento incremental (`IncrementalLogProcessingTest`)**

| Cenário | O que valida |
|---|---|
| Linhas válidas e inválidas | Apenas registros com todos os campos obrigatórios são persistidos |
| Processamento incremental | Segunda execução processa somente as linhas novas |
| JSON inválido | Linhas com JSON malformado, incompleto ou vazio são ignoradas |
| Arquivo vazio | Comando finaliza com sucesso sem inserir registros |
| Arquivo inexistente | Comando retorna falha com mensagem de erro |
| Reprocessamento sem novas linhas | Segunda execução não duplica registros |

**Relatórios CSV (`CsvReportCommandsTest`)**

| Cenário | O que valida |
|---|---|
| Relatório por consumidor | Header, quantidade de linhas e conteúdo exato de cada row |
| Relatório por serviço | Header, quantidade de linhas e conteúdo exato de cada row |
| Relatório de latências | Header, quantidade de linhas e médias calculadas corretamente |
| CSV sem dados | Arquivo gerado contém apenas o header quando não há logs |

---

## 🔍 Decisões técnicas adotadas

- **Leitura linha a linha** para reduzir consumo de memória em arquivos grandes
- **Persistência incremental** para evitar reprocessamento desnecessário
- **Separação por responsabilidades** entre comandos, models e service de relatório
- **Uso de timestamps de processamento** para auditabilidade
- **Índices no banco** para melhorar consultas analíticas por consumidor, serviço e data

---

## 📈 Possíveis evoluções

Como próximos passos, o projeto poderia evoluir com:

- dashboard web para visualização dos relatórios;
- filtros por período na exportação de CSV;
- processamento assíncrono com filas;
- observabilidade com métricas e logs estruturados;
- pipeline CI para testes automatizados em cada push.

---

## 🐳 Serviços Docker do ambiente

- `app`: container PHP-FPM com Laravel
- `web`: container Nginx exposto em `localhost:8080`
- `db`: container MySQL 8

---

## 👩‍💻 Observação final

Esta solução foi pensada para priorizar **legibilidade**, **manutenibilidade** e **aderência ao desafio técnico**, entregando uma base consistente para processamento incremental e futura expansão analítica.
