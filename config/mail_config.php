<?php
// tutoria/config/mail_config.php
return [
  // Cambia por tu servidor real (Gmail/App Password o SMTP institucional)
  'host'       => 'smtp.gmail.com',
  'username'   => 'programador.oti@undc.edu.pe',
  'password'   => 'jyxklugzrskhyeby',
  'port'       => 587,
  'encryption' => 'tls',

  'from_email' => 'programador.oti@undc.edu.pe',
  'from_name'  => 'SISTECU - UNDC',

  // Copia oculta global (opcional)
  'bcc'        => [
    // 'cgt@undc.edu.pe'
  ],
];
