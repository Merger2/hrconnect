<?php
// Supervisord config untuk HRConnect queue worker
// Simpan di /etc/supervisor/conf.d/hrconnect-worker.conf
// Reload: sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start hrconnect-worker:*

return [
    'program:hrconnect-worker' => [
        'command' => 'php /home/merger/hrconnect/artisan queue:work --queue=default,payroll_high,notifications --sleep=3 --tries=3 --max-time=3600',
        'directory' => '/home/merger/hrconnect',
        'autostart' => true,
        'autorestart' => true,
        'user' => 'merger',
        'numprocs' => 2,
        'redirect_stderr' => true,
        'stdout_logfile' => '/home/merger/hrconnect/storage/logs/queue-worker.log',
        'stdout_logfile_maxbytes' => '50MB',
        'stdout_logfile_backups' => 7,
    ],

    'program:hrconnect-scheduler' => [
        'command' => 'php /home/merger/hrconnect/artisan schedule:work',
        'directory' => '/home/merger/hrconnect',
        'autostart' => true,
        'autorestart' => true,
        'user' => 'merger',
        'redirect_stderr' => true,
        'stdout_logfile' => '/home/merger/hrconnect/storage/logs/scheduler.log',
        'stdout_logfile_maxbytes' => '50MB',
        'stdout_logfile_backups' => 7,
    ],
];
