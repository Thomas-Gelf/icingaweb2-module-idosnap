IDO Status Snapshots
====================

This [Icinga](https://icinga.com/) module allows taking multiple Snapshots of
current Host and Service Monitoring Status in your Icinga IDO database. It's
main purpose is being a simplistic tool allowing to compare system health across
two points in time.

The larger your setup, the more this might become helpful:

* when rolling out changes or upgrades on a larger scale
* when running Disaster Recovery tests
* before, during and after Migration processes

Sounds promising? Then read on!

Usage
-----

### Menu

Once [installed](#Installation) and [configured](#Configuration), you'll find
this module in your Icinga Web 2 **History** menu section:

![Menu](doc/screenshot/01-Menu.png)

### Create Snapshots

Head on and create your very first Snapshot. Please give your Snapshots
meaningful names, this makes your life easier later on: 

![Create a Snapshot](doc/screenshot/02-Create_a_Snapshot.png)

### Inspect Snapshots

Every snapshot contains your Icinga Service and Host object names, and their
current Monitoring Status. In case an object has a problem, the snapshot also
stores whether this problem has been acknowledged or is covered by a downtime.
In case it is a Service, it's Host state also has an influence on whether the
problem is considered being "handled".

You can select any snapshot and navigate through its objects. A click on any
object forwards you to the related Monitoring Details page:

![Snapshot Details](doc/screenshot/03-Snapshot-Details.png)

### Compare Snapshots

This is the main essential feature provided by this module. When visiting a
Snapshot, click **Diff**...

![Click Diff](doc/screenshot/04-Click_Diff.png)

...and choose one of your other Snapshots:

![Compare Snapshots](doc/screenshot/05-Compare_Snapshots.png)

It doesn't matter whether you choose the older or the recent one first, the
Diff will always be calculated and shown in the correct chronological order.
Objects that have been removed (or created) between two Snapshots will also
be shown:

![Snapshot Diff](doc/screenshot/06-Snapshot_Diff.png)

In case there are no Differences, you're being told:

![Identical Snapshots](doc/screenshot/07-Identical_Snapshots.png)

### Delete outdated Snapshots

Once you no longer need older Snapshots, please delete them:

![Delete Snapshot](doc/screenshot/08-Delete_Snapshot.png)

This saves disk space and computing power.

Installation
------------

### Requirements

* Icinga Web 2 (&gt; 2.8)
* PHP (&gt;= 7.1 or 8.x, 64bit only)
* MySQL (&gt;= 5.6) or MariaDB (&gt;= 5.5.3)
* The following Icinga modules must be installed and enabled:
    * [incubator](https://github.com/Icinga/icingaweb2-module-incubator) (>=0.12)
    * If you are using Icinga Web &lt; 2.9.0, the following modules are also required
        * [ipl](https://github.com/Icinga/icingaweb2-module-ipl) (>=0.5.0)
        * [reactbundle](https://github.com/Icinga/icingaweb2-module-reactbundle) (>=0.7.0)

### Module Installation

```shell
# You can customize these settings, but we suggest to stick with our defaults:
MODULE_VERSION="1.0.0"
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/Thomas-Gelf/icingaweb2-module-idosnap"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/idosnap"
URL="${REPO_URL}/archive/refs/tags/v${MODULE_VERSION}.tar.gz"

install -d -m 0755 "${TARGET_DIR}"
test -d "${TARGET_DIR}_TMP" && rm -rf "${TARGET_DIR}_TMP"
test -d "${TARGET_DIR}_BACKUP" && rm -rf "${TARGET_DIR}_BACKUP"
install -d -o root -g root -m 0755 "${TARGET_DIR}_TMP"
wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}_TMP" --strip-components 1 \
  && mv "${TARGET_DIR}" "${TARGET_DIR}_BACKUP" \
  && mv "${TARGET_DIR}_TMP" "${TARGET_DIR}" \
  && rm -rf "${TARGET_DIR}_BACKUP"
```

### Create an empty database on MariaDB (or MySQL)

This module requires a MariaDB or MySQL database:

    mysql -e "CREATE DATABASE idosnap CHARACTER SET 'utf8mb4' COLLATE utf8mb4_bin;
       CREATE USER idosnap@localhost IDENTIFIED BY 'some-password';
       GRANT ALL ON idosnap.* TO idosnap@localhost;"

HINT: You should replace `some-password` with a secure custom password.

### Apply the Database Schema

Our schema is provided in `schema/mysql.sql`, applying this is usually as simple
as running:

    mysql idosnap < "$TARGET_DIR/schema/mysql.sql"

Configuration
-------------

### Configure a DB Resource in Icinga Web 2

In your web frontend please go to `Configuration / Application / Resources`
and create a new database resource pointing to your newly created database.
Please make sure that you choose `utf8mb4` as an encoding.

### Refer the configured DB resource

Now you're ready to populate `/etc/icingaweb2/modules/eventtracker/config.ini`,
please reference your newly configured DB resource:

```ini
[db]
resource = "IDO Snap"
```

### When something goes wrong

Don't worry, when something goes wrong you're usually presented meaningful
error messages:

#### No DB configured
![Error - No DB configured](doc/screenshot/09-Error_no_db_configured.png)

#### No such resource
![Error - No such resource](doc/screenshot/10-Error_no_such_resource.png)

Read carefully, they usually point you to the right direction.