<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import EditorsList from '@/Components/Wiki/EditorsList.vue';
import WikiPreviewPane from '@/Components/Wiki/WikiPreviewPane.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import axios from 'axios';

// --- Props and Composables ---
const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false },
    editorIsEditable: { type: Boolean, default: true },
    errors: Object,
    initialVersionId: { type: Number, default: null },
    initialVersionNumber: { type: [Number, String], default: 0 },
});

// --- Refs and Reactive State ---
const tiptapEditorRef = ref(null);
const flashMessageRef = ref(null);
const showPreviewPane = ref(true);
const currentBaseVersionId = ref(props.initialVersionId);
const currentBaseVersionNumber = ref(props.initialVersionNumber);
const lastSuccessfulSaveVersionId = ref(props.initialVersionId);
const isOutdated = ref(false);
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);
const showStaleVersionModal = ref(false);
const staleVersionData = ref({
    current_version_id: null,
    current_version_number: null,
    diff_base_vs_current: '',
    diff_user_vs_current: '',
    current_content: '',
    current_version_creator: '',
    current_version_updated_at: '',
});
const isSaving = ref(false);
const isForcingConflict = ref(false);
const isDiscarding = ref(false);
const isMobile = ref(false); // Added for mobile view check

// --- Form ---
const form = useForm({
    title: props.page.title,
    content: props.content,
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: currentBaseVersionId.value,
    force_conflict: false,
});

// --- Computed Properties ---
const computedEditorIsEditable = computed(() => {
    return props.editorIsEditable && !isOutdated.value && !showStaleVersionModal.value;
});

const editorStatusBar = computed(() => {
    // ... (status bar logic remains the same as before) ...
    if (isOutdated.value && !showStaleVersionModal.value) {
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '页面已过时，请处理版本差异！' };
    }
    if (showStaleVersionModal.value) {
        return { class: 'text-yellow-500 dark:text-yellow-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '检测到版本冲突，请在弹窗中选择操作。' };
    }
    if (form.errors.general && !form.errors.general.includes('已被更新') && !form.errors.general.includes('Stale') && !form.errors.general.includes('stale')) {
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-circle'], spin: false, message: form.errors.general };
    }
    if (autosaveStatus.value) {
        switch (autosaveStatus.value.type) {
            case 'success': return { class: 'text-green-600 dark:text-green-400', icon: ['fas', 'check-circle'], spin: false, message: autosaveStatus.value.message };
            case 'error': return { class: 'text-red-600 dark:text-red-400', icon: ['fas', 'exclamation-circle'], spin: false, message: autosaveStatus.value.message };
            case 'pending': return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: autosaveStatus.value.message };
            case 'info': return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'info-circle'], spin: false, message: autosaveStatus.value.message };
            case 'warning': return { class: 'text-yellow-600 dark:text-yellow-400', icon: ['fas', 'exclamation-triangle'], spin: false, message: autosaveStatus.value.message };
            default: return { class: 'text-gray-500 dark:text-gray-400', icon: ['fas', 'info-circle'], spin: false, message: autosaveStatus.value.message };
        }
    }
    if (hasUnsavedChanges.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '有未保存的更改' };
    }
    if (localLastSaved.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'save'], spin: false, message: `草稿已于 ${formatDateTime(localLastSaved.value)} 自动保存` };
    }
    return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '无更改' };
});

const hasUnsavedChanges = computed(() => form.isDirty);

// Dynamically calculate editor/preview pane classes based on visibility
const editorPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'w-full md:w-1/2' : 'w-full';
});
const previewPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'w-full md:w-1/2' : 'hidden';
});

// --- Methods ---
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value) {
        showPreviewPane.value = false; // Optionally hide preview on mobile by default
    }
};

const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

const onDraftSaved = (status) => {
    // ... (draft save logic remains the same) ...
    if (isOutdated.value || showStaleVersionModal.value) return;
    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    if (form.errors.general && !form.errors.general.includes('已被更新') && !form.errors.general.includes('Stale')) {
        form.clearErrors('general');
    }
    autosaveStatus.value = status;
    if (status?.type === 'error' && !isOutdated.value) {
        form.setError('general', status.message);
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'error' && !isOutdated.value) {
                autosaveStatus.value = null;
                form.clearErrors('general');
            }
        }, 5000);
    } else if (status?.type !== 'pending') {
        setTimeout(() => {
            if (autosaveStatus.value && autosaveStatus.value.type !== 'error' && !isOutdated.value) {
                autosaveStatus.value = null;
            }
        }, 3000);
    }
};

const handleEditorStatusUpdate = (status) => {
    if (isOutdated.value || showStaleVersionModal.value) return;
    autosaveStatus.value = status;
};

