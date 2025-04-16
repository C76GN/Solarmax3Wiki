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

// --- Props & Page Data ---
const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true }, // Initial content (from DB or draft)
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false },
    isConflict: { type: Boolean, default: false }, // Initial conflict status from backend
    editorIsEditable: { type: Boolean, default: true }, // Whether editing is allowed (perms, lock etc)
    errors: Object, // Validation errors from Inertia
    initialVersionId: { type: Number, default: null }, // The version ID this edit session is based on
    initialVersionNumber: { type: [Number, String], default: 0 }, // The version number for display
});

// --- Refs and Reactive State ---
const tiptapEditorRef = ref(null);       // Ref to the Editor component
const flashMessageRef = ref(null);       // Ref to FlashMessage component
const showPreviewPane = ref(true);       // Control visibility of the preview pane
const currentBaseVersionId = ref(props.initialVersionId); // The version this *current edit session* is based on
const currentBaseVersionNumber = ref(props.initialVersionNumber); // Display number for the base version
const lastSuccessfulSaveVersionId = ref(props.initialVersionId); // Track the ID of the last *successfully saved* version by *this* user
const isOutdated = ref(false);          // Flag if the page has been updated by someone else *since page load* or *last save attempt*
const autosaveStatus = ref(null);       // Stores autosave status messages ({type, message})
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null); // Track last draft save time

// --- Conflict Handling State ---
const showStaleVersionModal = ref(false); // Controls the conflict resolution modal
const staleVersionData = ref({         // Data received from the server upon detecting a conflict (409)
    current_version_id: null,
    current_version_number: null,
    diff_base_vs_current: '',          // HTML diff: User's Base Version vs. DB Current Version
    diff_user_vs_current: '',          // HTML diff: User's Edits vs. DB Current Version
    current_content: '',               // Content of the DB Current Version
    current_version_creator: '',       // Creator of the DB Current Version
    current_version_updated_at: '',    // Timestamp of the DB Current Version
});

// --- Loading/Processing States ---
const isSaving = ref(false);              // True when the main "Save Page" request is in progress
const isForcingConflict = ref(false);     // True when "Force Conflict" modal action is processing
const isDiscarding = ref(false);          // True when "Discard" modal actions are processing

// --- Form Definition ---
const form = useForm({
    title: props.page.title,
    content: props.content, // Note: Tiptap's v-model binds this
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',               // User's comment for this save action
    version_id: currentBaseVersionId.value, // Sent to backend to check against DB current version
    force_conflict: false,    // Flag sent to backend if user chooses "Force Conflict"
});

// --- Computed Properties ---

// Determines if the editor itself should allow input, considering backend flags AND conflict state
const computedEditorIsEditable = computed(() => {
    // Not editable if backend says no, OR if outdated status is detected, OR if the conflict modal is active
    return props.editorIsEditable && !isOutdated.value && !showStaleVersionModal.value;
});

// Provides status messages for the editor's bottom bar
const editorStatusBar = computed(() => {
    if (isOutdated.value && !showStaleVersionModal.value) {
        // If outdated but modal not shown yet (likely detected by Echo or a failed background check)
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '页面已过时，请处理版本差异！' };
    }
    if (showStaleVersionModal.value) {
        // Explicitly showing message when modal is open
        return { class: 'text-yellow-500 dark:text-yellow-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '检测到版本冲突，请在弹窗中选择操作。' };
    }
    // Prioritize general form errors if present (but filter out specific conflict messages handled above)
    if (form.errors.general && !form.errors.general.includes('已被更新') && !form.errors.general.includes('Stale') && !form.errors.general.includes('stale')) {
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-circle'], spin: false, message: form.errors.general };
    }
    // Show current autosave status
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
    // Default / idle messages
    if (hasUnsavedChanges.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '有未保存的更改' };
    }
    if (localLastSaved.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'save'], spin: false, message: `草稿已于 ${formatDateTime(localLastSaved.value)} 自动保存` };
    }
    return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '无更改' };
});

// Simple check for unsaved changes (could be more sophisticated)
const hasUnsavedChanges = computed(() => form.isDirty);

