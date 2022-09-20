# Additional tasks for Deployer

Additional and enhanced tasks not available for [Deployer](https://github.com/deployphp/deployer) out of the box.

# Installation

```shell
composer require friemt/deployer-tasks
```

# Usage

Either include the recipe or task and the respective configuration to your deployer file.

## crontab

Syncs configured cronjobs to the target systems.

```php
# deploy.php
# ...

import('recipe/crontab.php');
# or
import('task/crontab.php');

# See `src/examples/crontab.yaml` for a yaml based example file.
import(__DIR__ . '/<path-to-configs>/crontab.yaml');

# When using the task instead of the recipe, add the sync task to the deployment manually:
after('deploy:symlink', 'crontab:sync');
```

### variables

| variable              | description                                                               | default                                            |
|-----------------------|---------------------------------------------------------------------------|----------------------------------------------------|
| bin/crontab           | path to the crontab binary on the target system                           | `which crontab`                                    |
| crontab_backup        | path where the backup should be placed                                    | `{{deploy_path}}/.dep/crontab.bak`                 |
| crontab_marker        | a string used to identify the deployment across target systems and stages | `{{application}} {{crontab_stage}}`                |
| crontab_stage         | a string to differentiate multiple deployments on the same target system  | `host.labels.stage`                                |
| crontab_user          | the user to use with crontabs `-u` option                                 | `host.remote_user`                                 |
| crontab_target_lookup | a list of labels to determine the target systems                          | `host.alias`, `host.hostname`, `host.labels.stage` |

### tasks

| task          | description                                                                         |
|---------------|-------------------------------------------------------------------------------------|
| crontab:check | checks the target system against the configuration and displays any differences     |
| crontab:sync  | syncs the configuration to the target system, adding, updating and deleting entries |

## runonce

Runs a defined command on the target system once and only once.

```php
# deploy.php
# ...

import('recipe/runonce.php');
# or
import('task/runonce.php');

# See `src/examples/runonce.yaml` for a yaml based example file.
import(__DIR__ . '/<path-to-configs>/runonce.yaml');

# When using the task instead of the recipe, add the run task to the deployment manually:
after('deploy:symlink', 'runonce:run');
```

### variables

| variable              | description                                      | default                                            |
|-----------------------|--------------------------------------------------|----------------------------------------------------|
| runonce_history       | path where the history should be placed          | `{{deploy_path}}/.dep/runonce_log`                 |
| runonce_target_lookup | a list of labels to determine the target systems | `host.alias`, `host.hostname`, `host.labels.stage` |

### tasks

| task          | description                                                                                             |
|---------------|---------------------------------------------------------------------------------------------------------|
| runonce:check | checks the target system against the configuration and displays any differences                         |
| runonce:run   | checks which commands have already run and runs any differences, also retries commands marked for retry |

## template

Copy files to the target system after replacing deployer placeholders.

```php
# deploy.php
# ...

import('recipe/template.php');
# or
import('task/template.php');

# See `src/examples/template.yaml` for a yaml based example file.
import(__DIR__ . '/<path-to-configs>/template.yaml');

# When using the task instead of the recipe, add the sync task to the deployment manually:
after('deploy:symlink', 'template:sync');
```

### variables

| variable               | description                                      | default                                            |
|------------------------|--------------------------------------------------|----------------------------------------------------|
| template_history       | path where the history should be placed          | `{{deploy_path}}/.dep/template_log`                |
| template_target_lookup | a list of labels to determine the target systems | `host.alias`, `host.hostname`, `host.labels.stage` |

### tasks

| task           | description                                                                     |
|----------------|---------------------------------------------------------------------------------|
| template:check | checks the target system against the configuration and displays any differences |
| template:sync  | syncs the configuration to the target system, adding and updating files         |