const savePage = async () => {
    // ... (save page logic remains the same, including pre-checks and error handling) ...
    if (isSaving.value) return;
    isSaving.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在提交保存...' };
    form.clearErrors();

    // Pre-check: If page is outdated and modal isn't showing, show modal
    if (isOutdated.value && !showStaleVersionModal.value) {
        autosaveStatus.value = { type: 'error', message: '页面已过时，请处理冲突' };
        isSaving.value = false;
        flashMessageRef.value?.addMessage('error', '页面已被其他用户更新，请选择如何处理！');
        handleSaveError({ // Re-trigger the error handling to show modal data if available
            response: {
                status: 409,
                data: staleVersionData.value.current_version_id ? {
                    status: 'stale_version',
                    ...staleVersionData.value
                } : {
                    status: 'stale_version',
                    message: '页面已被更新，请处理版本差异。' // Fallback message
                }
            }
        });
        return;
    }

    // Pre-check: If modal is already showing, prompt user
    if (showStaleVersionModal.value) {
        flashMessageRef.value?.addMessage('warning', '请先在弹出的窗口中处理版本冲突！');
        isSaving.value = false;
        autosaveStatus.value = { type: 'warning', message: '请在弹窗中操作' };
        return;
    }

    // Editor checks
    if (!tiptapEditorRef.value?.editor) {
        form.setError('general', '编辑器实例丢失，请刷新页面。');
        autosaveStatus.value = { type: 'error', message: '编辑器错误' };
        flashMessageRef.value?.addMessage('error', '编辑器错误，请刷新页面。');
        isSaving.value = false;
        return;
    }
    const currentContent = tiptapEditorRef.value.editor.getHTML();
    if (currentContent === '<p></p>' || !currentContent.trim()) {
        form.setError('content', '内容不能为空。');
        autosaveStatus.value = { type: 'error', message: '内容不能为空' };
        flashMessageRef.value?.addMessage('error', '内容不能为空。');
        isSaving.value = false;
        return;
    }

    // Prepare form data
    form.content = currentContent;
    form.version_id = currentBaseVersionId.value; // Send the base version ID
    form.force_conflict = false; // Explicitly set for normal save

    console.log(`Submitting save request for page ${props.page.id} based on version ID: ${form.version_id}`);

    try {
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());
        console.log("Save request attempt finished. Response data:", response.data);

        if (response.data && response.data.status) {
            const responseData = response.data;
            if (responseData.status === 'success') {
                handleSaveSuccess(responseData);
            } else if (responseData.status === 'no_changes') {
                handleNoChanges(responseData);
            } else if (responseData.status === 'conflict_forced') {
                handleConflictForced(responseData);
            } else {
                console.warn("Received unexpected success status:", responseData.status);
                autosaveStatus.value = { type: 'info', message: responseData.message || '操作完成，但状态未知。' };
                flashMessageRef.value?.addMessage('info', responseData.message || '操作完成，但状态未知。');
                if (responseData.redirect_url) router.visit(responseData.redirect_url);
                else router.visit(route('wiki.show', props.page.slug));
            }
        } else {
            console.error("Save response body missing expected status field:", response);
            autosaveStatus.value = { type: 'error', message: '服务器响应格式错误' };
            flashMessageRef.value?.addMessage('error', '保存似乎成功，但服务器响应异常，建议刷新确认。');
        }
    } catch (error) {
        handleSaveError(error);
    } finally {
        isSaving.value = false;
        // Clear 'pending' status only if no modal is shown
        if (autosaveStatus.value?.type === 'pending' && !showStaleVersionModal.value) {
            autosaveStatus.value = null;
        }
    }
};

const handleSaveSuccess = (responseData) => {
    // ... (logic remains the same) ...
    const newVersionId = responseData.new_version_id;
    const newVersionNumber = responseData.new_version_number;
    console.log('Save successful:', responseData.message);
    if (newVersionId != null && newVersionNumber != null) {
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId;
        form.version_id = currentBaseVersionId.value; // Update form's reference
        isOutdated.value = false; // Reset outdated status
        console.log(`Save success! New base version set to ID: ${currentBaseVersionId.value}, Number: ${currentBaseVersionNumber.value}`);
    } else {
        console.warn("Success response missing new version details. Base version not updated.");
    }
    form.comment = ''; // Clear comment field
    form.defaults(); // Set current state as default
    form.reset(); // Reset dirty state
    form.clearErrors();
    localLastSaved.value = null; // Clear draft info
    autosaveStatus.value = { type: 'success', message: responseData.message || '页面保存成功！' };
    flashMessageRef.value?.addMessage('success', responseData.message || '页面保存成功！');
    if (responseData.redirect_url) {
        // Redirect to show page after successful save
        router.visit(responseData.redirect_url);
    } else {
        // Should ideally redirect, but stay on page as fallback
        console.warn("Redirect URL missing in success response. Staying on edit page.");
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000); // Clear status after a while
    }
};

const handleNoChanges = (responseData) => {
    // ... (logic remains the same) ...
    autosaveStatus.value = { type: 'info', message: responseData.message || '未检测到更改。' };
    flashMessageRef.value?.addMessage('info', responseData.message || '未检测到更改，页面未更新。');
    form.defaults();
    form.reset(); // Reset dirty state
    setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 3000);
    if (responseData.redirect_url) {
        console.log("No changes detected, staying on edit page unless user cancels.");
        // Optionally redirect if needed based on UX design: router.visit(responseData.redirect_url);
    }
};

