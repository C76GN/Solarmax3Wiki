<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate; // 如果你还需要定义 Gate，取消注释
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// 引入你的模型和策略
use App\Models\WikiComment;
use App\Policies\WikiCommentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 将你的模型和策略关联起来
        WikiComment::class => WikiCommentPolicy::class,
        // 如果还有其他 Policy，也在这里注册
        // Model::class => ModelPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // 如果你还需要定义 Gate 规则，可以在这里定义
        // Gate::define('edit-settings', function (User $user) {
        //     return $user->isAdmin();
        // });
    }
}
