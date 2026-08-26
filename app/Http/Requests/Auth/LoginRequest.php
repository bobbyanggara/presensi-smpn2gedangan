<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Berapa detik durasi lock, dan berapa kali gagal sebelum di-lock.
     * Window ini SELALU di-refresh penuh (fresh) setiap ada percobaan gagal
     * baru, jadi hitungannya konsisten mulai dari percobaan terakhir —
     * bukan sisa waktu dari percobaan gagal yang paling pertama.
     */
    protected const MAX_ATTEMPTS = 5;
    protected const LOCK_SECONDS = 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('username', 'password'), $this->boolean('remember'))) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        $this->clearRateLimit();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $attempts = (int) Cache::get($this->attemptsKey(), 0);

        if ($attempts < self::MAX_ATTEMPTS) {
            return;
        }

        event(new Lockout($this));

        // Sisa waktu dihitung dari timestamp "unlock_at" yang di-refresh setiap
        // percobaan gagal (lihat recordFailedAttempt()), jadi selalu maksimal
        // 60 detik penuh sejak percobaan gagal terakhir, tidak pernah lebih
        // dan tidak "nanggung" sisa dari percobaan yang lebih lama.
        $unlockAt = (int) Cache::get($this->timerKey(), now()->timestamp);
        $seconds = max(0, $unlockAt - now()->timestamp);

        // Dikirim terpisah lewat session (bukan cuma dibungkus di pesan error)
        // supaya halaman login bisa menonaktifkan semua tombol/form selama
        // hitungan mundur berjalan, tanpa perlu parsing teks pesan.
        $this->session()->flash('login_lockout_seconds', $seconds);

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Catat 1 percobaan gagal dan REFRESH window lock ke 60 detik penuh
     * (dihitung mulai dari sekarang / percobaan gagal ini).
     */
    protected function recordFailedAttempt(): void
    {
        $attempts = (int) Cache::get($this->attemptsKey(), 0) + 1;

        Cache::put($this->attemptsKey(), $attempts, self::LOCK_SECONDS);
        Cache::put($this->timerKey(), now()->addSeconds(self::LOCK_SECONDS)->timestamp, self::LOCK_SECONDS);
    }

    /**
     * Hapus status rate limit (dipanggil setelah login berhasil).
     */
    protected function clearRateLimit(): void
    {
        Cache::forget($this->attemptsKey());
        Cache::forget($this->timerKey());
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }

    protected function attemptsKey(): string
    {
        return 'login_attempts:'.$this->throttleKey();
    }

    protected function timerKey(): string
    {
        return 'login_timer:'.$this->throttleKey();
    }
}

