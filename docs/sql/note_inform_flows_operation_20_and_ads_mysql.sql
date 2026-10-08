-- Executar no MySQL/MariaDB do SICODE (banco principal), antes de rodar
-- sicode:sync-note-inform-flows. Execute uma única vez.

ALTER TABLE note_inform_flows
    ADD COLUMN op20_order_number VARCHAR(80) NULL AFTER order_number,
    ADD COLUMN op20_finished_at TIMESTAMP NULL AFTER op20_order_number,
    ADD COLUMN ads_amount DECIMAL(14, 2) NULL AFTER ads_is_tacit,
    ADD COLUMN measurement_closed_in_sap TINYINT(1) NOT NULL DEFAULT 0 AFTER measurement_exited_at,
    ADD COLUMN measurement_closed_in_sap_at TIMESTAMP NULL AFTER measurement_closed_in_sap;
