# Icinga for Kubernetes Web

![Build Status](https://github.com/Icinga/icinga-kubernetes-web/actions/workflows/php.yml/badge.svg?branch=main)
[![Github Tag](https://img.shields.io/github/tag/Icinga/icinga-kubernetes-web.svg)](https://github.com/Icinga/icinga-kubernetes-web/releases/latest)

Icinga for Kubernetes is a set of components for monitoring and visualizing Kubernetes resources,
consisting of

* the [Icinga for Kubernetes daemon](https://github.com/Icinga/icinga-kubernetes),
  which uses the Kubernetes API to monitor the configuration and
  status changes of Kubernetes resources synchronizing every change in a database, and
* Icinga for Kubernetes Web, which connects to the database for visualizing Kubernetes resources and their state.

![Icinga for Kubernetes Overview](doc/res/icinga-kubernetes-overview.png)

Any of the Icinga for Kubernetes components can run either inside or outside Kubernetes clusters,
including the database.
At the moment it is only possible to monitor one Kubernetes cluster per Icinga for Kubernetes installation.

![Icinga Kubernetes Web Stateful Set](doc/res/icinga-kubernetes-web-stateful-set.png)
![Icinga Kubernetes Web Service](doc/res/icinga-kubernetes-web-service.png)
![Icinga Kubernetes Web Pod](doc/res/icinga-kubernetes-web-pod.png)

## Documentation

Icinga for Kubernetes Web documentation is available at [icinga.com/docs](https://icinga.com/docs/icinga-kubernetes-web).

## License

Icinga for Kubernetes Web and the Icinga for Kubernetes Web documentation are licensed under the terms of the
[GNU Affero General Public License Version 3](LICENSE).
