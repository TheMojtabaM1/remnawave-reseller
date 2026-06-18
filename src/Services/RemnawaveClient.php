<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * Single defensive wrapper around the Remnawave REST API.
 *
 * Design notes (see project plan):
 *  - All responses are wrapped by Remnawave in {"response": {...}} → unwrapped here.
 *  - Every endpoint path lives in self::EP so it can be corrected in one place
 *    without touching call sites (the live panel may disable Swagger).
 *  - Every call logs raw status + body to storage/logs/remnawave.log on error.
 *  - Field readers tolerate name drift across Remnawave 2.x minor versions.
 */
final class RemnawaveClient
{
    /** Centralised endpoint map — adjust here if a panel version differs. */
    private const EP = [
        'users'        => '/api/users',
        'user_by_uuid' => '/api/users/{uuid}',
        'user_by_tag'  => '/api/users/by-tag/{tag}',
        'user_delete'  => '/api/users/{uuid}',
        'user_revoke'  => '/api/users/{uuid}/actions/revoke',
        'squads'       => '/api/internal-squads',
        'nodes'        => '/api/nodes',
        'stats'        => '/api/system/stats',
        'stats_online' => '/api/system/stats/online',
    ];

    private Client $http;
    private string $base;

    public function __construct(?string $baseUrl = null, ?string $token = null)
    {
        $base  = $baseUrl ?? (string) Config::env('RW_BASE_URL', '');
        $token = $token ?? (string) Config::env('RW_API_TOKEN', '');

        // Normalise: drop trailing slash and an accidental trailing /api.
        $base = rtrim(trim($base), '/');
        $base = preg_replace('#/api$#', '', $base) ?? $base;
        $this->base = $base;

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        $extra = Config::env('RW_EXTRA_HEADERS', '');
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            if (is_array($decoded)) {
                $headers = array_merge($headers, $decoded);
            }
        }

