<?php

namespace App\Modules\Communications\Repositories;

use App\Models\User;
use App\Modules\Communications\Repositories\Contracts\ParentsContactRepository;
use App\Modules\Users\Repositories\Contracts\ParentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentParentsContactRepository implements ParentsContactRepository
{
    public function __construct(private readonly ParentRepository $parents)
    {
    }

    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        // Reuse the existing parent list query: it applies the null-safe
        // school filter (super-admin = no filter = all schools) that survived
        // the live regression. We only decorate it with interaction counts.
        $query = $this->parents->query($schoolId);

        if ($search !== null && trim($search) !== '') {
            $needle = '%'.trim($search).'%';
            $query->where(function ($w) use ($needle) {
                $w->where('users.name', 'like', $needle)
                    ->orWhere('users.phone', 'like', $needle)
                    ->orWhere('users.username', 'like', $needle)
                    ->orWhere('users.national_id', 'like', $needle)
                    ->orWhere('users.nationality', 'like', $needle);
            });
        }

        $query
            ->withCount('children')
            ->addSelect([
                'mail_count' => DB::table('internal_mail_recipients')
                    ->selectRaw('count(*)')
                    ->whereColumn('internal_mail_recipients.recipient_id', 'users.id')
                    ->where('internal_mail_recipients.trashed', false),
                'whatsapp_count' => DB::table('whatsapp_logs')
                    ->selectRaw('count(*)')
                    ->whereColumn('whatsapp_logs.parent_id', 'users.id'),
                'notification_count' => DB::table('notifications')
                    ->selectRaw('count(*)')
                    ->whereColumn('notifications.user_id', 'users.id'),
            ])
            ->orderBy('users.name');

        return $query->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?User
    {
        $parent = $this->parents->findScoped($id, $schoolId);
        if ($parent) {
            $parent->load(['children.classRoom']);
        }

        return $parent;
    }

    public function interactionLogs(User $parent): array
    {
        $mail = DB::table('internal_mail_recipients as r')
            ->join('internal_mails as m', 'm.id', '=', 'r.mail_id')
            ->leftJoin('users as s', 's.id', '=', 'm.sender_id')
            ->where('r.recipient_id', $parent->id)
            ->where('r.trashed', false)
            ->orderByDesc('m.created_at')
            ->limit(50)
            ->get([
                'm.subject', 'm.importance', 'm.created_at',
                'r.is_read', 'r.read_at', 's.name as sender_name',
            ]);

        $whatsapp = DB::table('whatsapp_logs')
            ->where('parent_id', $parent->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'message_text', 'to_number', 'status', 'type', 'sent_at', 'created_at',
            ]);

        $notifications = DB::table('notifications')
            ->where('user_id', $parent->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'title', 'body', 'type', 'read_at', 'created_at',
            ]);

        return [
            'mail' => $mail,
            'whatsapp' => $whatsapp,
            'notifications' => $notifications,
        ];
    }
}
