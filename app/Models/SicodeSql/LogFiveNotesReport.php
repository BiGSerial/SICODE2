<?php

namespace App\Models\SicodeSql;

use Illuminate\Database\Eloquent\Model;

class LogFiveNotesReport extends Model
{
    protected $connection = 'sqlsrv2';

    protected $table = 'dbo.log_five_notes_report_sync';

    protected $primaryKey = 'id_local';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id_local' => 'integer',
        'note_id_local' => 'integer',
        'pending_return_count' => 'integer',
        'waiting_days' => 'integer',

        'visible_partner' => 'boolean',
        'is_completed' => 'boolean',
        'is_supervisioned' => 'boolean',
        'is_payed' => 'boolean',
        'is_archived' => 'boolean',
        'is_passive' => 'boolean',
        'returned' => 'boolean',
        'is_report_eligible' => 'boolean',
        'active' => 'boolean',

        'd5_created_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'partner_returned_at' => 'datetime',
        'fiscalization_completed_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'pending_return_at' => 'datetime',
        'waiting_since_at' => 'datetime',
        'source_created_at' => 'datetime',
        'source_updated_at' => 'datetime',
        'timeline_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