const handleConflictForced = (responseData) => {
    // ... (logic remains the same) ...
    autosaveStatus.value = { type: 'warning', message: responseData.message || '更改已保存但标记为冲突。' };
    flashMessageRef.value?.addMessage('warning', responseData.message || '您的更改已保存，但与当前版本冲突。页面已被锁定，请等待处理。');
    form.defaults();
    form.reset(); // Reset form state
    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        // Fallback redirect to show page if specific conflict redirect URL is not provided
        router.visit(route('wiki.show', props.page.slug));
    }
};

const handleSaveError = (error) => {
    // ... (error handling logic remains the same, sets stale flags/modal) ...
    console.error("Save error occurred:", error);
    if (error.response) {
        // Handle 409 Stale Version Error
        if (error.response.status === 409 && error.response.data?.status === 'stale_version') {
            console.log("Stale version detected via 409 response:", error.response.data);
            // Store stale data to display diffs later
            staleVersionData.value = {
                ...error.response.data,
                // Ensure default values if some keys are missing
                current_version_id: error.response.data.current_version_id ?? null,
                current_version_number: error.response.data.current_version_number ?? null,
                diff_base_vs_current: error.response.data.diff_base_vs_current ?? '',
                diff_user_vs_current: error.response.data.diff_user_vs_current ?? '',
                current_content: error.response.data.current_content ?? '',
                current_version_creator: error.response.data.current_version_creator ?? '',
                current_version_updated_at: error.response.data.current_version_updated_at ?? '',
            };
            isOutdated.value = true; // Mark the state as outdated
            showStaleVersionModal.value = true; // Show the conflict modal
            autosaveStatus.value = { type: 'error', message: '版本冲突，请选择操作' };
            if (tiptapEditorRef.value) {
                // Potentially pause autosave if the modal is open
                console.log("Autosave should be paused due to conflict modal.");
                // Add logic here if autosave needs explicit pausing
            }
        }
        // Handle 422 Validation Error
        else if (error.response.status === 422) {
            form.errors = error.response.data.errors; // Set Inertia form errors
            const firstError = Object.values(error.response.data.errors).flat()[0];
            autosaveStatus.value = { type: 'error', message: firstError || '提交的内容有误' };
            flashMessageRef.value?.addMessage('error', firstError || '提交的内容有误，请检查表单。');
            console.error("Validation error:", error.response.data.errors);
        }
        // Handle other server errors
        else {
            const errorMsg = error.response.data?.message || error.message || '保存时发生未知服务器错误。';
            form.setError('general', errorMsg); // Set a general form error
            autosaveStatus.value = { type: 'error', message: errorMsg };
            flashMessageRef.value?.addMessage('error', errorMsg);
        }
    } else {
        // Handle network or non-response errors
        const netErrorMsg = '网络连接错误或请求无法发送。';
        form.setError('general', netErrorMsg);
        autosaveStatus.value = { type: 'error', message: '网络错误' };
        flashMessageRef.value?.addMessage('error', netErrorMsg);
        console.error("Network or other non-response save error:", error);
    }
};

const handleForceConflict = async () => {
    // ... (force conflict logic remains the same) ...
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isForcingConflict.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在强制提交...' };
    showStaleVersionModal.value = false; // Close modal

    form.force_conflict = true;
    form.version_id = currentBaseVersionId.value; // Send base version ID
    form.content = tiptapEditorRef.value?.editor?.getHTML() || form.content; // Ensure latest editor content

    console.log(`Submitting Force Conflict request for page ${props.page.id} based on version ID: ${form.version_id}`);

    try {
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());
        console.log("Force conflict submission successful. Response:", response.data);

        if (response.data?.status === 'conflict_forced') {
            handleConflictForced(response.data); // Use the dedicated handler
        } else {
            console.warn("Force conflict resulted in unexpected status:", response.data?.status);
            // Treat as info, redirect user
            flashMessageRef.value?.addMessage('info', '操作完成，页面可能已标记为冲突。');
            router.visit(route('wiki.show', props.page.slug));
        }
    } catch (error) {
        handleSaveError(error); // Use standard error handling
    } finally {
        isForcingConflict.value = false;
        form.force_conflict = false; // Reset flag
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null; // Clear pending status
    }
};

