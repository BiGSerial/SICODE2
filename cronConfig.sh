# Rotina de BAckup do Banco de dados SICODE
0 */12 * * * /bin/bash /usr/local/bin/dbbackup.sh

# Executa a checagem do Banco Sicode ao Banco SQL
0 9-21 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:chk_integridade
30 8-21 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:wpas_log
0 8 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseov --prazos --full
0 8 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:fix-prazos
0 7 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:fix-prazos
5 13 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:fix-prazos --full
40 6 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseEP
0 14 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseEP

# Update Base Construção Sicode
15 5 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOrder && php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOperation
45 9 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOrder && php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOperation
0 12 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOrder && php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOperation
0 16 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOrder && php /dados/sites/ftpcip/sicode_es/artisan sicode:upd_baseOperation

# Envio de Logs do Sicode para o SqlServer a cada 1h
30 */1 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:log_production
32 */1 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:transfer_log
32 */1 * * * /dados/script/cron_logger.sh php /dados/sites/ftpcip/sicode_es/artisan sicode:notestop_log
