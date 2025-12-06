<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $projectId;
    private $credentialsFile;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        $this->credentialsFile = config('services.firebase.credentials_file');
    }

    /**
     * Send push notification to a device token
     *
     * @param string $deviceToken - FCM device token from Android/iOS app
     * @param string $title - Notification title
     * @param string $body - Notification message
     * @param array $data - Additional data to send with notification
     * @return bool - Success/failure
     */
    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info('Firebase notification sent', ['device_token' => $deviceToken]);
                return true;
            }

            Log::error('Firebase notification failed', [
                'device_token' => $deviceToken,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Firebase notification error', [
                'message' => $e->getMessage(),
                'device_token' => $deviceToken,
            ]);

            return false;
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendBulkNotification($deviceTokens, $title, $body, $data = [])
    {
        $successful = 0;
        $failed = 0;

        foreach ($deviceTokens as $token) {
            if ($this->sendNotification($token, $title, $body, $data)) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'total' => count($deviceTokens),
        ];
    }

    /**
     * Get OAuth 2.0 access token for Firebase Admin API
     */
    private function getAccessToken()
    {
        try {
            $credentialsPath = storage_path('app/' . $this->credentialsFile);

            if (!file_exists($credentialsPath)) {
                throw new \Exception("Firebase credentials file not found at {$credentialsPath}");
            }

            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $token = $client->fetchAccessTokenWithAssertion();

            if (isset($token['access_token'])) {
                return $token['access_token'];
            }

            throw new \Exception('Failed to obtain access token from Firebase');
        } catch (\Exception $e) {
            Log::error('Failed to get Firebase access token', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send notification when payment is made
     */
    public function notifyPaymentMade($recipientToken, $payerName, $amount)
    {
        return $this->sendNotification(
            $recipientToken,
            '💰 Payment Received!',
            "{$payerName} paid you ₹{$amount}",
            [
                'type' => 'payment',
                'action' => 'view_payment',
            ]
        );
    }

    /**
     * Send notification when added to a group
     */
    public function notifyGroupInvite($userToken, $groupName, $invitedBy)
    {
        return $this->sendNotification(
            $userToken,
            '👥 New Group Invite!',
            "{$invitedBy} invited you to join {$groupName}",
            [
                'type' => 'group_invite',
                'action' => 'view_group',
            ]
        );
    }

    /**
     * Send notification for expense added to group
     */
    public function notifyExpenseAdded($groupMemberToken, $expenseName, $amount, $groupName)
    {
        return $this->sendNotification(
            $groupMemberToken,
            '💸 New Expense in ' . $groupName,
            "{$expenseName} - ₹{$amount}",
            [
                'type' => 'expense',
                'action' => 'view_expense',
            ]
        );
    }
}