const handleDiscardAndEditNew = async () => {
    // ... (discard and edit new logic remains the same) ...
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isDiscarding.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在加载最新版本...' };
    showStaleVersionModal.value = false; // Close modal

    try {
        // Attempt to delete user's draft first
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft for page ${props.page.id} deleted.`);
            localLastSaved.value = null; // Clear local draft time
        } catch (draftError) {
            console.warn("Failed to delete draft, proceeding with loading new version.", draftError);
        }

        // Get new version data from stale data
        const newContent = staleVersionData.value.current_content || '<p></p>';
        const newVersionId = staleVersionData.value.current_version_id;
        const newVersionNumber = staleVersionData.value.current_version_number;

        // Update form and editor content
        form.content = newContent;
        if (tiptapEditorRef.value?.editor) {
            tiptapEditorRef.value.editor.commands.setContent(newContent, false);
        } else {
            console.warn("Editor instance not found when trying to discard and load new content.");
            // Attempt a full page reload as a fallback
            router.reload({ preserveScroll: true });
            isDiscarding.value = false;
            return; // Stop further execution here
        }

        // Update version references
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId; // Update last saved ref
        form.version_id = currentBaseVersionId.value;

        // Reset flags and form state
        isOutdated.value = false;
        form.reset();       // Resets form values (may overwrite content just set, handle carefully)
        form.defaults();    // Set current state (including new content) as the clean default
        form.clearErrors(); // Clear any previous errors

        const successMsg = `已加载最新版本 v${currentBaseVersionNumber.value || '?'}. 更改已放弃，请继续编辑。`;
        autosaveStatus.value = { type: 'success', message: successMsg };
        flashMessageRef.value?.addMessage('success', successMsg);
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000);

    } catch (error) {
        console.error("Error discarding changes and loading new version:", error);
        const errorMsg = '加载最新版本时出错，请尝试刷新页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
    } finally {
        isDiscarding.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

const handleDiscardAndExit = async () => {
    // ... (discard and exit logic remains the same) ...
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isDiscarding.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃编辑...' };
    showStaleVersionModal.value = false; // Close modal

    try {
        // Attempt to delete draft before leaving
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft for page ${props.page.id} deleted before exiting.`);
        } catch (draftError) {
            console.warn("Failed to delete draft before exiting.", draftError);
        }

        // Redirect to the show page
        router.visit(route('wiki.show', props.page.slug));

        // Note: The page will navigate away, so cleanup might not be strictly needed here,
        // but it's good practice.
        autosaveStatus.value = null;
        isDiscarding.value = false; // Reset state in case navigation fails

    } catch (error) {
        console.error("Error discarding and exiting:", error);
        const errorMsg = '放弃编辑并退出时出错，请手动返回。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        isDiscarding.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

const refreshPage = () => {
    // ... (refresh logic remains the same) ...
    if (isSaving.value || isDiscarding.value || isForcingConflict.value) {
        alert("当前有操作正在进行，请稍后再刷新。"); return;
    }
    if (form.isDirty) {
        if (!confirm("刷新页面将丢失未保存的草稿或编辑更改，确定要刷新吗？")) return;
    }
    console.log("Refreshing page data using router.reload...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面...' };

    router.reload({
        // Specify which props to reload from the server
        only: [
            'page', 'content', 'categories', 'tags', 'hasDraft',
            'lastSaved', 'canResolveConflict', 'isConflict',
            'editorIsEditable', 'errors', 'initialVersionId',
            'initialVersionNumber'
        ],
        preserveState: false, // Force fresh data, false usually makes more sense here
        preserveScroll: true, // Keep scroll position
        onSuccess: (page) => {
            console.log("Page reloaded successfully via router.reload.");
            const newProps = page.props; // Use the props from the reload response

            // Update form data
            form.title = newProps.page.title;
            form.content = newProps.content; // Update form model if needed, editor content updates separately
            form.category_ids = newProps.page.category_ids || [];
            form.tag_ids = newProps.page.tag_ids || [];
            form.comment = ''; // Reset comment

            // Update component state from new props
            currentBaseVersionId.value = newProps.initialVersionId;
            currentBaseVersionNumber.value = newProps.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newProps.initialVersionId; // Reset last save reference
            form.version_id = currentBaseVersionId.value; // Update form version reference
            isOutdated.value = false; // Reset outdated flag
            showStaleVersionModal.value = false; // Ensure modal is closed

            // Explicitly reset form dirty state AFTER updating its values
            form.defaults(); // Set current values as the new 'clean' state
            form.reset(); // Reset form's dirty state/errors

            // Update editor content and state
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.commands.setContent(newProps.content, false); // Update editor content
                tiptapEditorRef.value.editor.setEditable(newProps.editorIsEditable && !isOutdated.value); // Set editor editable state
            } else {
                console.warn("Editor ref not available after reload.");
            }

            localLastSaved.value = newProps.lastSaved ? new Date(newProps.lastSaved) : null; // Update draft time

            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            flashMessageRef.value?.addMessage('success', '页面数据已刷新！');
            setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 2000); // Clear status message
        },
        onError: (errors) => {
            console.error("Page reload failed:", errors);
            autosaveStatus.value = { type: 'error', message: '刷新失败' };
            flashMessageRef.value?.addMessage('error', '刷新页面数据失败，请稍后重试或手动刷新浏览器。');
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 3000);
        }
    });
};

// --- Realtime Update Handling ---
let echoChannel = null;

const handleRealtimeVersionUpdate = (newVersionId) => {
    if (newVersionId != null && newVersionId === lastSuccessfulSaveVersionId.value) {
        console.log(`Ignoring Echo update for version ${newVersionId} because it matches the last successful save by this user.`);
        return; // Don't mark as outdated if it's our own save
    }
    if (newVersionId != null && newVersionId !== currentBaseVersionId.value) {
        console.log(`Page outdated detected via Echo! Current base: ${currentBaseVersionId.value}, Newest DB: ${newVersionId}`);
        if (!showStaleVersionModal.value) { // Only update state if modal isn't already showing
            isOutdated.value = true;
            autosaveStatus.value = { type: 'error', message: '注意：页面已被其他用户更新！' };
            // Optionally trigger the modal fetch logic if needed immediately
            // savePage(); // Or a lighter check method if available
        } else {
            console.log("Echo update received while conflict modal is open, ignoring state change.");
        }
    } else {
        console.log(`Received Echo update for version ${newVersionId}, which is the current base or invalid/null. No action needed.`);
    }
};

