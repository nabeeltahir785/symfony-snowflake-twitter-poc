# Kubernetes commands
k8s-build:
	docker build -f kubernetes/Dockerfile -t $(DOCKER_REGISTRY)/snowflake-poc:latest .
	docker push $(DOCKER_REGISTRY)/snowflake-poc:latest

k8s-deploy:
	helm upgrade --install snowflake-poc ./helm/snowflake-poc \
		--namespace snowflake-poc \
		--create-namespace \
		--set image.repository=$(DOCKER_REGISTRY)/snowflake-poc \
		--set image.tag=latest

k8s-delete:
	helm delete snowflake-poc --namespace snowflake-poc

k8s-status:
	kubectl get all --namespace snowflake-poc

k8s-logs:
	kubectl logs -f --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -c php

k8s-shell:
	kubectl exec -it --namespace snowflake-poc $(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[0].metadata.name}") -c php -- sh

k8s-exec:
	kubectl exec -it --namespace snowflake-poc $(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[0].metadata.name}") -c php -- $(c)

k8s-console:
	kubectl exec -it --namespace snowflake-poc $(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[0].metadata.name}") -c php -- bin/console $(c)

k8s-snowflake-info:
	kubectl exec -it --namespace snowflake-poc $(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[0].metadata.name}") -c php -- bin/console app:snowflake:info

k8s-scale:
	kubectl scale --namespace snowflake-poc statefulset/snowflake-poc --replicas=$(replicas)

k8s-port-forward:
	kubectl port-forward --namespace snowflake-poc svc/snowflake-poc 8080:80

k8s-port-forward-db:
	kubectl port-forward --namespace snowflake-poc svc/snowflake-poc-postgresql 5432:5432

k8s-port-forward-rabbitmq:
	kubectl port-forward --namespace snowflake-poc svc/snowflake-poc-rabbitmq 15672:15672 5672:5672

k8s-test-distribution:
	@echo "Testing Snowflake ID distribution across pods..."
	@for i in $(seq 1 5); do \
		pod=$(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[$i-1].metadata.name}"); \
		echo "Pod: $pod"; \
		kubectl exec -it --namespace snowflake-poc $pod -c php -- bin/console app:snowflake:info; \
		echo ""; \
	done

k8s-benchmark:
	@echo "Running Snowflake benchmark across all pods..."
	@for i in $(seq 1 $(kubectl get statefulset --namespace snowflake-poc snowflake-poc -o jsonpath="{.spec.replicas}")); do \
		pod=$(kubectl get pod --namespace snowflake-poc --selector app.kubernetes.io/name=snowflake-poc -o jsonpath="{.items[$i-1].metadata.name}"); \
		echo "Pod: $pod"; \
		kubectl exec --namespace snowflake-poc $pod -c php -- bin/console app:snowflake:info --benchmark; \
		echo ""; \
	done