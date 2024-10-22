---
title: "Advanced Usage"
description: "More in depth examples and usage of laravel cron monitor"
editLink: true
prev:
  text: General Usage
  link: /usage.html
---

::: warning
🏗️ This Page Under Construction and not ready for use yet!
:::

## Manually modifying cron monitors

All configured monitors are stored in the monitors table in the database. The various monitor commands manipulate the data that table:

- ``cron-monitor:create`` adds a row
- ``cron-monitor:delete`` deletes a row
- ``cron-monitor:enable`` and ``cron-monitor:disable`` change the value of the enabled field
- ``cron-monitor:list`` lists all rows
- ``cron-monitor:sync-file`` syncs monitors from a json file (see syncing monitors from a file)

### Manipulating table rows / model properties

You can also manually manipulate the table rows instead. Here's a description of the fields you can manipulate:

## Syncing monitors from a file

Using the ``cron-monitor:create`` becomes tedious fast if you have a large number of urls that you wish to monitor. Luckily there's also a command to bulk import urls from a json file:

```bash
php artisan cron-monitor:sync-file <path-to-file>
```

Here's an example of the structure that json file should have:

```json
[
  {
    // TODO
  },
  {
    // TODO
  }
]
```

By default the command will import all missing monitors and update existing monitors. If you wish to delete cron job monitors from the database that are not in the json file you can use the ``--delete-missing`` flag.

## Customizing Notifications

This package leverages Laravel's native notification capabilites to send out notifications.

```php
'notifications' => [
    \JkOster\CronMonitor\Notifications\Notifications\CronCheckFailed::class => ['slack'],
    \JkOster\CronMonitor\Notifications\Notifications\CronCheckRecovered::class => ['slack'],
],
```

Notice that the config keys are fully qualified class names of the Notification classes. All notifications have support for ``slack`` and ``mail`` out of the box. If you want to add support for more channels or just want to use change some text in the notifications you can specify your own notification classes in the config file. When creating custom notifications, it's probably best to extend the default ones shipped with this package.

## Using your own Model

By default this package uses the ``JkOster\CronMonitor\Models\CronMonitor`` model. If you want add some extra functionality you can specify your own model in the ``monitor_model`` key of the package config file. The only requirement for your custom model is that is should extend ``JkOster\CronMonitor\Models\CronMonitor``.

## Verifying incoming Cron Job Pings

To verify that the pings reaching your application are actually from your cron job and not someone else sending a request you can use a [middleware](https://laravel.com/docs/middleware#main-content) with your ping routes.