// CSS classes for editor and preview panes based on visibility state
const editorPaneClass = computed(() => showPreviewPane.value ? 'w-full md:w-1/2' : 'w-full');
const previewPaneClass = computed(() => showPreviewPane.value ? 'w-full md:w-1/2' : 'hidden');

// --- Methods ---

// Toggles the visibility of the right-side preview pane
const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// Callback function when the Editor component successfully saves a draft
const onDraftSaved = (status) => {
    // Ignore draft saves if we are already in an outdated/conflict state
    if (isOutdated.value || showStaleVersionModal.value) return;

    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    // Clear general errors unless it's specifically the conflict error message
    if (form.errors.general && !form.errors.general.includes('已被更新') && !form.errors.general.includes('Stale')) {
        form.clearErrors('general');
    }
    autosaveStatus.value = status; // Update the status bar display

    // Handle draft save errors (but only show if not already outdated)
    if (status?.type === 'error' && !isOutdated.value) {
        form.setError('general', status.message); // Show error in form
        // Hide error message after a delay
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'error' && !isOutdated.value) {
                autosaveStatus.value = null;
                form.clearErrors('general');
            }
        }, 5000);
    } else if (status?.type !== 'pending') {
        // Hide success/info messages after a delay
        setTimeout(() => {
            if (autosaveStatus.value && autosaveStatus.value.type !== 'error' && !isOutdated.value) {
                autosaveStatus.value = null;
            }
        }, 3000);
    }
};

// Handles status updates *from* the Editor component (e.g., "pending save")
const handleEditorStatusUpdate = (status) => {
    if (isOutdated.value || showStaleVersionModal.value) return; // Ignore if outdated
    autosaveStatus.value = status;
};

// --- Main Save Logic ---
const savePage = async () => {
    if (isSaving.value) return; // Prevent double clicks
    isSaving.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在提交保存...' };
    form.clearErrors(); // Clear previous validation/general errors

    // Pre-check: If already marked as outdated (but modal not shown yet), trigger the modal flow
    if (isOutdated.value && !showStaleVersionModal.value) {
        autosaveStatus.value = { type: 'error', message: '页面已过时，请处理冲突' };
        isSaving.value = false;
        flashMessageRef.value?.addMessage('error', '页面已被其他用户更新，请选择如何处理！');
        // Trigger the modal manually by simulating the 409 error
        handleSaveError({
            response: {
                status: 409,
                data: {
                    status: 'stale_version',
                    message: '页面已被更新。请处理版本差异。',
                    // Try to get existing stale data or nulls
                    current_version_id: staleVersionData.value.current_version_id || null,
                    current_version_number: staleVersionData.value.current_version_number || null,
                    diff_base_vs_current: staleVersionData.value.diff_base_vs_current || '',
                    diff_user_vs_current: staleVersionData.value.diff_user_vs_current || '',
                    current_content: staleVersionData.value.current_content || '',
                    current_version_creator: staleVersionData.value.current_version_creator || '',
                    current_version_updated_at: staleVersionData.value.current_version_updated_at || '',
                }
            }
        });
        return;
    }

    // Pre-check: If modal is *already* showing, tell user to use modal buttons
    if (showStaleVersionModal.value) {
        flashMessageRef.value?.addMessage('warning', '请先在弹出的窗口中处理版本冲突！');
        isSaving.value = false;
        autosaveStatus.value = { type: 'warning', message: '请在弹窗中操作' };
        return;
    }

    // Ensure editor exists
    if (!tiptapEditorRef.value?.editor) {
        form.setError('general', '编辑器实例丢失，请刷新页面。');
        autosaveStatus.value = { type: 'error', message: '编辑器错误' };
        flashMessageRef.value?.addMessage('error', '编辑器错误，请刷新页面。');
        isSaving.value = false;
        return;
    }

    // Validate content is not empty
    const currentContent = tiptapEditorRef.value.editor.getHTML();
    if (currentContent === '<p></p>' || !currentContent.trim()) {
        form.setError('content', '内容不能为空。');
        autosaveStatus.value = { type: 'error', message: '内容不能为空' };
        flashMessageRef.value?.addMessage('error', '内容不能为空。');
        isSaving.value = false;
        return;
    }

    // Prepare data for submission
    form.content = currentContent;
    form.version_id = currentBaseVersionId.value; // Send the base version ID
    form.force_conflict = false; // Ensure this is false for a normal save attempt

    console.log(`Submitting save request for page ${props.page.id} based on version ID: ${form.version_id}`);

    // --- Perform the PUT request using axios ---
    try {
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());

        console.log("Save request attempt finished. Response data:", response.data);

        // Handle different successful response statuses from backend
        if (response.data && response.data.status) {
            const responseData = response.data;
            if (responseData.status === 'success') {
                handleSaveSuccess(responseData);
            } else if (responseData.status === 'no_changes') {
                handleNoChanges(responseData);
            } else if (responseData.status === 'conflict_forced') {
                // This case shouldn't happen on a normal save, but handle defensively
                handleConflictForced(responseData);
            } else {
                // Unexpected status
                console.warn("Received unexpected success status:", responseData.status);
                autosaveStatus.value = { type: 'info', message: responseData.message || '操作完成，但状态未知。' };
                flashMessageRef.value?.addMessage('info', responseData.message || '操作完成，但状态未知。');
                if (responseData.redirect_url) router.visit(responseData.redirect_url);
                else router.visit(route('wiki.show', props.page.slug)); // Fallback redirect
            }
        } else {
            // Malformed success response from server
            console.error("Save response body missing expected status field:", response);
            autosaveStatus.value = { type: 'error', message: '服务器响应格式错误' };
            flashMessageRef.value?.addMessage('error', '保存似乎成功，但服务器响应异常，建议刷新确认。');
        }

    } catch (error) {
        // --- Handle Errors (Network, 409 Conflict, 422 Validation, 500 etc.) ---
        handleSaveError(error);

    } finally {
        isSaving.value = false; // Always release the saving lock
        // Reset status if it's still 'pending' and modal wasn't triggered
        if (autosaveStatus.value?.type === 'pending' && !showStaleVersionModal.value) {
            autosaveStatus.value = null;
        }
    }
};