const setupEchoListener = () => {
    if (!window.Echo) { console.warn("Echo is not initialized!"); return; }
    const channelName = `wiki.page.${props.page.id}`;
    try {
        echoChannel = window.Echo.channel(channelName);
        echoChannel.listen('.page.version.updated', (data) => {
            console.log('Received page.version.updated event via Echo:', data);
            handleRealtimeVersionUpdate(data.newVersionId);
        });
        echoChannel.error((error) => { console.error(`Echo channel error on ${channelName}:`, error); });
        console.log(`Listening on Echo channel: ${channelName}`);
    } catch (error) {
        console.error(`Error setting up Echo listener on channel ${channelName}:`, error);
    }
};

const cleanupEchoListener = () => {
    if (echoChannel) {
        try {
            echoChannel.stopListening('.page.version.updated');
            window.Echo.leave(`wiki.page.${props.page.id}`);
            console.log(`Stopped listening and left Echo channel: wiki.page.${props.page.id}`);
        } catch (e) { console.error("Error stopping Echo listener or leaving channel:", e); }
        echoChannel = null;
    }
};

// --- Preview Pane Helper ---
const openPreviewInNewTab = () => {
    // ... (logic remains the same) ...
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    if (!form.title.trim()) { alert('请先输入页面标题再进行预览！'); return; }
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') { alert('请先输入页面内容再进行预览！'); return; }

    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf; // Get CSRF token from page props

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank'; // Open in new tab
    tempForm.style.display = 'none';

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    // Add other fields
    const fields = {
        title: form.title,
        content: currentContent,
        category_ids: form.category_ids,
        tag_ids: form.tag_ids,
    };

    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            if (Array.isArray(value)) {
                // Handle arrays (like category_ids, tag_ids)
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`; // Use PHP array syntax
                    input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
                // Handle simple values
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                tempForm.appendChild(input);
            }
        }
    }

    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
};

// --- Lifecycle Hooks ---
onMounted(() => {
    console.log("Edit.vue mounted. Initial editable state:", props.editorIsEditable);
    console.log("Initial Base Version ID:", currentBaseVersionId.value, "Number:", currentBaseVersionNumber.value);
    setupEchoListener();

    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0) {
            // Update form errors if props.errors changes (e.g., after submission failure)
            form.errors = newErrors;
            // Display general error messages via flash
            if (newErrors.general) {
                flashMessageRef.value?.addMessage('error', newErrors.general);
            }
        }
    }, { deep: true, immediate: true });

    // Watch for changes in the initial version prop (e.g., after a successful save handled by parent)
    watch(() => props.initialVersionId, (newVal, oldVal) => {
        if (newVal !== oldVal && newVal !== currentBaseVersionId.value) {
            console.log(`Base Version ID prop changed from ${oldVal} to ${newVal}. Updating state.`);
            currentBaseVersionId.value = newVal;
            currentBaseVersionNumber.value = props.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newVal;
            form.version_id = currentBaseVersionId.value;
            isOutdated.value = false; // Reset outdated status
            showStaleVersionModal.value = false; // Close modal if open
            form.clearErrors(); // Clear any stale errors
        }
    });

    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);
});

onBeforeUnmount(() => {
    console.log("Edit.vue unmounting...");
    cleanupEchoListener();
    window.removeEventListener('resize', updateMobileStatus);

    // Autosave on unload using sendBeacon if possible and needed
    if (computedEditorIsEditable.value && form.isDirty && !showStaleVersionModal.value && tiptapEditorRef.value?.editor) {
        if (navigator.sendBeacon) {
            console.log("Attempting to save draft on unmount via sendBeacon");
            const url = route('wiki.save-draft', props.page.id);
            const formData = new FormData();
            formData.append('content', tiptapEditorRef.value.editor.getHTML());
            formData.append('_token', pageProps.csrf); // Include CSRF token
            const success = navigator.sendBeacon(url, formData);
            console.log("sendBeacon call returned:", success);
        } else {
            console.warn("sendBeacon not supported, cannot reliably save draft on unmount/unload.");
            // Consider alternative sync save for older browsers if critical,
            // but be aware it might delay unloading.
        }
    } else {
        console.log("Skipping draft save on unmount. Reason:", {
            editable: computedEditorIsEditable.value,
            dirty: form.isDirty,
            modal: showStaleVersionModal.value,
            editor: !!tiptapEditorRef.value?.editor
        });
    }
});

</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑: ${page.title}`" />
        <!-- Unified Card Container -->
        <div class="container mx-auto py-6 px-4 flex flex-col h-[calc(100vh-theme(space.24))]">
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 flex flex-col flex-grow overflow-hidden">

                <!-- Header Section (Moved inside card) -->
                <div class="flex flex-col md:flex-row justify-between md:items-start mb-3 gap-y-2 flex-shrink-0 z-10">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑: {{ page.title }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            当前编辑基于版本: v{{ currentBaseVersionNumber || '?' }}
                            <span class="hidden sm:inline">| 最后成功保存的服务器版本ID: {{ lastSuccessfulSaveVersionId ?? '无'
                                }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']"
                                class="mr-1 h-3 w-3" />
                            {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <button @click="refreshPage" class="btn-secondary text-sm" title="刷新页面数据（将丢失未保存的草稿或编辑更改）">
                            <font-awesome-icon :icon="['fas', 'sync-alt']" :spin="false" class="h-3 w-3" /> 刷新
                        </button>
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览页面">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1 h-3 w-3" /> 在新标签页预览
                        </button>
                        <Link :href="route('wiki.show', page.slug)" class="btn-secondary text-sm">
                        取消
                        </Link>
                        <button @click="savePage" class="btn-primary text-sm"
                            :disabled="!computedEditorIsEditable || form.processing || isSaving"
                            :title="isOutdated && !showStaleVersionModal ? '页面已过时，请点击保存处理' : (!computedEditorIsEditable ? '当前无法编辑（锁定或冲突）' : '保存更改')">
                            <font-awesome-icon v-if="form.processing || isSaving" :icon="['fas', 'spinner']" spin
                                class="mr-1 h-3 w-3" />
                            {{ isSaving ? '正在保存...' : '保存页面' }}
                        </button>
                    </div>
                </div>

                <!-- Status/Alert Section (Moved inside card) -->
                <div class="mb-3 flex-shrink-0 space-y-2">
                    <div v-if="isConflict && !canResolveConflict" class="alert alert-error">
                        此页面当前处于冲突状态，并且您没有解决冲突的权限。无法编辑。
                    </div>
                    <div v-else-if="isConflict && canResolveConflict && !showStaleVersionModal"
                        class="alert alert-warning">
                        此页面当前处于冲突状态。您可以通过提交新的编辑来解决冲突。
                    </div>
                    <div v-if="isOutdated && !showStaleVersionModal" class="alert alert-error cursor-pointer"
                        @click="savePage">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                        <strong>页面已过时！</strong> 其他用户已更新此页面。
                        <span class="font-bold underline hover:text-red-800 dark:hover:text-red-300 ml-1">点击此处</span>
                        处理版本差异。
                    </div>
                    <div v-if="hasDraft && localLastSaved && !isOutdated && !showStaleVersionModal"
                        class="alert alert-info">
                        <font-awesome-icon :icon="['fas', 'save']" class="mr-2" />
                        加载了于 {{ formatDateTime(localLastSaved) }} 保存的本地草稿。
                    </div>
                    <EditorsList :pageId="page.id" />
                </div>

                <!-- Main Content Area (Editor + Preview) (Modified flex parent) -->
                <div class="flex-grow flex flex-col md:flex-row gap-6 min-h-0">
                    <!-- Editor Pane -->
                    <div :class="[editorPaneClass, 'flex flex-col']">
                        <form @submit.prevent="savePage"
                            class="space-y-5 flex-grow flex flex-col overflow-y-auto pr-2 editor-pane-scrollbar">
                            <!-- Title -->
                            <div>
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                        class="text-red-500">*</span></label>
                                <input id="title" v-model="form.title" type="text" class="input-field"
                                    :disabled="!computedEditorIsEditable" required />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <!-- Editor -->
                            <div class="flex-grow flex flex-col min-h-[300px]">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                        class="text-red-500">*</span></label>
                                <Editor v-model="form.content" :editable="computedEditorIsEditable"
                                    :autosave="computedEditorIsEditable" :pageId="page.id" @saved="onDraftSaved"
                                    @statusUpdate="handleEditorStatusUpdate" placeholder="开始编辑页面内容..."
                                    ref="tiptapEditorRef"
                                    class="flex-grow !min-h-[250px] border-gray-300 dark:border-gray-600 rounded-b-lg" />
                                <InputError class="mt-1" :message="form.errors.content || form.errors.version_id" />
                            </div>
                            <!-- Categories -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类 <span
                                        class="text-red-500">*</span></label>
                                <div class="checkbox-group" :class="{ 'disabled-group': !computedEditorIsEditable }">
                                    <div v-for="category in categories" :key="category.id" class="flex items-center">
                                        <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                            v-model="form.category_ids" :disabled="!computedEditorIsEditable"
                                            class="checkbox" />
                                        <label :for="`category-${category.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ category.name
                                            }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.category_ids" />
                            </div>
                            <!-- Tags -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                                <div class="checkbox-group" :class="{ 'disabled-group': !computedEditorIsEditable }">
                                    <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                        <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id"
                                            v-model="form.tag_ids" :disabled="!computedEditorIsEditable"
                                            class="checkbox" />
                                        <label :for="`tag-${tag.id}`"
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="form.errors.tag_ids" />
                            </div>
                            <!-- Comment -->
                            <div>
                                <label for="comment"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">提交说明
                                    <span class="text-xs text-gray-500 dark:text-gray-400">(可选)</span></label>
                                <textarea id="comment" v-model="form.comment" rows="2"
                                    :disabled="!computedEditorIsEditable" class="textarea-field"
                                    placeholder="例如：修正了XX数据..."></textarea>
                                <InputError class="mt-1" :message="form.errors.comment" />
                            </div>
                            <!-- Status Bar -->
                            <div
                                class="editor-status-bar flex justify-end items-center mt-auto text-right pr-1 flex-shrink-0 sticky bottom-0 bg-white/80 dark:bg-gray-900/80 pt-2">
                                <span v-if="editorStatusBar.message" :class="editorStatusBar.class"
                                    class="flex items-center justify-end text-xs">
                                    <font-awesome-icon :icon="editorStatusBar.icon" :spin="editorStatusBar.spin"
                                        class="mr-1 h-3 w-3" />
                                    {{ editorStatusBar.message }}
                                </span>
                            </div>
                        </form>
                    </div>
                    <!-- Preview Pane -->
                    <div :class="[previewPaneClass, 'flex flex-col min-h-0']">
                        <WikiPreviewPane
                            class="h-full border-gray-300 dark:border-gray-600 border rounded-lg overflow-hidden"
                            :form="form" :categories="categories" :tags="tags" :page="page"
                            :currentVersion="page.current_version" />
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="showStaleVersionModal" @close="showStaleVersionModal = false" maxWidth="4xl" :closeable="false">
            <div class="p-6 bg-gray-800 text-gray-200 rounded-lg shadow-xl">
                <h2 class="text-xl font-bold mb-4 text-yellow-400 flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                    页面版本冲突
                </h2>
                <p class="mb-4 text-sm">在您编辑期间，页面已被 <strong class="text-white">{{
                    staleVersionData.current_version_creator || '其他用户' }}</strong> 更新至 <strong>v{{
                            staleVersionData.current_version_number || '新' }}</strong> 版本。 ({{
                            staleVersionData.current_version_updated_at ? '更新于 ' + formatDateTime(new
                                Date(staleVersionData.current_version_updated_at)) : '' }})</p>
                <p class="mb-6 text-sm">请检查以下差异，并选择如何处理您的更改：</p>
                <!-- Diff Views -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 max-h-[50vh] overflow-y-auto p-3 bg-gray-900/70 rounded-md border border-gray-700">
                    <div
                        class="md:border-r border-gray-700 pr-0 md:pr-4 pb-4 md:pb-0 mb-4 md:mb-0 border-b md:border-b-0">
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-blue-400">您的更改</span> <span class="text-gray-500"> (基于 v{{
                                currentBaseVersionNumber }}) </span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本</span> <span class="text-gray-500"> (v{{
                                staleVersionData.current_version_number }})</span>
                        </h3>
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_user_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载差异信息。</p>'">
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-red-400">您的起点</span> <span class="text-gray-500"> (v{{
                                currentBaseVersionNumber }})</span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本</span> <span class="text-gray-500"> (v{{
                                staleVersionData.current_version_number }})</span>
                        </h3>
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_base_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载差异信息。</p>'">
                        </div>
                    </div>
                </div>
                <!-- Modal Actions -->
                <div
                    class="flex flex-col sm:flex-row justify-end items-center gap-3 mt-6 pt-4 border-t border-gray-700">
                    <button @click="handleForceConflict" class="btn-warning w-full sm:w-auto text-sm"
                        :disabled="isSaving || isForcingConflict || isDiscarding"
                        title="您的更改将被保存，但页面会被标记为“冲突”状态，需要有权限者稍后解决。">
                        <font-awesome-icon v-if="isForcingConflict" :icon="['fas', 'spinner']" spin class="mr-1" />
                        强制提交 (产生冲突)
                    </button>
                    <button @click="handleDiscardAndEditNew" class="btn-secondary w-full sm:w-auto text-sm"
                        :disabled="isSaving || isForcingConflict || isDiscarding"
                        title="丢弃您在此页面上的所有未保存更改，加载最新版本的内容，并清除草稿。">
                        <font-awesome-icon v-if="isDiscarding && !isForcingConflict" :icon="['fas', 'spinner']" spin
                            class="mr-1" />
                        放弃编辑，加载最新
                    </button>
                    <button @click="handleDiscardAndExit" class="btn-danger w-full sm:w-auto text-sm"
                        :disabled="isSaving || isForcingConflict || isDiscarding" title="丢弃您在此页面上的所有未保存更改，并退出编辑模式。">
                        <font-awesome-icon v-if="isDiscarding && !isForcingConflict && !isSaving"
                            :icon="['fas', 'spinner']" spin class="mr-1" />
                        放弃编辑并退出
                    </button>
                </div>
            </div>
        </Modal>
        <FlashMessage ref="flashMessageRef" />
    </MainLayout>
