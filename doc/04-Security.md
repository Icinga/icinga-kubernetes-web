# Security

Icinga for Kubernetes allows users to show different Kubernetes resources. Users may be restricted to a specific set of
resources, by use of **permissions** and **restrictions**.

## Permissions

> If a role [limits users](#filters) to a specific set of results, the
> permissions or refusals of the very same role only apply to these results.
 
If a user has permission to show one resource but lacks permissions for another resource that is dependent on or related
to the first, the dependent resource will not appear in the detail view of the accessible resource.

This ensures that users can only see the specific resources they are authorized for, maintaining a strict boundary of
visibility and data access.

### Examples

If a user has permission to show **Deployments** but does not have permission to show **ReplicaSets**, the
**Deployment** detail view will omit any associated **ReplicaSets**.

Similarly, if a user can view **DaemonSets** but lacks permissions for **Pods** within the same namespace, the Pods will
be excluded from the DaemonSet's detail view.

Also, if a user lacks permission to show **ReplicaSets**, any **Events** related to **ReplicaSets** will not be shown at
all in the **ListController**.

| Name                                     | Allow...                         |
|------------------------------------------|----------------------------------|
| kubernetes/config-maps/show              | to show config maps              |
| kubernetes/cron-jobs/show                | to show cron jobs                |
| kubernetes/daemon-sets/show              | to show daemon sets              |
| kubernetes/deployments/show              | to show deployments              |
| kubernetes/events/show                   | to show events                   |
| kubernetes/ingresses/show                | to show ingresses                |
| kubernetes/jobs/show                     | to show jobs                     |
| kubernetes/nodes/show                    | to show nodes                    |
| kubernetes/persistent-volume-claims/show | to show persistent volume claims |
| kubernetes/persistent-volumes/show       | to show persistent volumes       |
| kubernetes/pods/show                     | to show pods                     |
| kubernetes/replica-sets/show             | to show replica sets             |
| kubernetes/secrets/show                  | to show secrets                  |
| kubernetes/services/show                 | to show services                 |
| kubernetes/stateful-sets/show            | to show stateful sets            |
| kubernetes/yaml/show                     | to show yaml                     |

## Restrictions

### Filters

Filters limit users to a specific set of results.

> **Note:**
>
> Filters from multiple roles will widen available access.

| Name                        | Description                                                       |
|-----------------------------|-------------------------------------------------------------------|
| kubernetes/filter/resources | Restrict access to the Kubernetes resources that match the filter |

`kubernetes/filter/resources` will only allow users to access matching Kubernetes resources. This applies to all
resources.

Allowed columns are:

* annotation.name
* annotation.value
* label.name
* label.value
* namespace
* name

> **Note:**
>
> Nodes, namespaces and persistent volumes do not belong to a namespace, therefore only the name is available for
> filtering.

## Restricted Permissions:

Restricted permissions define how permissions and restrictions are combined to control a user's access to resources.
Each role specifies what a user can access (permissions) and any limitations on that access (restrictions). When a user
has multiple roles, they see resources according to the permissions and restrictions defined per each role, without
merging or overlapping the restrictions across roles.

### Example

- **Role A**: Grants permission to view **deployments**, **replica sets**, and **pods**. Access is restricted within a specified **namespace**.

- **Role B**: Grants permission to view **daemon sets** and **pods**, with access limited to a specific **namespace**.

- **Role C**: Grants permission to view all resources, but restricts access to resources whose **name** matches a specified **pattern**.

If a user is assigned all three roles:
- They can see **deployments**, **replica sets**, and **pods** based on the namespace restriction from **Role A**.
- They can see **daemon sets** and **pods** based on the namespace restrictions from **Role B**.
- They can see all resources, matching the name restriction defined by **Role C**.

This ensures that each resource type respects its specific role's restrictions, enabling precise and controlled access
to resources.
