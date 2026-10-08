-- Executar no SQL Server da conexão sqlsrv2 antes do sync completo.
-- Ordem escolhida dentre as Ordens vinculadas ao informe, fim real da operação
-- 0020 e valor informado no ADS do próprio informe.

IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'op20_order_number') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD op20_order_number NVARCHAR(80) NULL;

IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'op20_finished_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD op20_finished_at DATETIME2 NULL;

IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'ads_amount') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD ads_amount DECIMAL(14, 2) NULL;

-- Confirmacao e fimReal SAP da medicao (operacao 0050).
IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'measurement_closed_in_sap') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD measurement_closed_in_sap BIT NOT NULL CONSTRAINT DF_log_note_inform_flows_sync_measurement_closed_in_sap DEFAULT 0;

IF COL_LENGTH('dbo.log_note_inform_flows_sync', 'measurement_closed_in_sap_at') IS NULL
    ALTER TABLE dbo.log_note_inform_flows_sync ADD measurement_closed_in_sap_at DATETIME2 NULL;
