<?php

namespace App;

class TelegramService {
    private $botToken;
    private $chatId;
    
    public function __construct() {
        $this->botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
        $this->chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID');
    }
    
    public function isConfigured(): bool {
        return !empty($this->botToken) && !empty($this->chatId);
    }
    
    public function sendNewOrderNotification($order): bool {
        if (!$this->isConfigured()) {
            error_log('Telegram не настроен - пропускаем уведомление');
            return false;
        }
        
        try {
            $orderTypeText = $order['order_type'] === 'astana' ? 'Астана' : 'Регионы';
            
            $message = "🚚 *Новая заявка #{$order['id']}*\n\n" .
                "*Тип:* {$orderTypeText}\n" .
                "*Клиент:* {$order['contact_person']}\n" .
                "*Телефон:* {$order['phone']}\n" .
                "*Груз:* {$order['cargo_type']} ({$order['weight']} кг)\n";
            
            if (!empty($order['pickup_city'])) {
                $message .= "*Город отправления:* {$order['pickup_city']}\n";
            }
            
            $message .= "*Забор:* {$order['pickup_address']}\n";
            
            if (!empty($order['destination_city'])) {
                $message .= "*Город назначения:* {$order['destination_city']}\n";
            }
            
            $message .= "*Доставка:* {$order['delivery_address']}\n" .
                "*Время готовности:* {$order['ready_time']}\n" .
                "*Получатель:* {$order['recipient_contact']} ({$order['recipient_phone']})\n";
            
            if (!empty($order['comment'])) {
                $message .= "*Комментарий:* {$order['comment']}\n";
            }
            
            if (!empty($order['delivery_method'])) {
                $message .= "*Способ доставки:* {$order['delivery_method']}\n";
            }
            
            if (!empty($order['desired_arrival_date'])) {
                $message .= "*Желаемая дата прибытия:* {$order['desired_arrival_date']}\n";
            }
            
            $message .= "\n*Дата создания:* " . date('d.m.Y H:i', strtotime($order['created_at']));
            
            return $this->sendMessage($message);
            
        } catch (Exception $e) {
            error_log('Ошибка отправки Telegram уведомления: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sendStatusUpdateNotification($order, $oldStatus): bool {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $statusText = $order['status'] === 'completed' ? '✅ Выполнена' : '🔄 В обработке';
            $oldStatusText = $oldStatus === 'completed' ? '✅ Выполнена' : '🔄 В обработке';
            
            $message = "📋 *Обновление статуса заявки #{$order['id']}*\n\n" .
                "*Клиент:* {$order['contact_person']}\n" .
                "*Статус изменен:* {$oldStatusText} → {$statusText}\n" .
                "*Дата изменения:* " . date('d.m.Y H:i');
            
            return $this->sendMessage($message);
            
        } catch (Exception $e) {
            error_log('Ошибка отправки Telegram уведомления о статусе: ' . $e->getMessage());
            return false;
        }
    }
    
    private function sendMessage($message): bool {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log('Ошибка отправки Telegram сообщения');
            return false;
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['ok']) && $response['ok']) {
            error_log('Telegram уведомление отправлено успешно');
            return true;
        } else {
            error_log('Ошибка Telegram API: ' . ($response['description'] ?? 'Неизвестная ошибка'));
            return false;
        }
    }
}