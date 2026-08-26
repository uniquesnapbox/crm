<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string[] ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (user()) {
            $isActive = cache()->remember('user_is_active_' . user()->id, 3600, function () {
                return User::withoutGlobalScopes()
                    ->where('id', user()->id)
                    ->where('status', 'active')
                    ->exists();
            });

            if (!$isActive) {
                cache()->forget('user_is_active_' . user()->id);
                auth()->logout();
                session()->flush();

                return redirect()->route('login');
            }
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }

}
