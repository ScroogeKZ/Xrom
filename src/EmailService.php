<?php
namespace App;

class EmailService
{
    private $from_email;
    private $from_name;
    
    public function __construct()
    {
        $this->from_email = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@chrome-kz.com';
        $this->from_name = 'Хром-KZ Логистика';
    }
    
    public function sendOrderNotification($order, $type = 'created')
    {
        $subject = match($type) {
            'created' => "Новый заказ №{$order['id']} - Хром-KZ",
            'status_updated' => "Статус заказа №{$order['id']} изменен - Хром-KZ",
            default => "Уведомление по заказу №{$order['id']} - Хром-KZ"
        };
        
        $message = $this->buildOrderEmailTemplate($order, $type);
        
        // Отправляем администратору
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@chrome-kz.com';
        $this->sendEmail($admin_email, $subject, $message);
        
        // Отправляем клиенту если есть email
        if (!empty($order['contact_email'])) {
            $this->sendEmail($order['contact_email'], $subject, $message);
        }
        
        return true;
    }
    
    private function buildOrderEmailTemplate($order, $type)
    {
        $statusText = match($order['status']) {
            'new' => 'Новый',
            'processing' => 'В обработке', 
            'completed' => 'Завершен',
            default => $order['status']
        };
        
        $orderTypeText = $order['order_type'] === 'astana' ? 'Астана' : 'Межгород';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
                .company-name { font-size: 24px; font-weight: bold; color: #2d3748; }
                .order-info { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
                .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
                .label { font-weight: bold; color: #4a5568; }
                .value { color: #2d3748; }
                .status-new { color: #dc2626; }
                .status-processing { color: #d97706; }
                .status-completed { color: #059669; }
                .footer { text-align: center; font-size: 12px; color: #718096; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='company-name'>Хром-KZ Логистика</div>
                    <p>Система управления доставками</p>
                </div>
                
                <div class='order-info'>
                    <h3>Заказ №{$order['id']}</h3>
                    
                    <div class='info-row'>
                        <span class='label'>Тип заказа:</span>
                        <span class='value'>{$orderTypeText}</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Статус:</span>
                        <span class='value status-{$order['status']}'>{$statusText}</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Адрес забора:</span>
                        <span class='value'>" . htmlspecialchars($order['pickup_address'] ?? '') . "</span>
                    </div>";
                    
        if ($order['order_type'] === 'regional') {
            $html .= "
                    <div class='info-row'>
                        <span class='label'>Город назначения:</span>
                        <span class='value'>" . htmlspecialchars($order['destination_city'] ?? '') . "</span>
                    </div>";
        }
        
        $html .= "
                    <div class='info-row'>
                        <span class='label'>Тип груза:</span>
                        <span class='value'>" . htmlspecialchars($order['cargo_type'] ?? '') . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Контактное лицо:</span>
                        <span class='value'>" . htmlspecialchars($order['contact_name'] ?? '') . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Телефон:</span>
                        <span class='value'>" . htmlspecialchars($order['contact_phone'] ?? '') . "</span>
                    </div>";
                    
        if (!empty($order['shipping_cost'])) {
            $html .= "
                    <div class='info-row'>
                        <span class='label'>Стоимость:</span>
                        <span class='value'>" . number_format($order['shipping_cost'], 0, ',', ' ') . " ₸</span>
                    </div>";
        }
        
        $html .= "
                    <div class='info-row'>
                        <span class='label'>Дата создания:</span>
                        <span class='value'>" . date('d.m.Y H:i', strtotime($order['created_at'])) . "</span>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Это автоматическое уведомление от системы Хром-KZ Логистика</p>
                    <p>Не отвечайте на это письмо</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    private function sendEmail($to, $subject, $message)
    {
        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: {$this->from_name} <{$this->from_email}>",
            "Reply-To: {$this->from_email}",
            "X-Mailer: PHP/" . phpversion()
        ];
        
        // В продакшене здесь будет настоящая отправка email
        // Пока логируем в файл для тестирования
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'headers' => $headers,
            'message_preview' => substr(strip_tags($message), 0, 100) . '...'
        ];
        
        file_put_contents(
            __DIR__ . '/../email_log.txt', 
            json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", 
            FILE_APPEND | LOCK_EX
        );
        
        return true;
    }
}