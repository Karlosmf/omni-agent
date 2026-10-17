<?php

/*
 * Hosting profiles supported by the white-label installer.
 * All of them run on shared hosting: one per-minute cron, database queue and cache, no Redis, no daemons.
 */
return [
    'cron_command' => '* * * * * cd {path} && {php} artisan schedule:run >> /dev/null 2>&1',

    'profiles' => [
        'hostinger' => [
            'label' => 'Hostinger (hPanel)',
            'panel' => 'hPanel',
            'cron_location' => 'Avanzado → Trabajos Cron',
            'php_binary' => '/opt/alt/php84/usr/bin/php',
            'public_dir' => 'public_html',
        ],
        'donweb' => [
            'label' => 'DonWeb (cPanel)',
            'panel' => 'cPanel',
            'cron_location' => 'Avanzado → Trabajos de Cron',
            'php_binary' => '/usr/local/bin/php',
            'public_dir' => 'public_html',
        ],
        'hostrentable' => [
            'label' => 'HostRentable (cPanel)',
            'panel' => 'cPanel',
            'cron_location' => 'Avanzado → Trabajos de Cron',
            'php_binary' => '/usr/local/bin/php',
            'public_dir' => 'public_html',
        ],
    ],
];