// --- Success Handlers for savePage ---
const handleSaveSuccess = (responseData) => {
    const newVersionId = responseData.new_version_id;
    const newVersionNumber = responseData.new_version_number;
    console.log('Save successful:', responseData.message);

    // Update the base version reference for subsequent saves
    if (newVersionId != null && newVersionNumber != null) {
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId; // Track this save
        form.version_id = currentBaseVersionId.value; // Update form's version ID
        isOutdated.value = false; // No longer outdated
        console.log(`Save success! New base version set to ID: ${currentBaseVersionId.value}, Number: ${currentBaseVersionNumber.value}`);
    } else {
        console.warn("Success response missing new version details. Base version not updated.");
    }

    form.comment = '';          // Clear the comment field
    form.defaults();            // Reset form defaults to current values
    form.reset();               // Reset form's dirty state
    form.clearErrors();       // Clear any previous errors

    localLastSaved.value = null; // Clear local draft time indicator

    // Show success messages
    autosaveStatus.value = { type: 'success', message: responseData.message || '页面保存成功！' };
    flashMessageRef.value?.addMessage('success', responseData.message || '页面保存成功！');

    // Redirect if instructed
    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        console.warn("Redirect URL missing in success response. Staying on edit page.");
        // Optionally hide success message after delay if staying on page
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000);
    }
};

const handleNoChanges = (responseData) => {
    autosaveStatus.value = { type: 'info', message: responseData.message || '未检测到更改。' };
    flashMessageRef.value?.addMessage('info', responseData.message || '未检测到更改，页面未更新。');
    // Reset dirty state as no changes were actually made according to backend
    form.defaults();
    form.reset();
    setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 3000);
    // Optionally redirect even if no changes, though perhaps less necessary
    if (responseData.redirect_url) {
        // router.visit(responseData.redirect_url);
        console.log("No changes detected, staying on edit page unless user cancels.");
    }
    // No need to reset isSaving here, happens in finally
};

