<?php

namespace App\Webhooks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Scoring
{
    public static function handler(int $leadId): array
    {
        // Получаем информацию о лиде
        $leadResponse = Http::post("https://b24-pwnag0.bitrix24.ru/rest/67531/" . config('services.bitrix.rest_key') . '/' . "crm.lead.get", [
            'id' => $leadId
        ]);

        if (isset($leadResponse->json()['error'])) {
            return self::createResult(false, $leadResponse->json()['error_description']);
        }

        $leadData = $leadResponse->json()['result'];
        $phone = null;

        // Проверяем есть ли привязанный контакт
        if (!empty($leadData['CONTACT_ID'])) {
            // Если есть контакт, получаем его данные
            $contactResponse = Http::post("https://b24-pwnag0.bitrix24.ru/rest/67531/" . config('services.bitrix.rest_key') . '/' . "crm.contact.get", [
                'id' => $leadData['CONTACT_ID']
            ]);

            if (isset($contactResponse->json()['error'])) {
                return self::createResult(false, $contactResponse->json()['error_description']);
            }

            $contactData = $contactResponse->json()['result'];
            // Берем только первый телефон из контакта
            if (!empty($contactData['PHONE'][0])) {
                $phone = $contactData['PHONE'][0]['VALUE'];
            }
        } else {
            // Если контакта нет, берем первый телефон из лида
            if (!empty($leadData['PHONE'][0])) {
                $phone = $leadData['PHONE'][0]['VALUE'];
            }
        }

        if (empty($phone)) {
            return self::createResult(false, "Не найден телефонный номер");
        }

        $response = Http::withHeaders([
            "Accept" => "application/json",
            "Token" => config("services.scoring.api_key"),
        ])->get("http://89.169.158.138:8080/api/Score/GetScorePhone", [
            'phoneNum' => $phone
        ]);

        if ($response->status() !== 200) {
            return self::createResult(false, "Ошибка получения скоринга");
        }

        $data = $response->json();

        if (!$data['isSuccess']) {
            return self::createResult(false, $data['errorMessage'] ?? "Ошибка получения скоринга");
        }

        // Если scoreResult пустой или null, используем 0
        $scoreResult = empty($data['scoreResult']) ? 0 : $data['scoreResult'];

        $bitrixResponse = Http::post("https://b24-pwnag0.bitrix24.ru/rest/67531/" . config('services.bitrix.rest_key') . '/' . "crm.lead.update", [
            'id' => $leadId,
            'fields' => [
                "UF_CRM_1729858827" => $scoreResult,
            ],
        ]);

        if (isset($bitrixResponse->json()['error'])) {
            return self::createResult(false, $bitrixResponse->json()['error_description']);
        }

        Log::debug("success scoring");

        return self::createResult(true);
    }

    private static function createResult(bool $success, ?string $errorMessage = null, ?string $errorPhone = null): array
    {
        $result = [
            'success' => $success,
            'errorMessage' => $errorMessage,
            'errorPhone' => $errorPhone
        ];

        if (!$errorMessage) unset($result['errorMessage']);
        if (!$errorPhone) unset($result['errorPhone']);

        return $result;
    }
}