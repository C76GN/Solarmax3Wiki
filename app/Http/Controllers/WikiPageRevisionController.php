<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wiki页面版本控制器
 * 
 * 负责管理Wiki页面的版本历史，包括查看版本列表、
 * 查看历史版本、比较版本差异和回滚到历史版本
 */
class WikiPageRevisionController extends Controller
{
    /**
     * 显示页面的版本历史列表
     *
     * @param WikiPage $page 当前页面
     * @return Response 版本列表页面
     */
    public function index(WikiPage $page): Response
    {
        // 获取页面的所有版本并分页
        $revisions = $page->revisions()
            ->with('creator')
            ->orderBy('version', 'desc')
            ->paginate(20);
            
        return Inertia::render('Wiki/Revisions/Revisions', [
            'page' => $page->only('id', 'title', 'current_version'), 
            'revisions' => $revisions,
            'can' => [
                'edit' => auth()->user()?->hasPermission('wiki.edit')
            ]
        ]);
    }

    /**
     * 显示页面的特定历史版本
     *
     * @param WikiPage $page 当前页面
     * @param int $version 要查看的版本号
     * @return Response 历史版本页面
     */
    public function show(WikiPage $page, $version): Response
    {
        // 获取指定版本的详细信息
        $revision = $page->revisions()
            ->where('version', $version)
            ->with('creator')
            ->firstOrFail();
            
        return Inertia::render('Wiki/Show', [
            'page' => array_merge(
                $page->only('id', 'title', 'current_version'), 
                [
                    'content' => $revision->content,
                    'revision' => $revision
                ]
            ),
            'can' => [
                'edit' => auth()->user()?->hasPermission('wiki.edit')
            ]
        ]);
    }

    /**
     * 比较页面的两个历史版本的差异
     *
     * @param WikiPage $page 当前页面
     * @param int $fromVersion 起始版本号
     * @param int $toVersion 目标版本号
     * @return Response 版本比较页面
     */
    public function compare(WikiPage $page, $fromVersion, $toVersion): Response
    {
        // 获取要比较的两个版本
        $fromRevision = $page->revisions()
            ->where('version', $fromVersion)
            ->with('creator')
            ->firstOrFail();
            
        $toRevision = $page->revisions()
            ->where('version', $toVersion)
            ->with('creator')
            ->firstOrFail();
            
        return Inertia::render('Wiki/Revisions/CompareRevisions', [
            'page' => $page->only('id', 'title'),
            'oldRevision' => $fromRevision,
            'newRevision' => $toRevision,
            'fromVersion' => (int)$fromVersion,
            'toVersion' => (int)$toVersion
        ]);
    }

    /**
     * 将页面回滚到历史版本
     *
     * @param Request $request 请求对象
     * @param WikiPage $page 当前页面
     * @param int $version 要回滚到的版本号
     * @return RedirectResponse 重定向响应
     */
    public function revert(Request $request, WikiPage $page, $version): RedirectResponse
    {
        // 检查用户权限
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }
        
        try {
            // 执行回滚操作
            $page->revertToVersion($version);
            
            return redirect()->route('wiki.show', $page->id)->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => "页面已恢复到版本 {$version}"
                ]
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => [
                    'type' => 'error',
                    'text' => '恢复失败：' . $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * 比较数据库中的页面与正在编辑中的页面
     *
     * @param WikiPage $page 当前页面
     * @param Request $request 请求对象
     * @return Response 在线比较页面
     */
    public function compareLive(WikiPage $page, Request $request): Response
    {
        // 获取数据库版本内容和正在编辑的内容
        $currentContent = $page->content;
        $editingContent = $request->input('content');
        
        return Inertia::render('Wiki/CompareLive', [
            'page' => $page->only('id', 'title'),
            'databaseVersion' => [
                'content' => $currentContent,
                'updated_at' => $page->updated_at
            ],
            'editingVersion' => [
                'content' => $editingContent
            ]
        ]);
    }
}