</template>
<style scoped>
.h-\[calc\(100vh-theme\(space\.24\)\)\] {
    /* Adjust if navbar height changes */
    height: calc(100vh - 6rem);
}

.container {
    height: 100%;
}

/* Main Card Style */
.flex-grow.bg-white\/80 {
    display: flex;
    flex-direction: column;
}

/* Ensure main content flex container takes available height and allows children to scroll */
.flex-grow.flex.flex-col.md\:flex-row {
    flex-grow: 1;
    /* Takes remaining vertical space */
    min-height: 0;
    /* Prevents flex items from overflowing parent in some scenarios */
}

/* Editor pane scrollbar */
.editor-pane-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
    /* thumb track */
}

.dark .editor-pane-scrollbar {
    scrollbar-color: #4b5563 #2d3748;
}

.editor-pane-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.editor-pane-scrollbar::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 3px;
}

.dark .editor-pane-scrollbar::-webkit-scrollbar-track {
    background: #2d3748;
}

.editor-pane-scrollbar::-webkit-scrollbar-thumb {
    background-color: #a0aec0;
    border-radius: 3px;
}

.dark .editor-pane-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}

/* Ensure editor container grows */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    /* Removed min-height here, handled by flex */
}

/* Let the editor content scroll if it overflows its container */
:deep(.editor-content) {
    flex-grow: 1;
    overflow-y: auto;
    /* Add scroll to editor content area if needed */
    min-height: 250px;
    /* Keep a minimum height */
}


