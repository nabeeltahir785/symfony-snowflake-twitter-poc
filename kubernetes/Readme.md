
# Kubernetes Deployment for Snowflake ID Generator

This directory contains the Kubernetes configuration for deploying the Snowflake ID Generator Symfony application to a Kubernetes cluster.

## Architecture

The Kubernetes deployment uses a StatefulSet to ensure consistent pod identity, critical for maintaining unique Snowflake node IDs. Each pod gets a unique node ID based on its ordinal index in the StatefulSet.

### Components

- **StatefulSet**: Ensures stable, unique network identifiers and stable persistent storage
- **Service**: Exposes the application within the cluster
- **Ingress**: Exposes the application externally
- **ConfigMap**: Stores configuration data
- **HPA**: Horizontal Pod Autoscaler for scaling
- **Helm Chart**: Package manager for Kubernetes resources
- **Skaffold**: For local development and continuous deployment

## Snowflake ID Distribution

The most critical aspect of this deployment is ensuring that each pod (node) generates unique Snowflake IDs:

1. Each pod in the StatefulSet has a stable identity (e.g., `snowflake-poc-0`, `snowflake-poc-1`, etc.)
2. The pod's ordinal index is extracted from its hostname
3. This index is added to a base node ID (configurable via `app.snowflakeBaseNodeId` value)
4. This ensures each pod has a unique node ID component in the Snowflake algorithm

For example, with a base node ID of 100:
- Pod `snowflake-poc-0` uses node ID 100
- Pod `snowflake-poc-1` uses node ID 101
- Pod `snowflake-poc-2` uses node ID 102

## Deployment Options

### Helm

```bash
# Add required Helm repositories
helm repo add bitnami https://charts.bitnami.com/bitnami

# Deploy using Helm
helm upgrade --install snowflake-poc ./helm/snowflake-poc \
  --namespace snowflake-poc \
  --create-namespace \
  --set image.repository=your-registry/snowflake-poc \
  --set image.tag=latest
```

### Kustomize

```bash
# Deploy development environment
kubectl apply -k kubernetes/

# Deploy production environment
kubectl apply -k kubernetes/overlays/production/
```

### Skaffold

```bash
# Development with hot reload
skaffold dev --profile=dev

# Deploy to production
skaffold run --profile=prod
```

## Testing Across Multiple Nodes

To validate that Snowflake IDs are distributed properly across pods:

```bash
# Run the test script
kubernetes/test-distribution.sh

# Or use the Makefile command
make k8s-test-distribution
```

This will generate IDs on each pod and verify that:
1. No duplicate IDs are generated
2. Each pod is using the correct node ID in the generated Snowflake IDs
3. The sequence numbers are distributed properly

## Cross-Cluster Testing

For testing across multiple Kubernetes clusters:

1. Deploy the application to multiple clusters
2. Ensure each cluster uses a different base node ID range:
    - Cluster 1: `app.snowflakeBaseNodeId: 100`
    - Cluster 2: `app.snowflakeBaseNodeId: 200`
    - Cluster 3: `app.snowflakeBaseNodeId: 300`

3. Set up RabbitMQ clustering across clusters using a federated exchange:

```bash
# Enable federation plugin on all RabbitMQ clusters
kubectl exec -it -n snowflake-poc snowflake-poc-rabbitmq-0 -- rabbitmq-plugins enable rabbitmq_federation

# Set up upstream links between clusters
kubectl exec -it -n snowflake-poc snowflake-poc-rabbitmq-0 -- rabbitmqctl set_parameter federation-upstream cluster2 \
  '{"uri":"amqp://guest:guest@cluster2-rabbitmq.snowflake-poc.svc.cluster.local:5672"}'

# Set up federation policy
kubectl exec -it -n snowflake-poc snowflake-poc-rabbitmq-0 -- rabbitmqctl set_policy \
  --apply-to exchanges federate-messages "^messages$" \
  '{"federation-upstream":"cluster2"}'
```

## Monitoring

The deployment includes Prometheus metrics for:
- Application performance
- PostgreSQL database
- RabbitMQ

To access the metrics:

```bash
# Port-forward Prometheus metrics
kubectl port-forward svc/snowflake-poc-metrics 9090:9090
```

## Troubleshooting

### Pod Startup Issues

If pods are failing to start:

```bash
# Check pod status
kubectl get pods -n snowflake-poc

# Check pod logs
kubectl logs -n snowflake-poc snowflake-poc-0 -c php
```

### Database Connection Issues

```bash
# Check PostgreSQL pod status
kubectl get pods -n snowflake-poc -l app.kubernetes.io/name=postgresql

# Check PostgreSQL logs
kubectl logs -n snowflake-poc snowflake-poc-postgresql-0
```

### RabbitMQ Connection Issues

```bash
# Check RabbitMQ cluster status
kubectl exec -it -n snowflake-poc snowflake-poc-rabbitmq-0 -- rabbitmqctl cluster_status

# Check RabbitMQ logs
kubectl logs -n snowflake-poc snowflake-poc-rabbitmq-0
```