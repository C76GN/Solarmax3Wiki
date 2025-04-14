<?php

namespace App\Providers;

use App\Models\User; // 引入 User
use App\Models\WikiComment;
use App\Models\WikiPage;    // 引入 WikiPage
use App\Policies\RolePolicy; // 引入 RolePolicy
use App\Policies\UserPolicy; // 引入 UserPolicy
use App\Policies\WikiCommentPolicy;
use App\Policies\WikiPagePolicy; // 引入 WikiPagePolicy
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role; // 引入 Spatie Role

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 你的模型 => 对应的 Policy
        User::class => UserPolicy::class,          // 注册 UserPolicy
        Role::class => RolePolicy::class,          // 注册 RolePolicy (关联 Spatie Role)
        WikiPage::class => WikiPagePolicy::class,    // 注册 WikiPagePolicy
        WikiComment::class => WikiCommentPolicy::class, // 保持 WikiCommentPolicy
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // 注册 Policies (这行通常默认存在)
        $this->registerPolicies();

        // 可以在这里定义 Gates (如果需要)，但 Policy 通常是更好的选择
        // Gate::define('view-dashboard', function (User $user) { ... });
    }
}
