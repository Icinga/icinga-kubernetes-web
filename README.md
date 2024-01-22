# Icinga Kubernetes Web

[![PHP Support](https://img.shields.io/badge/php-%3E%3D%207.2-777BB4?logo=PHP)](https://php.net/)
![Build Status](https://github.com/Icinga/icinga-kubernetes-web/actions/workflows/php.yml/badge.svg?branch=main)
[![Github Tag](https://img.shields.io/github/tag/Icinga/icinga-kubernetes-web.svg)](https://github.com/Icinga/icinga-kubernetes-web/releases/latest)

Icinga Kubernetes is a set of components for monitoring and visualizing Kubernetes resources,
consisting of

* the [Icinga Kubernetes daemon](https://icinga.com/docs/icinga-kubernetes),
  which uses the Kubernetes API to monitor the configuration and
  status changes of Kubernetes resources synchronizing every change in a database, and
* Icinga Kubernetes Web, which connects to the database for visualizing Kubernetes resources and their state.

![Icinga Kubernetes Overview](doc/res/icinga-kubernetes-overview.png)

Any of the Icinga Kubernetes components can run either inside or outside Kubernetes clusters,
including the database.
At the moment it is only possible to monitor one Kubernetes cluster per Icinga Kubernetes installation.

## Documentation

Icinga Kubernetes Web documentation is available at [icinga.com/docs](https://icinga.com/docs/icinga-kubernetes-web).

## License

Icinga Kubernetes Web and the Icinga Kubernetes Web documentation are licensed under the terms of the
[GNU General Public License Version 2](LICENSE).
