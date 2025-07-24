<?php

namespace App;

use Exception;

class VerificationService {
    
    public static function generateCode() {
        return sprintf("%06d", random_int(100000, 999999));
    }
    
    public static function sendSMSCode($phone, $code) {
        // В реальной среде здесь был бы SMS API (Twilio, SMS.ru, etc.)
        // Для демонстрации логируем код
        try {
            // Логируем код для тестирования
            error_log("SMS Code for {$phone}: {$code}");
            
            // В реальном приложении здесь бы был вызов SMS API
            // Пример: $smsService->send($phone, "Код верификации Хром-KZ: {$code}");
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending SMS code: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendEmailCode($email, $code) {
        try {
            // Логируем код для тестирования
            error_log("Email Code for {$email}: {$code}");
            
            // В реальном приложении здесь бы была отправка email
            // $emailService = new EmailService();
            // $subject = "Код верификации - Хром-KZ Логистика";
            // return $emailService->sendEmail($email, $subject, $message);
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending email code: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendVerificationCode($phone, $email, $code) {
        $smsResult = false;
        $emailResult = false;
        
        // Отправляем SMS если есть номер телефона
        if (!empty($phone)) {
            $smsResult = self::sendSMSCode($phone, $code);
        }
        
        // Отправляем Email если есть адрес
        if (!empty($email)) {
            $emailResult = self::sendEmailCode($email, $code);
        }
        
        // Возвращаем true если хотя бы один способ сработал
        return $smsResult || $emailResult;
    }
    
    public static function formatPhoneNumber($phone) {
        // Очищаем номер от лишних символов
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Добавляем +7 если номер начинается с 8
        if (strlen($cleaned) == 11 && substr($cleaned, 0, 1) == '8') {
            $cleaned = '7' . substr($cleaned, 1);
        }
        
        // Добавляем + если его нет
        if (strlen($cleaned) == 11 && substr($cleaned, 0, 1) == '7') {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }
    
    public static function validatePhone($phone) {
        $cleaned = self::formatPhoneNumber($phone);
        
        // Проверяем формат казахстанского номера
        return preg_match('/^\+7[0-9]{10}$/', $cleaned);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}