<!-- {% if index %} -->

# Installing Icinga for Kubernetes Web

The recommended way to install Icinga for Kubernetes Web is to use prebuilt packages for
all supported platforms from our official release repository.
Please follow the steps listed for your target operating system,
which guide you through setting up the repository and installing Icinga for Kubernetes Web.

![Icinga for Kubernetes Web](res/icinga-kubernetes-web-installation.png)

Before installing Icinga for Kubernetes Web, make sure you have installed
[Icinga for Kubernetes](https://icinga.com/docs/icinga-kubernetes).

<!-- {% else %} -->
<!-- {% if not icingaDocs %} -->

## Installing the Package

If the [repository](https://packages.icinga.com) is not configured yet, please add it first.
Then use your distribution's package manager to install the `icinga-kubernetes-web` package
or install [from source](02-Installation.md.d/From-Source.md).
<!-- {% endif %} -->

This concludes the installation. Now proceed with the [configuration](03-Configuration.md).
<!-- {% endif %} --><!-- {# end else if index #} -->
