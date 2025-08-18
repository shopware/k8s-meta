# k8s-meta: a Symfony Kubernetes Recipe for Shopware

k8s-meta provides recipes specifically designed to deploy and manage Shopware applications based on the Symfony framework within Kubernetes environments. 

## Features

- Kubernetes manifests and configuration tailored for Shopware applications built on Symfony
- Recipes for handling Shopware container deployments, scaling, and configuration
- Easy integration with Shopware [Helm charts](https://github.com/shopware/helm-charts) and the [Shopware Operator](https://github.com/shopware/shopware-operator) for managing lifecycle
- Support for popular Kubernetes ecosystems and tools such as Helm and Traefik

## Getting Started

### Prerequisites

- Kubernetes cluster (version 1.19 or later recommended)
- Helm 3+ package manager
- Docker for image building
- Optional: Kind for local Kubernetes cluster testing

### Installation

1. Clone the repository:

```bash
git clone https://github.com/shopware/k8s-meta.git
cd k8s-meta
```

2. Build your Shopware Docker image (example):

```bash
docker build -t your-shopware-image -f docker/Dockerfile .
```

3. Load the image into your local Kubernetes cluster (e.g., Kind):

```bash
kind load docker-image your-shopware-image
```

4. Use the Helm charts from the Shopware project to deploy Shopware in your cluster:

```bash
helm repo add shopware https://shopware.github.io/helm-charts/
helm install shopware shopware/shopware --namespace shopware --create-namespace
```

Refer to the Shopware Helm chart documentation for full installation, configuration, and upgrade instructions.

## Documentation & Resources

- [Shopware Developer Docs](https://developer.shopware.com)

## License

Shopware 6 is completely free and released under the MIT License.

## Contributing

Contributions are welcome! Please follow the standard GitHub flow:

- Fork the repository
- Create a feature branch
- Open a pull request with detailed description of changes