/* Basic input/select/textarea styles */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

.textarea-field {
    min-height: 5rem;
    /* Example min-height for textarea */
}

/* Checkbox group styling */
.checkbox-group {
    @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

.checkbox-group.disabled-group {
    @apply bg-gray-100 dark:bg-gray-800/30 opacity-60 cursor-not-allowed;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed;
}

.checkbox:disabled+label {
    @apply cursor-not-allowed opacity-60;
}

/* Button styles */
.btn-primary,
.btn-secondary,
.btn-warning,
.btn-danger {
    @apply px-4 py-1.5 rounded-md transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

.btn-warning {
    @apply bg-yellow-500 text-white hover:bg-yellow-600;
}

.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700;
}

/* Alert styles */
.alert {
    @apply p-3 rounded-md border-l-4 mb-3 text-sm;
}

.alert-error {
    @apply bg-red-50 border-red-400 text-red-700 dark:bg-red-900/30 dark:text-red-300 dark:border-red-600;
}

.alert-warning {
    @apply bg-yellow-50 border-yellow-400 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-600;
}

.alert-info {
    @apply bg-blue-50 border-blue-400 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-600;
}

/* Editor status bar */
.editor-status-bar {
    @apply text-xs min-h-[16px];
}

/* Diff Modal Styles */
.diff-modal {
    max-height: 30vh;
    /* Limit height in modal */
    overflow-y: auto;
    background-color: #1f2937;
    border: 1px solid #4b5563;
    border-radius: 0.25rem;
    scrollbar-width: thin;
    scrollbar-color: #4b5563 #1f2937;
}

.diff-modal::-webkit-scrollbar {
    width: 5px;
}

.diff-modal::-webkit-scrollbar-track {
    background: #1f2937;
}

.diff-modal::-webkit-scrollbar-thumb {
    background-color: #4b5563;
}

.diff-modal :deep(table.diff) {
    font-size: 0.7rem;
    line-height: 1.3;
}

.diff-modal :deep(td) {
    padding: 0.1rem 0.3rem !important;
    border-color: #374151 !important;
}

.diff-modal :deep(th) {
    padding: 0.15rem 0.3rem !important;
    background-color: #2d3748 !important;
    border-color: #4b5563 !important;
}

.diff-modal :deep(td.lines-no) {
    background-color: #2d3748 !important;
    border-right-color: #4b5563 !important;
}

/* Diff color adjustments */
.diff-modal :deep(.ChangeDelete .Left) {
    background-color: rgba(153, 27, 27, 0.25) !important;
}

.diff-modal :deep(.ChangeDelete .Left del) {
    background-color: rgba(220, 38, 38, 0.35) !important;
    color: #fecaca !important;
}

.diff-modal :deep(.ChangeInsert .Right) {
    background-color: rgba(4, 100, 65, 0.25) !important;
}

.diff-modal :deep(.ChangeInsert .Right ins) {
    background-color: rgba(5, 150, 105, 0.35) !important;
    color: #a7f3d0 !important;
    text-decoration: none;
}

.diff-modal :deep(.ChangeReplace .Left) {
    background-color: rgba(153, 27, 27, 0.25) !important;
}

.diff-modal :deep(.ChangeReplace .Right) {
    background-color: rgba(4, 100, 65, 0.25) !important;
}

.diff-modal :deep(td.Left .ChangeReplace del) {
    background-color: rgba(220, 38, 38, 0.35) !important;
    color: #fecaca !important;
}

.diff-modal :deep(td.Right .ChangeReplace ins) {
    background-color: rgba(5, 150, 105, 0.35) !important;
    color: #a7f3d0 !important;
    text-decoration: none;
}


/* Ensure responsive layout adjustments */
@media (max-width: 767px) {
    .md\:flex-row {
        flex-direction: column;
    }

    .md\:w-1\/2 {
        width: 100%;
    }

    .editor-pane-scrollbar {
        padding-right: 0;
    }

    .flex-grow.flex.flex-col.md\:flex-row {
        gap: 1rem;
    }

    /* Add gap for column layout */
}
</style>