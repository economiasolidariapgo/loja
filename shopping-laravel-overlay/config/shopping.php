<?php

// Sem closures aqui: elas quebram `php artisan config:cache`.
return [
    // ADMIN_EMAIL: um ou mais e-mails separados por vírgula (acesso a /admin e aos banners)
    'admins' => array_values(array_filter(array_map('strtolower', array_map('trim', explode(',', (string) env('ADMIN_EMAIL', '')))))),

    'categories' => ['Moda', 'Calçados', 'Alimentação', 'Casa e Decoração', 'Saúde e Beleza',
                     'Eletrônicos', 'Serviços', 'Pet', 'Esporte e Lazer', 'Outros'],

    'days' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],

    'timezone' => 'America/Sao_Paulo',

    // botão flutuante de WhatsApp do site (só dígitos, com DDD)
    'site_whatsapp' => preg_replace('/\D/', '', (string) env('SITE_WHATSAPP', '')),

    // Recuperação de senha por e-mail. Por padrão liga quando MAIL_MAILER não é "log"/"array".
    // Desligada, a tela "Esqueci minha senha" orienta a falar com o administrador (modo manual).
    'reset_by_email' => (bool) env('SHOPPING_RESET_BY_EMAIL', ! in_array(env('MAIL_MAILER', 'log'), ['log', 'array'], true)),
];
