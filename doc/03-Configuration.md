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

Open **Configuration → Modules → kubernetes → Notifications** and select a cluster.
Enter an HTTP or HTTPS URL for Icinga Notifications. Automatic source setup requires
Icinga Notifications Web 1.0 or later with a configured database. Unless overridden in
the daemon configuration, HTTP setup uses the cluster UUID as the listener username
and generates a password.

For Unix socket transport, use versions of Icinga for Kubernetes and Icinga Notifications
that support Unix socket URLs and OS user authentication. The Icinga for Kubernetes
process must be able to access the Notifications socket. Remove Notifications credentials
set in the daemon configuration before switching to a Unix socket. The daemon ignores
stored HTTP credentials over Unix, so the form keeps them for a later return to HTTP.
Before switching a cluster that was configured for HTTP, upgrade Icinga for Kubernetes
to a release with Unix socket discovery support. Earlier releases can use manual Unix
URLs for clusters without stored HTTP credentials.

Select **Use discovered Unix socket** to prefill the URL and check the process username,
then select **Save Changes**. All live instances of the selected cluster must report the
same path and username. The form ignores heartbeats more than 60 seconds old. If the
path or username changes before saving, select the new discovery result again.

Assisted setup creates a Kubernetes source without a password when the process username
is available. When Icinga Notifications Web can report the source type, assisted setup
reuses an existing Kubernetes source as is and rejects a username assigned to a
different source type. Clusters running under the same OS user can share the source.
With Icinga Notifications Web 1.0.0, verify an existing source's type in Icinga
Notifications, clear the discovery checkbox, and save the socket URL manually.

For manual setup, create or verify the Kubernetes source for the process username in
Icinga Notifications, then enter a URL such as
`unix:///run/icinga-notifications/api.sock`.
Use this procedure when the daemon does not publish discovery metadata. Encode spaces,
`#`, `?`, and `%` in a socket path as `%20`, `%23`, `%3F`, and `%25`, respectively.
If the encoded URL exceeds 255 bytes, use a shorter path or a symlink the
Icinga for Kubernetes process can access.

If Icinga Notifications Web can report the source type, the form rejects an HTTP
listener username assigned to a different source type. If HTTP setup asks you to
confirm an existing source, verify its listener username and Kubernetes source type
in Icinga Notifications. The form creates locked sources named
`Icinga for Kubernetes (<cluster UUID>)`. Select **Use an existing HTTP source** if the
source is already configured for this cluster. For a manually provisioned source with
different credentials, set its username and password in the daemon configuration.
Restart the daemon to load those settings before returning to the form.

When the daemon configuration supplies no password, the form generates a fresh one
for each new HTTP source and keeps HTTP disabled until activation succeeds.
If the form creates a source but cannot save the HTTP URL, verify the source and
select **Use an existing HTTP source** when you save the URL again.

If you use a locally configured username and a form-generated password, verify the
source and select **Use an existing HTTP source** when reusing it. When switching
back to an earlier username, restore its password in the daemon configuration.
If that password is unavailable, rotate the dedicated source's password as described
below or coordinate a change with the source administrator.

After changing a password in the daemon configuration, restart the daemon to sync it.
In Icinga Notifications, verify the listener username, the Kubernetes source type, and
the lock on this cluster's dedicated source. Select **Apply the locally configured
HTTP password** in the Kubernetes form and save. Coordinate changes to shared sources
with their administrators. Ordinary saves leave existing source passwords in place.

Leave the Notifications URL blank to disable notifications for the cluster. Existing
sources and stored HTTP credentials stay available for other clusters or for later use.
Restart Icinga for Kubernetes after saving to apply the database configuration.