// Handles the specific 'conflict_forced' success status from the backend
const handleConflictForced = (responseData) => {
    autosaveStatus.value = { type: 'warning', message: responseData.message || '更改已保存但标记为冲突。' };
    flashMessageRef.value?.addMessage('warning', responseData.message || '您的更改已保存，但与当前版本冲突。页面已被锁定，请等待处理。');
    form.defaults();
    form.reset();
    // Redirect to the show page to see the conflict status
    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        router.visit(route('wiki.show', props.page.slug));
    }
};

// --- Error Handler for savePage ---
const handleSaveError = (error) => {
    console.error("Save error occurred:", error);
    if (error.response) {
        // Handle specific HTTP status codes
        if (error.response.status === 409 && error.response.data?.status === 'stale_version') {
            // *** This is where the CONFLICT MODAL is triggered ***
            console.log("Stale version detected via 409 response:", error.response.data);
            staleVersionData.value = error.response.data; // Store conflict data
            isOutdated.value = true;                    // Mark as outdated
            showStaleVersionModal.value = true;         // Show the modal
            autosaveStatus.value = { type: 'error', message: '版本冲突，请选择操作' };
            // Stop draft autosave
            if (tiptapEditorRef.value) {
                // Assuming Editor component has a method or prop to control autosave
                // tiptapEditorRef.value.pauseAutosave(); // Or similar mechanism
                console.log("Autosave should be paused due to conflict modal.");
            }
        } else if (error.response.status === 422) {
            // Validation errors
            form.errors = error.response.data.errors;
            const firstError = Object.values(error.response.data.errors).flat()[0];
            autosaveStatus.value = { type: 'error', message: firstError || '提交的内容有误' };
            flashMessageRef.value?.addMessage('error', firstError || '提交的内容有误，请检查表单。');
            console.error("Validation error:", error.response.data.errors);
        } else {
            // Other server errors (500, 403, etc.)
            const errorMsg = error.response.data?.message || error.message || '保存时发生未知服务器错误。';
            form.setError('general', errorMsg);
            autosaveStatus.value = { type: 'error', message: errorMsg };
            flashMessageRef.value?.addMessage('error', errorMsg);
        }
    } else {
        // Network errors or request setup errors
        const netErrorMsg = '网络连接错误或请求无法发送。';
        form.setError('general', netErrorMsg);
        autosaveStatus.value = { type: 'error', message: '网络错误' };
        flashMessageRef.value?.addMessage('error', netErrorMsg);
        console.error("Network or other non-response save error:", error);
    }
};

// --- Conflict Modal Actions ---

// Option 1: Force Save (Marks page as 'conflict')
const handleForceConflict = async () => {
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isForcingConflict.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在强制提交...' };
    showStaleVersionModal.value = false; // Close modal before request

    form.force_conflict = true;          // Set the flag
    form.version_id = currentBaseVersionId.value; // Use the ORIGINAL base version ID
    form.content = tiptapEditorRef.value?.editor?.getHTML() || form.content; // Get latest editor content

    console.log(`Submitting Force Conflict request for page ${props.page.id} based on version ID: ${form.version_id}`);

    try {
        // Re-use the main update endpoint, backend logic handles `force_conflict: true`
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());
        console.log("Force conflict submission successful. Response:", response.data);
        if (response.data?.status === 'conflict_forced') {
            handleConflictForced(response.data); // Handle the specific response
        } else {
            console.warn("Force conflict resulted in unexpected status:", response.data?.status);
            // Fallback redirect / message
            flashMessageRef.value?.addMessage('info', '操作完成，页面可能已标记为冲突。');
            router.visit(route('wiki.show', props.page.slug));
        }
    } catch (error) {
        handleSaveError(error); // Use the general error handler
    } finally {
        isForcingConflict.value = false;
        form.force_conflict = false; // Reset flag
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null; // Clear pending status
    }
};

