<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.is_demo', false)) {
            return $next($request);
        }

        // Allow GET requests (viewing data)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Allow POST requests for creating new data
        if ($request->isMethod('POST') && !$this->isUpdateOrDeleteRoute($request)) {
            return $next($request);
        }

        // Block PUT, PATCH, DELETE requests (editing/deleting existing data)
        if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            return $this->demoModeResponse($request);
        }

        // Block specific update/delete POST routes
        if ($this->isUpdateOrDeleteRoute($request)) {
            return $this->demoModeResponse($request);
        }

        return $next($request);
    }

    /**
     * Check if the route is for updating or deleting existing data
     */
    private function isUpdateOrDeleteRoute(Request $request): bool
    {
        $route = $request->route();
        if (!$route) return false;

        $routeName = $route->getName();
        $uri = $request->getPathInfo();

        // Routes that modify existing data
        $restrictedPatterns = [
            '/toggle-status',
            '/approve',
            '/reject',
            '/reset-password',
            '/upgrade-plan',
            '/reply',
            '/settings',
            '/update',
            '/destroy',
            '/payment-settings',
            'api/media/batch',
            'switch-business',
            'languages/create',
            'languages/{languageCode}',
            'languages/{languageCode}/toggle',
            'referral/settings',
            'api/chatgpt/generate'
        ];

        foreach ($restrictedPatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }

        // Route names that modify existing data
        $restrictedRoutePatterns = [
            '.update',
            '.destroy',
            '.toggle-status',
            '.approve',
            '.reject',
            '.reset-password',
            '.upgrade-plan',
            '.reply',
            'payment.settings',
            'api.media.batch',
            'api.media.destroy',
            'switch-business',
            'languages.create',
            'languages.toggle',
            'languages.delete',
            'referral.settings.update',
            'landing-page.custom-pages.store'
        ];

        if ($routeName) {
            foreach ($restrictedRoutePatterns as $pattern) {
                if (str_contains($routeName, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return demo mode response
     */
    private function demoModeResponse(Request $request): Response
    {
        $message = 'This action is disabled in demo mode. You can only create new data, not modify existing demo data.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'demo_mode' => true
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
