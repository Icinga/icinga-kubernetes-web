# Icinga for Kubernetes Web

Icinga for Kubernetes is a set of components for monitoring and visualizing Kubernetes resources,
consisting of

* the [Icinga for Kubernetes daemon](https://icinga.com/docs/icinga-kubernetes),
  which uses the Kubernetes API to monitor the configuration and
  status changes of Kubernetes resources synchronizing every change in a database, and
* Icinga for Kubernetes Web, which connects to the database for visualizing Kubernetes resources and their state.

![Icinga for Kubernetes Overview](res/icinga-kubernetes-overview.png)

Any of the Icinga for Kubernetes components can run either inside or outside Kubernetes clusters,
including the database.
At the moment it is only possible to monitor one Kubernetes cluster per Icinga for Kubernetes installation.

![Icinga Kubernetes Web Deployment](res/icinga-kubernetes-web-deployment.png)
![Icinga Kubernetes Web Replica Set](res/icinga-kubernetes-web-replica-set.png)
![Icinga Kubernetes Web Pod](res/icinga-kubernetes-web-pod.png)

## Installation

To install Icinga for Kubernetes Web see [Installation](02-Installation.md).

## License

Icinga for Kubernetes Web and the Icinga for Kubernetes Web documentation are licensed under the terms of the
GNU Affero General Public License Version 3.
