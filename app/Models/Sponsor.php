<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Sponsor — Modelo de patrocinadores de eventos.
 *
 * @package App\Models
 */
class Sponsor extends Model
{
    protected string $table = 'sponsors';

    protected array $fillable = [
        'event_id', 'name', 'logo', 'website', 'description',
        'tier', 'contact_name', 'contact_email', 'benefits', 'sort_order',
    ];

    /**
     * Patrocinadores de un evento ordenados por tier y posición.
     *
     * @param  int $eventId
     * @return array Indexado por tier
     */
    public static function byEventGrouped(int $eventId): array
    {
        $sponsors = self::rawQuery(
            "SELECT * FROM sponsors WHERE event_id = :eid ORDER BY FIELD(tier,'platinum','gold','silver','bronze','partner'), sort_order ASC",
            [':eid' => $eventId]
        );

        $grouped = [];
        foreach ($sponsors as $sponsor) {
            $grouped[$sponsor['tier']][] = $sponsor;
        }

        return $grouped;
    }
}
