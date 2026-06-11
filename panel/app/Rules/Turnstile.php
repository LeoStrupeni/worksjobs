<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class Turnstile implements Rule
{
    protected $errorMessage = 'La validación de seguridad ha fallado. Inténtalo de nuevo.';

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determina si la regla de validación pasa.
     *
     * @param  string  $attribute  (En este caso será 'cf-turnstile-response')
     * @param  mixed  $value       (El token enviado por el frontend)
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Si el token está vacío, no pasa
        if (empty($value)) {
            $this->errorMessage = 'Por favor, completa el desafío de seguridad.';
            return false;
        }

        // Petición HTTP interna a Cloudflare usando el cliente HTTP de Laravel 8
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful()) {
            $this->errorMessage = 'No se pudo verificar el desafío de seguridad. Inténtalo de nuevo.';
            return false;
        }

        $responseData = $response->json();

        // Retorna true si Cloudflare confirma que el token es válido
        if (!($responseData['success'] ?? false)) {
            $this->errorMessage = 'La validación de seguridad ha fallado. Inténtalo de nuevo.';
            return false;
        }

        return true;
    }

    /**
     * Obtiene el mensaje de error de validación.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage;
    }
}