        $this->http = new Client([
            'base_uri'    => $base,
            'timeout'     => (float) Config::env('RW_TIMEOUT', 30),
            'http_errors' => false,
            'headers'     => $headers,
        ]);
    }

    // ── User operations ─────────────────────────────────────────────

    /**
     * @param array $opts username,status,trafficLimitBytes,trafficLimitStrategy,
     *                    expireAt,hwidDeviceLimit,description,tag,telegramId,email,squads[]
     */
    public function createUser(array $opts): array
    {
        $payload = [
            'username'             => $opts['username'],
            'status'               => $opts['status'] ?? 'ACTIVE',
            'trafficLimitBytes'    => (int) ($opts['trafficLimitBytes'] ?? 0),
            'trafficLimitStrategy' => $opts['trafficLimitStrategy'] ?? 'NO_RESET',
            'expireAt'             => $opts['expireAt'],
            'hwidDeviceLimit'      => (int) ($opts['hwidDeviceLimit'] ?? 0),
            'description'          => $opts['description'] ?? '',
            'tag'                  => $opts['tag'] ?? null,
            'activeInternalSquads' => array_values($opts['squads'] ?? []),
        ];
        if (!empty($opts['telegramId'])) {
            $payload['telegramId'] = (int) $opts['telegramId'];
        }
        if (!empty($opts['email'])) {
            $payload['email'] = $opts['email'];
        }

        return $this->request('POST', self::EP['users'], $payload);
    }

    public function updateUser(string $uuid, array $fields): array
    {
        $payload = array_merge(['uuid' => $uuid], $fields);
        return $this->request('PATCH', self::EP['users'], $payload);
    }

    public function getUser(string $uuid): array
    {
        return $this->request('GET', $this->path('user_by_uuid', ['uuid' => $uuid]));
    }

    public function deleteUser(string $uuid): bool
    {
        $this->request('DELETE', $this->path('user_delete', ['uuid' => $uuid]));
        return true;
    }

    /** @return array list of user rows tagged with $tag */
    public function getUsersByTag(string $tag): array
    {
        try {
            $res = $this->request('GET', $this->path('user_by_tag', ['tag' => $tag]));
        } catch (RemnawaveException) {
            // Fallback for panels without the /by-tag route.
            $res = $this->request('GET', self::EP['users'] . '?tag=' . rawurlencode($tag) . '&size=500');
        }
        return $this->extractUsers($res);
    }

    public function listUsers(array $query = []): array
    {
        $qs = $query ? ('?' . http_build_query($query)) : '';
        $res = $this->request('GET', self::EP['users'] . $qs);
        return $this->extractUsers($res);
    }

    /** Returns used bytes for a user (reads whichever field the panel exposes). */
    public function getUserUsage(string $uuid): int
    {
        $user = $this->getUser($uuid);
        return $this->usedBytes($user);
    }

    /** Revoke / regenerate the user's subscription. */
    public function revokeSubscription(string $uuid): array
    {
        return $this->request('POST', $this->path('user_revoke', ['uuid' => $uuid]), []);
    }

    // ── Squads / nodes / stats ──────────────────────────────────────

    /** @return array<int, array{uuid:string,name:string,members:int}> */
    public function listInternalSquads(): array
    {
        $res = $this->request('GET', self::EP['squads']);
        $list = $res['internalSquads'] ?? $res['squads'] ?? (is_array($res) ? $res : []);
        $out = [];
        foreach ($list as $s) {
            if (!is_array($s) || empty($s['uuid'])) {
                continue;
            }
            $out[] = [
                'uuid'    => (string) $s['uuid'],
                'name'    => (string) ($s['name'] ?? $s['uuid']),
                'members' => (int) ($s['info']['membersCount'] ?? $s['membersCount'] ?? 0),
            ];
        }
        return $out;
    }

    public function listNodes(): array
    {
        $res = $this->request('GET', self::EP['nodes']);
        return is_array($res['nodes'] ?? null) ? $res['nodes'] : (array_is_list($res) ? $res : ($res['response'] ?? []));
    }

    public function systemStats(): array
    {
        return $this->request('GET', self::EP['stats']);
    }

    /** Online-users / active connections count. Returns -1 if unavailable. */
    public function onlineCount(): int
    {
        try {
            $res = $this->systemStats();
        } catch (RemnawaveException) {
            return -1;
        }
        // Remnawave 2.x: system/stats -> { onlineStats: { onlineNow } }.
        if (isset($res['onlineStats']) && is_array($res['onlineStats'])) {
            foreach (['onlineNow', 'online', 'usersOnline'] as $k) {
                if (isset($res['onlineStats'][$k]) && is_numeric($res['onlineStats'][$k])) {
                    return (int) $res['onlineStats'][$k];
                }
            }
        }
        foreach (['onlineUsers', 'usersOnline', 'online', 'activeConnections'] as $k) {
            if (isset($res[$k]) && is_numeric($res[$k])) {
                return (int) $res[$k];
            }
        }
        return -1;
    }

    /** Lightweight connectivity/token check used by the installer + settings. */
    public function ping(): bool
    {
        $this->request('GET', self::EP['squads']);
        return true;
    }

    // ── Field readers (defensive) ───────────────────────────────────

    public function usedBytes(array $user): int
    {
        // Remnawave 2.x nests usage under userTraffic on the detailed user GET.
        $nested = $user['userTraffic'] ?? [];
        foreach (['usedTrafficBytes', 'lifetimeUsedTrafficBytes', 'usedTraffic'] as $k) {
            if (isset($nested[$k]) && is_numeric($nested[$k])) {
                return (int) $nested[$k];
            }
        }
        foreach (['usedTrafficBytes', 'usedTraffic', 'trafficUsedBytes', 'usedBytes', 'lifetimeUsedTrafficBytes'] as $k) {
            if (isset($user[$k]) && is_numeric($user[$k])) {
                return (int) $user[$k];
            }
        }
        return 0;
    }

    public function subscriptionUrl(array $user): ?string
    {
        foreach (['subscriptionUrl', 'subscription_url', 'subLink', 'happUrl'] as $k) {
            if (!empty($user[$k])) {
                return (string) $user[$k];
            }
        }
        // Build from shortUuid if only that is exposed.
        $short = $user['shortUuid'] ?? $user['subscriptionShortUuid'] ?? null;
        if ($short) {
            return $this->base . '/sub/' . $short;
        }
        return null;
    }

    public function userExpired(array $user): bool
    {
        $exp = $user['expireAt'] ?? $user['expire_at'] ?? null;
        if (!$exp) {
            return false;
        }
        return strtotime((string) $exp) < time();
    }

    // ── Internals ───────────────────────────────────────────────────

    private function extractUsers(array $res): array
    {
        if (isset($res['users']) && is_array($res['users'])) {
            return $res['users'];
        }
        if (array_is_list($res)) {
            return $res;
        }
        return [];
    }

    private function path(string $key, array $params): string
    {
        $path = self::EP[$key];
        foreach ($params as $k => $v) {
            $path = str_replace('{' . $k . '}', rawurlencode((string) $v), $path);
        }
        return $path;
    }

    /**
     * Execute a request, unwrap the {response:...} envelope, log + throw on error.
     * @return array decoded "response" payload (or [] for empty bodies)
     */
    private function request(string $method, string $uri, ?array $body = null): array
    {
        $options = [];
        if ($body !== null) {
            // An empty array must serialize as a JSON object {} (Remnawave
            // validation rejects [] where it expects an object, e.g. revoke).
            $options['json'] = $body === [] ? new \stdClass() : $body;
        }

        try {
            $resp = $this->http->request($method, $uri, $options);
        } catch (RequestException | GuzzleException $e) {
            $this->log($method, $uri, 0, $e->getMessage());
            throw new RemnawaveException(
                'ارتباط با پنل Remnawave برقرار نشد. بعداً تلاش کنید.',
                0,
                $e->getMessage()
            );
        }

        $status = $resp->getStatusCode();
        $raw    = (string) $resp->getBody();

        if ($status >= 400) {
            $this->log($method, $uri, $status, $raw);
            $msg = $this->humanError($status, $raw);
            throw new RemnawaveException($msg, $status, $raw);
        }

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        // Unwrap the standard envelope.
        if (array_key_exists('response', $decoded)) {
            $inner = $decoded['response'];
            return is_array($inner) ? $inner : ['value' => $inner];
        }
        return $decoded;
    }

    private function humanError(int $status, string $raw): string
    {
        $decoded = json_decode($raw, true);
        $detail  = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? '') : '';
        if (is_array($detail)) {
            $detail = implode('، ', array_map('strval', $detail));
        }
        return match (true) {
            $status === 401 || $status === 403 => 'توکن API نامعتبر یا فاقد دسترسی است.',
            $status === 404 => 'منبع موردنظر در پنل Remnawave یافت نشد.',
            $status === 409 => 'تداخل داده در پنل Remnawave: ' . $detail,
            $status >= 500  => 'خطای داخلی پنل Remnawave. بعداً تلاش کنید.',
            default => 'خطای پنل Remnawave (' . $status . '): ' . $detail,
        };
    }

    private function log(string $method, string $uri, int $status, string $body): void
    {
        $line = sprintf(
            "[%s] %s %s -> %d %s\n",
            gmdate('Y-m-d H:i:s'),
            $method,
            $uri,
            $status,
            substr(str_replace("\n", ' ', $body), 0, 1000)
        );
        $file = dirname(__DIR__, 2) . '/storage/logs/remnawave.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
