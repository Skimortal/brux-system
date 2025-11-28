# Cron Job Setup für Brux API Sync

## Einrichtung

Füge folgende Zeile in die Crontab ein (`crontab -e`):

```
0 3 * * * cd /pfad/zum/projekt && php bin/console app:sync-brux-api >> /var/log/brux-sync.log 2>&1
```

## Alternativen

### 1. Symfony Scheduler (empfohlen ab Symfony 6.3+)

In `config/packages/scheduler.yaml`:

```
yaml framework: scheduler: schedules: brux_api_sync: task: 'app:sync-brux-api' frequency: 'daily' time: '03:00'
```

### 2. Manueller Aufruf

```
bash php bin/console app:sync-brux-api
```

## Log-Überwachung

Logs werden geschrieben nach:
- `var/log/prod.log` (Production)
- `var/log/dev.log` (Development)