<?php

namespace App\Providers;

use App\Models\User;
use App\Models\WikiComment;
use App\Models\WikiPage;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\WikiCommentPolicy;
use App\Policies\WikiPagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

/**
 * AuthServiceProvider 类
 * 注册应用程序的认证/授权服务和策略。
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * 应用程序的模型策略映射。
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        WikiPage::class => WikiPagePolicy::class,
        WikiComment::class => WikiCommentPolicy::class,
    ];

    /**
     * 引导任何认证/授权服务。
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}