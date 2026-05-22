<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogNoteInformFlows extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'dbo.log_note_inform_flows_sync';

    protected $primaryKey = 'id_local';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_local',
        'flow_key',
        'flow_type',

        'note_number',
        'ovi',
        'order_number',

        'company_informe_name',

        'informed_at',
        'inform_type',
        'is_validated_by_publication',
        'publication_validated_at',

        'has_ads',
        'ads_sent_at',
        'ads_type',
        'ads_is_tacit',

        'fiscalization_entered_at',
        'fiscalization_type',
        'fiscal_assigned_at',
        'fiscal_user_name',
        'fiscal_user_company_name',
        'fiscalization_completed_at',

        'fiscalization_closed_in_sicode',
        'fiscalization_closed_in_sicode_at',
        'fiscalization_closed_in_sap',
        'fiscalization_closed_in_sap_at',
        'baixa_fiscal_status',

        'has_d5',
        'five_note_number',
        'five_note_created_at',

        'measurement_entered_at',
        'measurement_type',
        'measurement_completed_at',
        'measurement_exited_at',
        'baixa_measurement_status',

        'payment_user_name',
        'payment_user_company_name',

        'final_cycle_started_at',
        'final_cycle_ended_at',

        'current_stage',
        'blocking_reason',

        'active',
        'source_created_at',
        'source_updated_at',
        'calculated_at',

        'resolver_payload',
        'synced_at',
    ];

    protected $casts = [
        'id_local' => 'integer',

        'informed_at' => 'datetime',
        'publication_validated_at' => 'datetime',
        'ads_sent_at' => 'datetime',

        'fiscalization_entered_at' => 'datetime',
        'fiscal_assigned_at' => 'datetime',
        'fiscalization_completed_at' => 'datetime',
        'fiscalization_closed_in_sicode_at' => 'datetime',
        'fiscalization_closed_in_sap_at' => 'datetime',

        'five_note_created_at' => 'datetime',

        'measurement_entered_at' => 'datetime',
        'measurement_completed_at' => 'datetime',
        'measurement_exited_at' => 'datetime',

        'final_cycle_started_at' => 'datetime',
        'final_cycle_ended_at' => 'datetime',

        'source_created_at' => 'datetime',
        'source_updated_at' => 'datetime',
        'calculated_at' => 'datetime',
        'synced_at' => 'datetime',

        'is_validated_by_publication' => 'boolean',
        'has_ads' => 'boolean',
        'ads_is_tacit' => 'boolean',
        'fiscalization_closed_in_sicode' => 'boolean',
        'fiscalization_closed_in_sap' => 'boolean',
        'has_d5' => 'boolean',
        'active' => 'boolean',
    ];
}