// Option 2: Discard User's Edits and Load/Edit the Latest Version
const handleDiscardAndEditNew = async () => {
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isDiscarding.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在加载最新版本...' };
    showStaleVersionModal.value = false; // Close modal

    try {
        // 1. (Optional but good practice) Tell backend to delete user's draft for this page
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft for page ${props.page.id} deleted.`);
            localLastSaved.value = null; // Clear local draft indicator
        } catch (draftError) {
            // Log warning but continue, deleting draft is not critical path
            console.warn("Failed to delete draft, proceeding with loading new version.", draftError);
        }

        // 2. Get the latest data from the staleVersionData we stored
        const newContent = staleVersionData.value.current_content || '<p></p>';
        const newVersionId = staleVersionData.value.current_version_id;
        const newVersionNumber = staleVersionData.value.current_version_number;

        // 3. Update the form *and* the editor content
        form.content = newContent;
        if (tiptapEditorRef.value?.editor) {
            tiptapEditorRef.value.editor.commands.setContent(newContent, false);
        } else {
            console.warn("Editor instance not found when trying to discard and load new content.");
            // Might need to reload page entirely as a fallback if editor state is broken
            router.reload({ preserveScroll: true }); // Less ideal, full reload
            isDiscarding.value = false;
            return;
        }


        // 4. Update the base version refs
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId; // Now we are based on the latest
        form.version_id = currentBaseVersionId.value;

        // 5. Reset conflict/outdated flags and form state
        isOutdated.value = false;
        form.reset();             // Reset dirty state
        form.defaults();          // Update defaults to new content/base
        form.clearErrors();       // Clear errors

        // 6. Update status bar & show success message
        const successMsg = `已加载最新版本 v${currentBaseVersionNumber.value || '?'}. 更改已放弃，请继续编辑。`;
        autosaveStatus.value = { type: 'success', message: successMsg }; // Use 'success' for user feedback
        flashMessageRef.value?.addMessage('success', successMsg);

        // Hide message after delay
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000);


    } catch (error) {
        console.error("Error discarding changes and loading new version:", error);
        const errorMsg = '加载最新版本时出错，请尝试刷新页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
    } finally {
        isDiscarding.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null; // Clear pending
    }
};

// Option 3: Discard User's Edits and Exit Editor
const handleDiscardAndExit = async () => {
    if (isSaving.value || isForcingConflict.value || isDiscarding.value) return;
    isDiscarding.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃编辑...' };
    showStaleVersionModal.value = false; // Close modal

    try {
        // 1. Attempt to delete draft
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft for page ${props.page.id} deleted before exiting.`);
        } catch (draftError) {
            console.warn("Failed to delete draft before exiting.", draftError);
        }

        // 2. Redirect user back to the 'show' page
        router.visit(route('wiki.show', props.page.slug));

        // No need to reset state locally as we are navigating away

    } catch (error) {
        console.error("Error discarding and exiting:", error);
        const errorMsg = '放弃编辑并退出时出错，请手动返回。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        // Still need to reset processing state if navigation fails for some reason
        isDiscarding.value = false;
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
    // finally not needed here because router.visit() navigates away
};


// --- Utility & Lifecycle ---

// Manual refresh button handler
const refreshPage = () => {
    if (isSaving.value || isDiscarding.value || isForcingConflict.value) {
        alert("当前有操作正在进行，请稍后再刷新。"); return;
    }
    if (form.isDirty) { // Use form.isDirty to check for changes
        if (!confirm("刷新页面将丢失未保存的草稿或编辑更改，确定要刷新吗？")) return;
    }
    console.log("Refreshing page data using router.reload...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面...' };

    router.reload({
        // Specify which props to reload to avoid unnecessary data transfer
        only: ['page', 'content', 'categories', 'tags', 'hasDraft', 'lastSaved', 'canResolveConflict', 'isConflict', 'editorIsEditable', 'errors', 'initialVersionId', 'initialVersionNumber'],
        preserveState: false, // Important: Need to reset component state
        preserveScroll: true,
        onSuccess: (page) => {
            console.log("Page reloaded successfully via router.reload.");
            const newProps = page.props; // Use the fresh props from the response

            // Update local state based on reloaded props
            form.title = newProps.page.title;
            form.content = newProps.content; // Update form content model
            form.category_ids = newProps.page.category_ids || [];
            form.tag_ids = newProps.page.tag_ids || [];
            form.comment = ''; // Reset comment

            currentBaseVersionId.value = newProps.initialVersionId;
            currentBaseVersionNumber.value = newProps.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newProps.initialVersionId; // Reset last save reference
            form.version_id = currentBaseVersionId.value;

            isOutdated.value = false; // Reset outdated flag
            showStaleVersionModal.value = false; // Ensure modal is closed

            // Explicitly reset form dirty state AFTER updating its values
            form.defaults(); // Set current values as the new 'clean' state
            form.reset();    // Reset isDirty and errors

            // Update the editor content and editable status
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.commands.setContent(newProps.content, false);
                tiptapEditorRef.value.editor.setEditable(newProps.editorIsEditable && !isOutdated.value);
            } else {
                console.warn("Editor ref not available after reload.");
            }

            localLastSaved.value = newProps.lastSaved ? new Date(newProps.lastSaved) : null;

            // Update status bar
            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            flashMessageRef.value?.addMessage('success', '页面数据已刷新！');
            setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 2000);
        },
        onError: (errors) => {
            console.error("Page reload failed:", errors);
            autosaveStatus.value = { type: 'error', message: '刷新失败' };
            flashMessageRef.value?.addMessage('error', '刷新页面数据失败，请稍后重试或手动刷新浏览器。');
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 3000);
        }
    });
};


