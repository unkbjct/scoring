<?php

namespace App\Webhooks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Scoring
{

  public static function handler(int $leadId, string $phones): array
  {
    $points = [];
    $phones = explode(",", $phones);
    
    foreach ($phones as $phone) {
      $response = Http::withHeaders([
        "Accept" => "application/json",
        "Token" => config("services.scoring.api_key"),
      ])->get("http://89.169.158.138:8080/api/Score/GetScorePhone", [
        'phoneNum' => $phone
      ]);

      if ($response->status() !== 200) {
        continue;
        // return self::createResult(false, "Неизвестная ошибка, попробуйте позднее");
      }

      $data = $response->json();

      if (!$data['isSuccess']) {
        continue;
        // return self::createResult(false, $data['errorMessage'], $phone);
      }

      array_push($points, $data['scoreResult']);
    }

    $bitrixResponse = Http::post("https://2024-10finzor.bitrix24.ru/rest/19/" . config('services.bitrix.rest_key') . '/' . "crm.lead.update", [
      'id' => $leadId,
      'fields' => [
        "UF_CRM_1731347943235" => $points,
      ],
    ]);

    if (isset($bitrixResponse->json()['error'])) {
      return self::createResult(false, $bitrixResponse->json()['error_description']);
    }

    return self::createResult(true);
  }

  /**
   * Creating result
   * @param bool $success
   * @param ?string|null $errorMessage
   * @param ?string|null $errorPhone
   * @return array
   */
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
