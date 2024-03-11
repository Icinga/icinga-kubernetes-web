# Configuration

If Icinga Web has been installed but not yet set up, please visit Icinga Web and follow the web-based setup wizard.
For Icinga Web setups already running, log in to Icinga Web with a privileged user and follow the steps below to
configure Icinga for Kubernetes Web:

## Database Configuration

Connection configuration for the database to which Icinga for Kubernetes synchronizes monitoring data.

1. Create a new resource for the Icinga for Kubernetes database via the `Configuration → Application → Resources` menu.

2. Configure the resource you just created as the database connection for the Icinga for Kubernetes Web module using the
   `Configuration → Modules → kubernetes → Database` menu.
