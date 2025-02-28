
# Snowflake Symfony Application

A dockerized Symfony application implementing Twitter's Snowflake ID algorithm with PostgreSQL and RabbitMQ.

## Features

-   Distributed unique 64-bit ID generation (timestamp + node ID + sequence)
-   Complete Symfony bundle with Doctrine integration
-   RESTful API and asynchronous processing via RabbitMQ
-   Production-ready Docker environment

## Security Features

- Isolated network segments (frontend/backend networks)
- Non-root users in containers
- Environment variable separation
- Secure PHP and Nginx configurations
- Container health checks
- Docker secrets support (in production)

## Getting Started

### Prerequisites

- Docker and Docker Compose installed
- Git

## Quick Start

```bash
# Clone and setup
git clone https://github.com/nabeeltahir785/symfony-snowflake-twitter-poc
cd symfony-snowflake-twitter-poc
mkdir -p docker/nginx/ssl docker/postgres/init var/log var/cache
cp .env.docker .env.docker.local

# Start application
make up

```

The application runs at http://localhost with PostgreSQL at localhost:5432 and RabbitMQ UI at http://localhost:15672.

## Common Commands

```bash
make up              # Start containers
make down            # Stop containers
make build           # Rebuild containers
make logs            # View logs
make shell           # Access PHP container
make db-create       # Create database
make db-migrate      # Run migrations
make console c=CMD   # Run Symfony console command

```

## Kubernetes Deployment

```bash
# Using Helm
helm upgrade --install snowflake-poc ./helm/snowflake-poc --namespace snowflake-poc --create-namespace

# Scale replicas
kubectl scale --namespace snowflake-poc statefulset/snowflake-poc --replicas=5

```


## Production Deployment

For production:

1. Use Docker Swarm or Kubernetes for orchestration
2. Enable HTTPS in Nginx configuration
3. Use Docker secrets for sensitive data
4. Set `APP_ENV=prod` in environment
5. Limit exposed ports using `127.0.0.1:` prefix
6. Configure log shipping to a centralized log system

## Troubleshooting

If you encounter connection issues:

1. Ensure all containers are running:
   ```bash
   docker-compose ps
   ```

2. Check container logs:
   ```bash
   docker-compose logs php
   docker-compose logs postgres
   docker-compose logs rabbitmq
   ```

3. Verify PostgreSQL connection:
   ```bash
   docker-compose exec postgres psql -U symfony -d snowflake_db
   ```

4. Check network connectivity:
   ```bash
   docker-compose exec php ping postgres
   docker-compose exec php ping rabbitmq
   ```
   # Snowflake ID Generator - Symfony PoC

This repository demonstrates a complete implementation of the Snowflake ID algorithm in a Symfony application with PostgreSQL and RabbitMQ. It provides a mature, production-ready architecture for generating distributed unique IDs in a microservices environment.

## What is Snowflake?

Snowflake is an ID generation algorithm originally developed at Twitter. It generates unique 64-bit IDs composed of:

-   **Timestamp** (41 bits): Milliseconds since a custom epoch
-   **Node ID** (10 bits): A unique identifier for the server/process
-   **Sequence** (12 bits): A counter that resets every millisecond

This structure allows for:

-    Time-sortable IDs
-    No database coordination needed
-    High throughput (4,096 IDs per millisecond per node)
-    Distribution across up to 1,024 nodes

## Features

- **Complete Snowflake Implementation**: A full PHP implementation of the Snowflake algorithm
- **Symfony Bundle**: Packaged as a reusable bundle with dependency injection
- **Doctrine Integration**: Custom type and event subscriber for automatic ID generation
- **RESTful API**: Product management API showcasing Snowflake IDs in practice
- **Console Commands**: CLI tools for generating and analyzing Snowflake IDs
- **Distributed Architecture**: Docker-based setup with proper networking and security
- **Performance Optimized**: Efficient ID generation with benchmarking tools