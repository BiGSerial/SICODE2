-- Executar no SQL Server (conexão sqlsrv2) para o log_reclaims refletir 100% a tela/export de Retorno Interno.
-- O sync (sicode:reclaims) só grava estas colunas se existirem; sem elas, segue com o esquema antigo.
IF COL_LENGTH('dbo.log_reclaims', 'rubrica')      IS NULL ALTER TABLE dbo.log_reclaims ADD rubrica      NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'municipality') IS NULL ALTER TABLE dbo.log_reclaims ADD municipality NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'group5')       IS NULL ALTER TABLE dbo.log_reclaims ADD group5       NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'material')     IS NULL ALTER TABLE dbo.log_reclaims ADD material     NVARCHAR(500) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'text')         IS NULL ALTER TABLE dbo.log_reclaims ADD [text]       NVARCHAR(MAX) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'status')       IS NULL ALTER TABLE dbo.log_reclaims ADD [status]     NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.log_reclaims', 'completed')    IS NULL ALTER TABLE dbo.log_reclaims ADD completed    BIT NULL;
