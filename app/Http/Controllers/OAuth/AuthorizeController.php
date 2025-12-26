<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizeController extends Controller
{
    /**
     * Handle OAuth authorization request.
     */
    public function authorize(
        ServerRequestInterface $serverRequest,
        Request $request,
        ResponseInterface $response,
        AuthorizationViewResponse $viewResponse,
        PassportAuthorizationController $passportController
    )
    {
        $request->validate([
            'client_id' => 'required|string',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'nullable|string',
            'state' => 'nullable|string',
        ]);

        // Find application by client_id
        $application = Application::where('client_id', $request->client_id)
            ->where('is_active', true)
            ->first();

        if (!$application) {
            return $this->errorResponse($request, 'invalid_client', 'Client not found or inactive.', 400);
        }

        // Validate redirect_uri
        if (!$application->isValidRedirectUri($request->redirect_uri)) {
            return $this->errorResponse($request, 'invalid_request', 'Invalid redirect_uri.', 400);
        }

        $user = $request->user();

        // Check if user can access this application
        if (!$user->canAccessApplication($application)) {
            return $this->errorResponse($request, 'access_denied', 'User does not have access to this application.', 403);
        }

        return $passportController->authorize($serverRequest, $request, $response, $viewResponse);
    }

    protected function errorResponse(Request $request, string $error, string $description, int $status): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $redirectUri = $request->input('redirect_uri');

        if ($redirectUri && filter_var($redirectUri, FILTER_VALIDATE_URL)) {
            $params = [
                'error' => $error,
                'error_description' => $description,
            ];

            if ($request->filled('state')) {
                $params['state'] = $request->input('state');
            }

            $separator = parse_url($redirectUri, PHP_URL_QUERY) ? '&' : '?';

            return redirect($redirectUri.$separator.http_build_query($params));
        }

        return response()->json([
            'error' => $error,
            'error_description' => $description,
        ], $status);
    }
}
