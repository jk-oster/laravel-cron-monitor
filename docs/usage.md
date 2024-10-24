---
title: "Usage"
description: "Generate report and trends from collections."
editLink: true
prev:
  text: Installtion & Setup
  link: /#installation
next:
  text: Advanced Usage
  link: /advanced-usage.html
---

::: warning
🏗️ This Page Under Construction and not ready for use yet!
:::

## Creating a cron monitor

After you've [set up the package](/#installation) you can use the ``cron-monitor:create`` artisan command to monitor an cron job. 

```bash
php artisan cron-monitor:create CronJobName
```

## Removing a monitor

You can remove a monitor by running monitor:delete. Here's how to delete the monitor of CronJobName:

```bash
php artisan cron-monitor:delete CronJobName
```

This will remove the monitor for CronJobName from the database. Want to delete multiple monitors at once? Just pass all the urls as comma-separated list.

Instead of using the ``cron-monitor:delete`` command you may also manually delete the relevant row in the ``cron_monitors`` table.

## Controller & API Route

## Events

### CronCheckFailedEvent

### CronCheckRecoverdEvent

### IncomingPingReceivedEvent

## Notifications

The package notifies you if certain events take place when running the cron check.
You can specify which channels the notifications for certain events should be sent in the config file.
If you don't want any notifications for a certain event, just pass an empty array. Out of the box ``slack`` and ``mail`` are supported.
If you want to use another channel or modify the notifications, read the section on [customizing notifications](./advanced-usage.md).

