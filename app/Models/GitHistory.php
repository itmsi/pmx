<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'author_name',
        'author_email',
        'branch',
        'commit_message',
        'commit_hash',
        'pushed_at',
        'repository_name',
        'repository_url',
    ];

    protected $casts = [
        'pushed_at' => 'datetime',
    ];

    /**
     * Get the ticket that owns the git history.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user that made the git push.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Extract ticket ID from commit message.
     */
    public static function extractTicketId(string $commitMessage): ?int
    {
        // Pattern untuk mencari ticket ID dalam commit message
        // Contoh: "Fix bug #123", "Update feature TICKET-456", "Fix T-789", "#EPC-3D76K1"
        $patterns = [
            '/#(\d+)/',                    // #123
            '/TICKET-(\d+)/i',             // TICKET-456
            '/T-(\d+)/i',                  // T-789
            '/TICKET(\d+)/i',              // TICKET123
            '/#([A-Za-z]+-[A-Za-z0-9]+)/i', // #EPC-3D76K1, #epc-LMM1ZD, #ABC-123XYZ
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $commitMessage, $matches)) {
                $ticketIdentifier = $matches[1];
                
                // Jika berupa angka, langsung return
                if (is_numeric($ticketIdentifier)) {
                    return (int) $ticketIdentifier;
                }
                
                // Jika berupa UUID/string, cari ticket berdasarkan UUID
                $ticket = Ticket::where('uuid', $ticketIdentifier)->first();
                if ($ticket) {
                    return $ticket->id;
                }
            }
        }

        return null;
    }
}
