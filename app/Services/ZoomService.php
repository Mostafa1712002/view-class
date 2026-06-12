<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected string $accountId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->accountId    = (string) config('zoom.account_id', '');
        $this->clientId     = (string) config('zoom.client_id', '');
        $this->clientSecret = (string) config('zoom.client_secret', '');
        $this->baseUrl      = (string) config('zoom.base_url', 'https://api.zoom.us/v2/');
    }

    /**
     * Obtain a Server-to-Server OAuth access token, cached for ~58 minutes.
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('zoom_access_token_viewclass', 3500, function () {
            try {
                $response = Http::asForm()
                    ->withBasicAuth($this->clientId, $this->clientSecret)
                    ->post('https://zoom.us/oauth/token', [
                        'grant_type' => 'account_credentials',
                        'account_id' => $this->accountId,
                    ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('ZoomService: failed to obtain access token', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            } catch (\Throwable $e) {
                Log::error('ZoomService: exception obtaining access token', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Create a scheduled Zoom meeting.
     *
     * @param  array{title: string, start_time: string, duration_minutes: int, description?: string, timezone?: string, passcode?: string}  $data
     * @return array{id: string, join_url: string, start_url: string, passcode: string}
     *
     * @throws \RuntimeException when the meeting cannot be created
     */
    public function createMeeting(array $data): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            throw new \RuntimeException('ZoomService: unable to obtain access token');
        }

        $payload = [
            'topic'      => $data['title'],
            'type'       => 2,                                    // Scheduled
            'start_time' => $data['start_time'],                  // ISO 8601: 2026-06-20T10:00:00Z
            'duration'   => (int) ($data['duration_minutes'] ?? 45),
            'timezone'   => $data['timezone'] ?? 'Asia/Riyadh',
            'agenda'     => $data['description'] ?? '',
            'settings'   => [
                'host_video'         => true,
                'participant_video'  => true,
                'join_before_host'   => false,
                'mute_upon_entry'    => true,
                'watermark'          => false,
                'audio'              => 'both',
                'auto_recording'     => 'none',
            ],
        ];

        if (! empty($data['passcode'])) {
            $payload['password'] = $data['passcode'];
        }

        $response = Http::withToken($token)
            ->post($this->baseUrl . 'users/me/meetings', $payload);

        if ($response->successful()) {
            $meeting = $response->json();

            return [
                'id'        => (string) $meeting['id'],
                'join_url'  => $meeting['join_url'],
                'start_url' => $meeting['start_url'],
                'passcode'  => $meeting['password'] ?? '',
            ];
        }

        Log::error('ZoomService: failed to create meeting', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new \RuntimeException(
            'ZoomService: meeting creation failed — HTTP ' . $response->status() . ': ' . $response->body()
        );
    }
}