// --- Real-time Handling (Echo) ---
let echoChannel = null;

// Handles incoming real-time version updates from Echo
const handleRealtimeVersionUpdate = (newVersionId) => {
    // **Crucial:** Ignore updates triggered by the current user's own successful save
    if (newVersionId != null && newVersionId === lastSuccessfulSaveVersionId.value) {
        console.log(`Ignoring Echo update for version ${newVersionId} because it matches the last successful save by this user.`);
        return;
    }

    // Check if the received version ID is actually newer than our base
    if (newVersionId != null && newVersionId !== currentBaseVersionId.value) {
        console.log(`Page outdated detected via Echo! Current base: ${currentBaseVersionId.value}, Newest DB: ${newVersionId}`);
        // Only set outdated flag and show message IF the conflict modal isn't already open
        if (!showStaleVersionModal.value) {
            isOutdated.value = true;
            // Update status bar to warn the user (non-intrusive until save attempt)
            autosaveStatus.value = { type: 'error', message: '注意：页面已被其他用户更新！' };
            // Maybe briefly show a flash message? Be careful not to be too annoying.
            // flashMessageRef.value?.addMessage('warning', '页面已被其他用户更新，保存时可能需要处理冲突。');
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

        // Optional: Listen for other relevant events if needed

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

// --- Mobile Responsiveness ---
const isMobile = ref(false);
const updateMobileStatus = () => { isMobile.value = window.innerWidth < 768; };

// --- Preview Tab ---
const openPreviewInNewTab = () => {
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    if (!form.title.trim()) { alert('请先输入页面标题再进行预览！'); return; }
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') { alert('请先输入页面内容再进行预览！'); return; }

    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf; // Access CSRF from Inertia props

    const tempForm = document.createElement('form');
    tempForm.method = 'POST'; tempForm.action = url; tempForm.target = '_blank'; tempForm.style.display = 'none';

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);

    // Add form data
    const fields = { title: form.title, content: currentContent, category_ids: form.category_ids, tag_ids: form.tag_ids };
    for (const key in fields) {
        if (Object.prototype.hasOwnProperty.call(fields, key)) {
            const value = fields[key];
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = `${key}[${index}]`; input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = key; input.value = value;
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
    setupEchoListener(); // Start listening for real-time updates

    // Watch for Inertia validation errors
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0) {
            form.errors = newErrors; // Update form errors
            if (newErrors.general) {
                flashMessageRef.value?.addMessage('error', newErrors.general);
            }
        }
    }, { deep: true, immediate: true });

    // Watch for changes in initialVersionId prop (might happen on reload)
    watch(() => props.initialVersionId, (newVal, oldVal) => {
        if (newVal !== oldVal && newVal !== currentBaseVersionId.value) {
            console.log(`Base Version ID prop changed from ${oldVal} to ${newVal}. Updating state.`);
            currentBaseVersionId.value = newVal;
            currentBaseVersionNumber.value = props.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newVal; // Reset last saved reference too
            form.version_id = currentBaseVersionId.value;
            isOutdated.value = false;           // Reset flags
            showStaleVersionModal.value = false;
            form.clearErrors();
            // Assuming content prop also updated, Editor's watch handles content update
        }
    });

    // Mobile detection
    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);
});

