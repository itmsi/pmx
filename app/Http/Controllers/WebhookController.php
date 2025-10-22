<?php

namespace App\Http\Controllers;

use App\Models\GitHistory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Git webhook requests (GitHub, GitLab, Bitbucket, etc.)
     */
    public function gitWebhook(Request $request): JsonResponse
    {
        try {
            // Log webhook request untuk debugging
            Log::info('Git webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Deteksi provider Git berdasarkan headers atau payload
            $provider = $this->detectGitProvider($request);
            
            // Parse payload berdasarkan provider
            $payload = $this->parsePayload($request, $provider);
            
            if (!$payload) {
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            // Proses setiap commit dalam payload
            $processedCommits = [];
            foreach ($payload['commits'] as $commit) {
                $result = $this->processCommit($commit, $payload);
                if ($result) {
                    $processedCommits[] = $result;
                }
            }

            return response()->json([
                'message' => 'Webhook processed successfully',
                'processed_commits' => count($processedCommits),
                'commits' => $processedCommits
            ]);

        } catch (\Exception $e) {
            Log::error('Git webhook error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Deteksi provider Git berdasarkan headers atau payload
     */
    private function detectGitProvider(Request $request): string
    {
        $headers = $request->headers->all();
        
        // GitHub
        if (isset($headers['x-github-event'])) {
            return 'github';
        }
        
        // GitLab
        if (isset($headers['x-gitlab-event'])) {
            return 'gitlab';
        }
        
        // Bitbucket
        if (isset($headers['x-event-key'])) {
            return 'bitbucket';
        }
        
        // Default ke GitHub jika tidak bisa dideteksi
        return 'github';
    }

    /**
     * Parse payload berdasarkan provider
     */
    private function parsePayload(Request $request, string $provider): ?array
    {
        $payload = $request->all();
        
        switch ($provider) {
            case 'github':
                return $this->parseGitHubPayload($payload);
            case 'gitlab':
                return $this->parseGitLabPayload($payload);
            case 'bitbucket':
                return $this->parseBitbucketPayload($payload);
            default:
                return null;
        }
    }

    /**
     * Parse GitHub webhook payload
     */
    private function parseGitHubPayload(array $payload): ?array
    {
        if (!isset($payload['commits']) || !is_array($payload['commits'])) {
            return null;
        }

        return [
            'repository' => [
                'name' => $payload['repository']['name'] ?? 'Unknown',
                'url' => $payload['repository']['html_url'] ?? null,
            ],
            'pusher' => [
                'name' => $payload['pusher']['name'] ?? 'Unknown',
                'email' => $payload['pusher']['email'] ?? null,
            ],
            'ref' => $payload['ref'] ?? 'refs/heads/main',
            'commits' => array_map(function ($commit) {
                return [
                    'id' => $commit['id'],
                    'message' => $commit['message'],
                    'timestamp' => $commit['timestamp'],
                    'author' => [
                        'name' => $commit['author']['name'] ?? 'Unknown',
                        'email' => $commit['author']['email'] ?? null,
                    ],
                ];
            }, $payload['commits'])
        ];
    }

    /**
     * Parse GitLab webhook payload
     */
    private function parseGitLabPayload(array $payload): ?array
    {
        if (!isset($payload['commits']) || !is_array($payload['commits'])) {
            return null;
        }

        return [
            'repository' => [
                'name' => $payload['project']['name'] ?? 'Unknown',
                'url' => $payload['project']['web_url'] ?? null,
            ],
            'pusher' => [
                'name' => $payload['user_name'] ?? 'Unknown',
                'email' => $payload['user_email'] ?? null,
            ],
            'ref' => $payload['ref'] ?? 'refs/heads/main',
            'commits' => array_map(function ($commit) {
                return [
                    'id' => $commit['id'],
                    'message' => $commit['message'],
                    'timestamp' => $commit['timestamp'],
                    'author' => [
                        'name' => $commit['author']['name'] ?? 'Unknown',
                        'email' => $commit['author']['email'] ?? null,
                    ],
                ];
            }, $payload['commits'])
        ];
    }

    /**
     * Parse Bitbucket webhook payload
     */
    private function parseBitbucketPayload(array $payload): ?array
    {
        if (!isset($payload['push']['changes']) || !is_array($payload['push']['changes'])) {
            return null;
        }

        $commits = [];
        foreach ($payload['push']['changes'] as $change) {
            if (isset($change['commits'])) {
                foreach ($change['commits'] as $commit) {
                    $commits[] = [
                        'id' => $commit['hash'],
                        'message' => $commit['message'],
                        'timestamp' => $commit['date'],
                        'author' => [
                            'name' => $commit['author']['user']['display_name'] ?? 'Unknown',
                            'email' => $commit['author']['user']['email_address'] ?? null,
                        ],
                    ];
                }
            }
        }

        return [
            'repository' => [
                'name' => $payload['repository']['name'] ?? 'Unknown',
                'url' => $payload['repository']['links']['html']['href'] ?? null,
            ],
            'pusher' => [
                'name' => $payload['actor']['display_name'] ?? 'Unknown',
                'email' => $payload['actor']['email_address'] ?? null,
            ],
            'ref' => $payload['push']['changes'][0]['new']['name'] ?? 'refs/heads/main',
            'commits' => $commits
        ];
    }

    /**
     * Proses setiap commit dan simpan ke database
     */
    private function processCommit(array $commit, array $payload): ?array
    {
        // Extract ticket ID dari commit message
        $ticketId = GitHistory::extractTicketId($commit['message']);
        
        if (!$ticketId) {
            Log::info('No ticket ID found in commit message', ['message' => $commit['message']]);
            return null;
        }

        // Cek apakah ticket exists
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            Log::warning('Ticket not found', ['ticket_id' => $ticketId]);
            return null;
        }

        // Cari user berdasarkan email atau name
        $user = $this->findUserByEmailOrName($commit['author']['email'], $commit['author']['name']);

        // Extract branch name dari ref
        $branch = $this->extractBranchName($payload['ref']);

        // Simpan git history
        $gitHistory = GitHistory::create([
            'ticket_id' => $ticketId,
            'user_id' => $user?->id,
            'branch' => $branch,
            'commit_message' => $commit['message'],
            'commit_hash' => $commit['id'],
            'pushed_at' => $commit['timestamp'],
            'repository_name' => $payload['repository']['name'],
            'repository_url' => $payload['repository']['url'],
        ]);

        Log::info('Git history saved', [
            'ticket_id' => $ticketId,
            'commit_hash' => $commit['id'],
            'user_id' => $user?->id
        ]);

        return [
            'ticket_id' => $ticketId,
            'commit_hash' => $commit['id'],
            'branch' => $branch,
            'message' => $commit['message']
        ];
    }

    /**
     * Cari user berdasarkan email atau name
     */
    private function findUserByEmailOrName(?string $email, string $name): ?User
    {
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                return $user;
            }
        }

        // Jika tidak ditemukan berdasarkan email, coba berdasarkan name
        return User::where('name', 'like', '%' . $name . '%')->first();
    }

    /**
     * Extract branch name dari ref
     */
    private function extractBranchName(string $ref): string
    {
        // Contoh: refs/heads/main -> main
        if (str_starts_with($ref, 'refs/heads/')) {
            return substr($ref, 11);
        }
        
        return $ref;
    }
}