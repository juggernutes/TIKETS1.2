<?php

if (!function_exists('portal_response')) {
    /**
     * Respuesta JSON estandarizada.
     * Uso: return portal_response('Ticket creado.', $data, 201);
     */
    function portal_response(string $message, mixed $data = null, int $status = 200): \Illuminate\Http\JsonResponse
    {
        $body = ['message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $status);
    }
}
