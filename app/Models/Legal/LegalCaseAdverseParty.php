<?php

namespace App\Models\Legal;

use App\Models\User;
use App\Support\Legal\LegalPartyDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalCaseAdverseParty extends Model
{
    use HasFactory;

    protected $table = 'legal_case_adverse_parties';

    protected $fillable = [
        'legal_case_id',
        'name',
        'document_type',
        'document_encrypted',
        'document_hash',
        'document_last4',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_encrypted' => 'encrypted',
    ];

    protected $hidden = [
        'document_encrypted',
        'document_hash',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function getDocumentMaskedAttribute(): string
    {
        return LegalPartyDocument::mask($this->document_encrypted, $this->document_type);
    }

    public function getDocumentFormattedAttribute(): string
    {
        return LegalPartyDocument::format($this->document_encrypted, $this->document_type);
    }
}