onBeforeUnmount(() => {
    console.log("Edit.vue unmounting...");
    cleanupEchoListener(); // Stop listening to Echo
    window.removeEventListener('resize', updateMobileStatus);

    // Attempt to save draft on exit IF editor is editable, there are changes, AND no conflict modal shown
    if (computedEditorIsEditable.value && form.isDirty && !showStaleVersionModal.value && tiptapEditorRef.value?.editor) {
        if (navigator.sendBeacon) {
            console.log("Attempting to save draft on unmount via sendBeacon");
            const url = route('wiki.save-draft', props.page.id);
            const formData = new FormData();
            formData.append('content', tiptapEditorRef.value.editor.getHTML());
            formData.append('_token', pageProps.csrf);
            const success = navigator.sendBeacon(url, formData);
            console.log("sendBeacon call returned:", success); // Note: true only means queued, not necessarily sent successfully
        } else {
            console.warn("sendBeacon not supported, cannot reliably save draft on unmount/unload.");
            // Maybe a synchronous XHR? Very discouraged.
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

        <!-- Main container with adjusted height -->
        <div class="container mx-auto py-6 px-4 flex flex-col h-[calc(100vh-theme(space.24))]">

            <!-- Header Section (Title, Actions, Status) -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-t-lg shadow-lg p-4 md:p-6 flex-shrink-0 z-10">
                <!-- Title and Links -->
                <div class="flex flex-col md:flex-row justify-between md:items-start mb-3 gap-y-2">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑: {{ page.title }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            编辑基于版本: v{{ currentBaseVersionNumber || '?' }}
                            <span class="hidden sm:inline">| 最后成功保存的版本ID: {{ lastSuccessfulSaveVersionId || '无'
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

                <!-- Alerts & Status Messages -->
                <div v-if="isConflict && !canResolveConflict" class="alert alert-error mb-3">
                    此页面当前处于冲突状态，并且您没有解决冲突的权限。无法编辑。
                </div>
                <div v-else-if="isConflict && canResolveConflict && !showStaleVersionModal"
                    class="alert alert-warning mb-3">
                    此页面当前处于冲突状态。您可以通过提交新的编辑来解决冲突。
                </div>
                <!-- Dynamic "Outdated" Alert -->
                <div v-if="isOutdated && !showStaleVersionModal" class="alert alert-error mb-3 cursor-pointer"
                    @click="savePage">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                    <strong>页面已过时！</strong> 其他用户已更新此页面。
                    <span class="font-bold underline hover:text-red-800 dark:hover:text-red-300 ml-1">点击此处</span>
                    处理版本差异。
                </div>
                <!-- Draft Loaded Message -->
                <div v-if="hasDraft && localLastSaved && !isOutdated && !showStaleVersionModal"
                    class="alert alert-info mb-3">
                    <font-awesome-icon :icon="['fas', 'save']" class="mr-2" />
                    加载了于 {{ formatDateTime(localLastSaved) }} 保存的本地草稿。
                </div>

                <!-- Current Editors List -->
                <EditorsList :pageId="page.id" />
            </div>

            <!-- Editor & Preview Panes -->
            <div class="flex-grow flex flex-col md:flex-row gap-6 overflow-hidden">
                <!-- Editor Pane -->
                <div
                    :class="[editorPaneClass, 'flex flex-col bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-b-lg shadow-lg p-4 md:p-6 overflow-y-auto editor-pane-scrollbar']">
                    <form @submit.prevent="savePage" class="space-y-5 flex-grow flex flex-col">
                        <!-- Title Input -->
                        <div>
                            <label for="title"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标题 <span
                                    class="text-red-500">*</span></label>
                            <input id="title" v-model="form.title" type="text" class="input-field"
                                :disabled="!computedEditorIsEditable" required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>
                        <!-- Tiptap Editor -->
                        <div class="flex-grow flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">内容 <span
                                    class="text-red-500">*</span></label>
                            <Editor v-model="form.content" :editable="computedEditorIsEditable"
                                :autosave="computedEditorIsEditable" :pageId="page.id" @saved="onDraftSaved"
                                @statusUpdate="handleEditorStatusUpdate" placeholder="开始编辑页面内容..." ref="tiptapEditorRef"
                                class="flex-grow" />
                            <InputError class="mt-1" :message="form.errors.content || form.errors.version_id" />
                        </div>
                        <!-- Category & Tag Selection -->
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                            <div class="checkbox-group" :class="{ 'disabled-group': !computedEditorIsEditable }">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        :disabled="!computedEditorIsEditable" class="checkbox" />
                                    <label :for="`tag-${tag.id}`"
                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>
                        <!-- Save Comment -->
                        <div>
                            <label for="comment"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">提交说明
                                (本次保存的简要说明)</label>
                            <textarea id="comment" v-model="form.comment" rows="2" :disabled="!computedEditorIsEditable"
                                class="textarea-field" placeholder="例如：修正了XX数据..."></textarea>
                            <InputError class="mt-1" :message="form.errors.comment" />
                        </div>
                        <!-- Editor Status Bar -->
                        <div
                            class="editor-status-bar flex justify-end items-center mt-auto text-right pr-1 flex-shrink-0">
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
                <div
                    :class="[previewPaneClass, 'flex flex-col bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-b-lg shadow-lg overflow-hidden']">
                    <WikiPreviewPane class="h-full" :form="form" :categories="categories" :tags="tags" :page="page"
                        :currentVersion="page.current_version" />
                </div>
            </div>
        </div>

        <!-- Conflict Resolution Modal -->
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
                    <div class="border-r border-gray-700 pr-4">
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
/* Add scoped styles here if needed, potentially reusing styles from Create.vue and Show.vue */
/* Height Calculation */
.h-\[calc\(100vh-theme\(space\.24\)\)\] {
    height: calc(100vh - 6rem);
    /* Adjust 6rem based on your navbar height */
}

/* Editor Pane Scrollbar */
.editor-pane-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #4b5563 #2d3748;
    /* thumb track for dark mode */
}

.editor-pane-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.editor-pane-scrollbar::-webkit-scrollbar-track {
    background: #2d3748;
    border-radius: 3px;
}

.editor-pane-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
    border-radius: 3px;
}

/* Flex grow fix for editor/preview layout */
.flex-grow.flex.flex-col.md\:flex-row {
    min-height: 0;
}

/* Prevent flex parent from expanding indefinitely */
.editor-pane-scrollbar,
.flex-col.bg-white\/80 {
    /* Assuming preview pane also has similar class */
    flex-basis: 50%;
    /* Each takes half width on md+ */
    min-height: 0;
    /* Allow panes to shrink if needed */
    display: flex;
    flex-direction: column;
    /* Ensure children stack vertically */
}

/* Make Tiptap editor expand correctly */
:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-height: 300px;
    /* Or adjust as needed */
}

:deep(.editor-content) {
    flex-grow: 1;
    /* Remove fixed max-height if it exists from other styles */
    max-height: none;
    height: auto;
}

/* Input & Form Styles (re-use from Create) */
.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

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

/* Button Styles (ensure consistency) */
.btn-primary {
    @apply px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-1.5 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

.btn-warning {
    @apply px-4 py-1.5 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
    @apply px-4 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Alert Styles (ensure consistency) */
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

/* Editor Status Bar Style */
.editor-status-bar {
    @apply text-xs min-h-[16px];
}

/* Minimum height to prevent layout shifts */

/* Styles for the Diff inside the Modal */
.diff-modal {
    max-height: 30vh;
    /* Limit height inside modal */
    overflow-y: auto;
    background-color: #1f2937;
    /* Darker background for modal diff */
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

/* Specific diff colors adjusted for contrast in dark modal */
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
}

/* Mobile Responsive Adjustments */
@media (max-width: 767px) {

    /* Force editor/preview panes to stack vertically */
    .md\:flex-row {
        flex-direction: column;
    }

    .md\:w-1\/2 {
        width: 100%;
    }

    /* Adjust pane scrollbars if needed for mobile */
    .editor-pane-scrollbar {
        padding-right: 0;
    }

    .flex-grow.flex.flex-col.md\:flex-row {
        gap: 1rem;
    }
}
</style>