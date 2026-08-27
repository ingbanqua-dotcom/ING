<?php
declare(strict_types=1);

return [
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_username' => getenv('SMTP_USERNAME') ?: 'ingbanqua@gmail.com',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: 'yjtx exvq mjch tvph',
    'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_encryption' => strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls'),
    'mail_from' => getenv('MAIL_FROM') ?: 'ingbanqua@gmail.com',
    'mail_from_name' => getenv('MAIL_FROM_NAME') ?: 'ING Bank',
];