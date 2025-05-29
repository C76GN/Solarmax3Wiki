<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 登录请求表单
 *
 * 此表单请求类用于处理用户的登录操作。
 * 它包含对邮箱和密码的验证规则，并集成了身份认证和频率限制逻辑。
 */
class LoginRequest extends FormRequest
{
    /**
     * 确定用户是否有权发起此登录请求。
     *
     * @return bool 始终返回 true，表示任何用户都可以尝试登录。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取适用于登录请求的验证规则。
     *
     * 规定了邮箱和密码字段的必填性和格式要求。
     *
     * @return array 验证规则数组。
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * 尝试对用户进行身份验证。
     *
     * 在验证之前会检查登录尝试频率限制。如果认证失败，会增加频率限制计数并抛出验证异常。
     *
     * @throws ValidationException 当认证失败时抛出，包含错误信息。
     */
    public function authenticate(): void
    {
        // 检查当前请求是否受到频率限制
        $this->ensureIsNotRateLimited();

        // 尝试使用请求中的邮箱和密码进行认证，并考虑“记住我”选项
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // 认证失败，增加当前 IP 和邮箱组合的尝试次数
            RateLimiter::hit($this->throttleKey());

            // 抛出验证异常，提示认证失败
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // 认证成功，清除该 IP 和邮箱组合的频率限制计数
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * 确保当前登录请求未受到速率限制。
     *
     * 如果在短时间内尝试登录次数过多，会触发锁定事件，并抛出异常，
     * 告知用户需要等待一段时间才能再次尝试。
     *
     * @throws ValidationException 当请求频率超过预设限制时抛出。
     */
    public function ensureIsNotRateLimited(): void
    {
        // 检查当前登录尝试是否已达到或超过最大限制（5次）
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            // 如果未超过限制，允许请求继续
            return;
        }

        // 如果超过限制，触发 Laravel 的锁定事件
        event(new Lockout($this));

        // 获取剩余的等待时间（秒）
        $seconds = RateLimiter::availableIn($this->throttleKey());

        // 抛出验证异常，提示用户已达到频率限制并提供等待时间
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60), // 将秒转换为分钟（向上取整）
            ]),
        ]);
    }

    /**
     * 生成用于速率限制的唯一键。
     *
     * 此键结合了用户邮箱（转换为小写并去除特殊字符）和客户端 IP 地址，
     * 确保对特定用户从特定 IP 的登录尝试进行独立限制。
     *
     * @return string 用于速率限制的唯一标识符。
     */
    public function throttleKey(): string
    {
        // 使用用户邮箱（小写且转译）和 IP 地址生成一个唯一的节流键
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}