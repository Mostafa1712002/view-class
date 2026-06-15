<?php

namespace App\Modules\Whatsapp\Actions;

use App\Models\ActivityLog;
use App\Modules\Whatsapp\DTOs\ComposeMessageDto;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappBroadcast;
use App\Modules\Whatsapp\Models\WhatsappLog;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use App\Modules\Whatsapp\Services\WhatsappService;
use App\Modules\Whatsapp\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Storage;

/**
 * Persists a WhatsApp broadcast plus one log row per recipient, and drives the
 * configured WhatsApp driver (real send for text via the active provider/log
 * driver; media is stored + logged and handed to the driver which forwards a
 * media URL — actual external media delivery depends on the provider).
 */
final class SendBroadcastAction
{
    public function __construct(private readonly RecipientRepository $repo) {}

    public function execute(ComposeMessageDto $dto): WhatsappBroadcast
    {
        // Resolve the recipients again on the server (never trust the client list
        // blindly) and scope to the active school.
        $users = $dto->audience === 'specific_users'
            ? $this->repo->findUsers($dto->recipientIds, $dto->schoolId)
            : $this->repo->resolveAudience($dto->audience, $dto->schoolId, $this->refIdFromDto($dto))
                ->whereIn('id', $dto->recipientIds);

        // Settings + driver for the active school. When schoolId is null
        // (super-admin / all schools) there is no per-school provider, so we
        // fall back to the log driver (WhatsappService(null) handles this).
        $setting = $dto->schoolId
            ? SchoolWhatsappSetting::where('school_id', $dto->schoolId)->first()
            : null;

        $service  = new WhatsappService($setting);
        $driver   = $service->resolveDriver();
        $provider = $setting->provider ?? 'log';

        $broadcast = WhatsappBroadcast::create([
            'school_id'           => $dto->schoolId,
            'sender_id'           => $dto->senderId,
            'message_type'        => $dto->messageType,
            'body'                => $dto->body,
            'media_path'          => $dto->mediaPath,
            'media_original_name' => $dto->mediaOriginalName,
            'audience_label'      => $dto->audienceLabel,
            'provider'            => $provider,
        ]);

        $mediaUrl = $dto->mediaPath ? Storage::disk('public')->url($dto->mediaPath) : null;

        $sent = $failed = $skipped = 0;
        $seen = [];

        foreach ($users->unique('id') as $user) {
            $normalized = PhoneNormalizer::normalize($user->whatsapp ?? $user->phone ?? null);

            // Skip rules — never call the provider for a bad/missing/duplicate number.
            if ($normalized === null) {
                $this->logRow($broadcast, $dto, $user, null, 'no_number', 'لا يوجد رقم');
                $skipped++;
                continue;
            }
            if (! PhoneNormalizer::isValid($normalized)) {
                $this->logRow($broadcast, $dto, $user, $normalized, 'invalid_number', 'رقم غير صحيح');
                $skipped++;
                continue;
            }
            if (isset($seen[$normalized])) {
                $this->logRow($broadcast, $dto, $user, $normalized, 'skipped', 'رقم مكرر');
                $skipped++;
                continue;
            }
            $seen[$normalized] = true;

            // Drive the actual send.
            if ($dto->messageType === 'text') {
                $result = $driver->send($normalized, (string) $dto->body);
            } else {
                $result = $driver->sendMedia($normalized, $dto->body, (string) $mediaUrl, $dto->messageType);
            }

            $status = $result['success'] ? 'sent' : 'failed';
            $this->logRow(
                $broadcast,
                $dto,
                $user,
                $normalized,
                $status,
                $result['failure_reason'] ?? null,
                $result['success']
            );

            $result['success'] ? $sent++ : $failed++;
        }

        $broadcast->update([
            'total_recipients' => $sent + $failed + $skipped,
            'sent_count'       => $sent,
            'failed_count'     => $failed,
            'skipped_count'    => $skipped,
        ]);

        ActivityLog::log(
            'whatsapp.broadcast',
            sprintf(
                'إرسال رسالة واتساب (%s) إلى %d مستلم — تم: %d، فشل: %d، متخطى: %d',
                WhatsappBroadcast::MESSAGE_TYPES[$dto->messageType] ?? $dto->messageType,
                $sent + $failed + $skipped,
                $sent,
                $failed,
                $skipped
            ),
            $broadcast
        );

        return $broadcast->refresh();
    }

    private function logRow(
        WhatsappBroadcast $broadcast,
        ComposeMessageDto $dto,
        $user,
        ?string $number,
        string $status,
        ?string $reason,
        bool $sentNow = false
    ): void {
        WhatsappLog::create([
            'school_id'         => $dto->schoolId ?? $user->school_id,
            'broadcast_id'      => $broadcast->id,
            'recipient_user_id' => $user->id,
            'recipient_role'    => $user->role_name,
            'to_number'         => $number ?? '',
            'message_text'      => $dto->body ?? '',
            'message_type'      => $dto->messageType,
            'media_path'        => $dto->mediaPath,
            'status'            => $status,
            'failure_reason'    => in_array($status, ['sent', 'pending'], true) ? null : $reason,
            'provider'          => $broadcast->provider,
            'sent_at'           => $sentNow ? now() : null,
            'triggered_by'      => $dto->senderId,
            // attendance-shaped `type` column is NOT NULL — reuse it to mark the
            // broadcast message kind so attendance rows ('absence'/'late') stay distinct.
            'type'              => 'broadcast_' . $dto->messageType,
        ]);
    }

    private function refIdFromDto(ComposeMessageDto $dto): ?int
    {
        // refId is carried inside audienceLabel parsing isn't reliable; the
        // controller already resolved the recipient ids, so for non-specific
        // audiences we simply intersect against the provided ids. refId not
        // needed here because we filter by recipientIds.
        return null;
    }
}
