<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * LoginRequest handles the validation and authentication of login requests.
 * 
 * @extends FormRequest
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @access public
     * @return bool
     */
    public function authorize() : bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @access public
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules() : array {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string']
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * 
     * @throws ValidationException
     * 
     * @access public
     * @return void
     */
    public function authenticate() : void {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     * 
     * @throws ValidationException
     * 
     * @access public
     * @return void
     */
    public function ensureIsNotRateLimited() : void {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60)
            ])
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     * 
     * @access public
     * @return string
     */
    public function throttleKey() : string {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
