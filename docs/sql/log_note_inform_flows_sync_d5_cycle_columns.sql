-- Executar no SQL Server da conexao sqlsrv2 ANTES do proximo sync (sicode:sync-log-note-inform-flows).
-- Colunas fiscalization_* / measurement_* passam a refletir so o ciclo 1 (producoes nao-D5 do informe).
-- Marcos da D5 ficam em d5_*; regulatory_deadline_applies = 1 so no informe final (prazo encerra na
-- medicao do ciclo 1) e 0 no parcial. Depois: sicode:sync-note-inform-flows --all e
-- sicode:sync-log-note-inform-flows --rebase para republicar com as novas regras.

IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_fiscal_user_name') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_fiscal_user_name NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_fiscalization_entered_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_fiscalization_entered_at DATETIME2 NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_fiscalization_completed_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_fiscalization_completed_at DATETIME2 NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_payment_user_name') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_payment_user_name NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_measurement_entered_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_measurement_entered_at DATETIME2 NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_measurement_completed_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_measurement_completed_at DATETIME2 NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'd5_measurement_exited_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD d5_measurement_exited_at DATETIME2 NULL;
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'regulatory_deadline_applies') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD regulatory_deadline_applies BIT NOT NULL
        CONSTRAINT DF_log_note_inform_flows_sync_regulatory_deadline_applies DEFAULT 1;
