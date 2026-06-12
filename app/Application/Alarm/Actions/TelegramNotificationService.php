<?php

namespace App\Application\Alarm\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private ?string $token;
    private ?string $chatId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->chatId = config('services.telegram.chat_id', env('TELEGRAM_CHAT_ID'));
    }

    /**
     * Send an alarm notification to Telegram.
     */
    public function sendAlarmNotification(\App\Domain\Alarm\Models\Alarm $alarm): bool
    {
        if (!$this->token || !$this->chatId) {
            return false;
        }

        $emoji = match ($alarm->severity) {
            'critical' => '🚨',
            'major'    => '🔴',
            'minor'    => '🟠',
            'warning'  => '🟡',
            default    => '🔵',
        };

        $status = strtoupper($alarm->status);
        $entityType = strtoupper($alarm->entity_type);
        $entityId = $alarm->entity_id;
        $time = $alarm->created_at->format('Y-m-d H:i:s');

        $message = "{$emoji} <b>[{$status}] {$entityType} Alarm</b>\n";
        $message .= "<b>Message:</b> {$alarm->message}\n";
        $message .= "<b>Severity:</b> " . strtoupper($alarm->severity) . "\n";
        $message .= "<b>Entity ID:</b> {$entityId}\n";
        $message .= "<b>Time:</b> {$time}\n";

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification: ' . $e->getMessage());
            return false;
        }
    }
}
