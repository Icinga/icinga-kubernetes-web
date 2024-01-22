# Icinga Kubernetes Web

Icinga Kubernetes is a set of components for monitoring and visualizing Kubernetes resources,
consisting of

* the [Icinga Kubernetes daemon](https://icinga.com/docs/icinga-kubernetes),
  which uses the Kubernetes API to monitor the configuration and
  status changes of Kubernetes resources synchronizing every change in a database, and
* Icinga Kubernetes Web, which connects to the database for visualizing Kubernetes resources and their state.

![Icinga Kubernetes Overview](res/icinga-kubernetes-overview.png)

Any of the Icinga Kubernetes components can run either inside or outside Kubernetes clusters,
including the database.
At the moment it is only possible to monitor one Kubernetes cluster per Icinga Kubernetes installation.

## Installation

To install Icinga Kubernetes Web see [Installation](02-Installation.md).

## License

Icinga Kubernetes Web and the Icinga Kubernetes Web documentation are licensed under the terms of the
GNU General Public License Version 2.
