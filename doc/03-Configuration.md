# Configuration

If Icinga Web has been installed but not yet set up, please visit Icinga Web and follow the web-based setup wizard.
For Icinga Web setups already running, log in to Icinga Web with a privileged user and follow the steps below to
configure Icinga for Kubernetes Web:

## Database Configuration

Connection configuration for the database to which Icinga for Kubernetes synchronizes monitoring data.

1. Create a new resource for the Icinga for Kubernetes database via the `Configuration → Application → Resources` menu.

2. Configure the resource you just created as the database connection for the Icinga for Kubernetes Web module using the
   `Configuration → Modules → kubernetes → Database` menu.

## Notifications Configuration

Open `Configuration → Modules → kubernetes → Notifications` and select the
cluster. Enter an HTTP or HTTPS Notifications URL, or use a Unix socket URL
such as `unix:///run/icinga-notifications/api.sock`. The Icinga Web URL must
use HTTP or HTTPS. Leave the Notifications URL blank to disable notifications
for the selected cluster.

For assisted Unix socket setup, select `Use discovered Unix socket` to prefill
the URL, then select `Save Changes`. Assistance requires matching socket and
process user metadata from the cluster's live daemon instances. Metadata older
than 60 seconds is ignored. An older daemon without this metadata can use
manual URL configuration. Locally managed settings can prevent assistance.

Unix sockets authenticate the operating system user of the Icinga for
Kubernetes process. Saving a Unix URL creates a Kubernetes source for that
username if fresh process user metadata is available. Without that metadata,
configure the source in Icinga Notifications manually. Unix URLs remove
writable HTTP credentials. Remove conflicting credentials from the daemon's
local configuration before switching transports.

Source usernames are shared across all Notifications integrations. A username
already used by another source type cannot be reused. Unix setup preserves
existing source credentials. Disabling one cluster keeps its source for other
clusters. HTTP and HTTPS setup creates a password. Switching back from Unix
renews the password for the cluster's automatically managed HTTP source and
reuses its source ID.

Restart Icinga for Kubernetes after saving to apply the database configuration.
For manual Unix URLs, encode reserved path characters such as spaces, `#`, `?`,
and `%` as `%20`, `%23`, `%3F`, and `%25`.
