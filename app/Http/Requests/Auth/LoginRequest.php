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
 * 处理用户登录请求，包括表单验证规则定义、
 * 认证逻辑和请求频率限制
 */
class LoginRequest extends FormRequest
{
    /**
     * 确定用户是否有权发起此请求
     *
     * @return bool 是否授权
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取适用于请求的验证规则
     *
     * @return array 验证规则
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * 尝试对用户进行身份验证
     * 
     * 验证用户凭据并在失败时增加请求频率限制计数
     *
     * @throws ValidationException 当身份验证失败时抛出
     * @return void
     */
    public function authenticate(): void
    {
        // 检查是否已超过频率限制
        $this->ensureIsNotRateLimited();
        
        // 尝试登录
        if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // 登录失败，增加频率限制计数
            RateLimiter::hit($this->throttleKey());
            
            // 抛出验证异常
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
        
        // 登录成功，清除频率限制
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * 确保请求未受到速率限制
     * 
     * 检查当前IP和邮箱组合是否已达到请求频率限制
     *
     * @throws ValidationException 当请求频率超过限制时抛出
     * @return void
     */
    public function ensureIsNotRateLimited(): void
    {
        // 检查是否已超过尝试次数(5次)
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        
        // 触发锁定事件
        event(new Lockout($this));
        
        // 计算剩余等待时间
        $seconds = RateLimiter::availableIn($this->throttleKey());
        
        // 抛出验证异常，提示用户等待
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * 获取用于速率限制的键
     * 
     * 结合用户邮箱和IP地址创建一个唯一的限制键
     *
     * @return string 限制键
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}