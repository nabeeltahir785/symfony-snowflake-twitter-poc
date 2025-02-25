
# Snowflake Symfony Application

This repository contains a dockerized Symfony application with PostgreSQL and RabbitMQ.

## Architecture

- **PHP/Symfony**: Backend application framework
- **Nginx**: Web server
- **PostgreSQL**: Database
- **RabbitMQ**: Message queue for async processing

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

### Setup

1. Clone this repository:
   ```bash
   git clone <repository-url>
   cd snowflake-poc
   ```

2. Create necessary directories:
   ```bash
   mkdir -p docker/nginx/ssl docker/postgres/init var/log var/cache
   ```

3. Copy environment file:
   ```bash
   cp .env.docker .env.docker.local
   ```

4. Start the application:
   ```bash
   make up
   ```

## Usage

The application is now running at http://localhost

* PostgreSQL is available at localhost:5432
* RabbitMQ management UI is at http://localhost:15672

## Common Commands

Using the Makefile for common operations:

```bash
# Start containers
make up

# Stop containers
make down

# Rebuild containers
make build

# View logs
make logs

# Access PHP container shell
make shell

# Create database
make db-create

# Run migrations
make db-migrate

# Clear cache
make cache-clear

# Run Symfony console commands
make console c=cache:clear
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


I've completed a comprehensive Symfony Proof of Concept that implements the Snowflake ID generator algorithm. Let me summarize what we've built:

## Core Snowflake Implementation

1.  **SnowflakeIdGenerator Service**: A complete PHP implementation of Twitter's Snowflake algorithm that generates 64-bit unique IDs composed of timestamp, node ID, and sequence components.
2.  **Snowflake Bundle**: A reusable Symfony bundle that makes it easy to integrate Snowflake IDs into any Symfony project, with proper dependency injection and configuration.
3.  **Doctrine Integration**: Custom DBAL type (SnowflakeType) and event subscribers to automatically generate Snowflake IDs for Doctrine entities.

## API and Functionality

1.  **RESTful Product API**: A complete API for product management that uses Snowflake IDs for primary keys.
2.  **Asynchronous Processing**: Integration with RabbitMQ for message-based asynchronous processing with Snowflake IDs.
3.  **Console Commands**: Utilities for generating, analyzing, and benchmarking Snowflake IDs from the command line.

## Infrastructure and Best Practices

1.  **Docker Setup**: A secure, production-ready Docker environment with proper network isolation and security configurations.
2.  **High Availability Design**: Guidelines for deploying Snowflake ID generation in distributed environments with proper node ID allocation.
3.  **Documentation**: Comprehensive documentation including API reference, deployment guides, and troubleshooting information.

## Key Benefits of Snowflake IDs in This Implementation

1.  **Distributed Generation**: No central coordination needed between nodes/servers
2.  **Time-Sortable**: IDs are approximately sortable by creation time
3.  **High Throughput**: Can generate thousands of IDs per second per node
4.  **No Database Sequence**: Eliminates the performance bottleneck of DB sequences
5.  **K-Sortable**: IDs from the same timeframe sort near each other

This implementation is production-ready and follows best practices for Symfony development, providing a solid foundation for scaling distributed systems with reliable ID generation.



## Kubernetes Deployment

The Snowflake ID Generator is designed for distributed deployment using Kubernetes:

### Setup Options

1. **Helm Chart**: Complete Helm chart for deploying the application, PostgreSQL, and RabbitMQ
   ```bash
   helm upgrade --install snowflake-poc ./helm/snowflake-poc \
     --namespace snowflake-poc \
     --create-namespace
   ```

2. **Skaffold**: Development workflow for iterating on the application
   ```bash
   skaffold dev --profile=dev
   ```

3. **Kustomize**: Environment-specific configuration
   ```bash
   kubectl apply -k kubernetes/
   ```

### Multi-Node Distribution

The Kubernetes deployment uses a StatefulSet to ensure consistent pod identities:

- Each pod receives a unique Snowflake node ID (via the `SNOWFLAKE_NODE_ID` environment variable)
- Node IDs are calculated from the pod's ordinal index + a configurable base ID
- This ensures unique IDs across distributed deployments

### Scaling

```bash
# Scale to 5 replicas
kubectl scale --namespace snowflake-poc statefulset/snowflake-poc --replicas=5

# Or use Horizontal Pod Autoscaler (already configured)
kubectl get hpa --namespace snowflake-poc
```

### Testing Cross-Node Distribution

A test script is provided to validate ID uniqueness across pods:

```bash
kubernetes/test-distribution.sh
```

For more details, see the [Kubernetes README](kubernetes/README.md).