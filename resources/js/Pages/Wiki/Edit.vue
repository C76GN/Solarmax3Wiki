## File: resources/js/Pages/Wiki/Edit.vue
<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick, onBeforeUnmount } from 'vue';
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'; // Ensure this is imported if not globally

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const props = defineProps({
    page: { type: Object, required: true },
    content: { type: String, required: true },
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    hasDraft: { type: Boolean, default: false },
    lastSaved: { type: String, default: null },
    canResolveConflict: { type: Boolean, default: false }, // User can resolve page-level conflict
    isConflict: { type: Boolean, default: false },     // Is the page itself in conflict state
    editorIsEditable: { type: Boolean, default: true }, // Can this user edit *right now*
    errors: Object,
    initialVersionId: { type: Number, default: null },
    initialVersionNumber: { type: [Number, String], default: 0 },
});

const tiptapEditorRef = ref(null);
const flashMessageRef = ref(null);
const showPreviewPane = ref(true);

// State related to version tracking and conflicts
const currentBaseVersionId = ref(props.initialVersionId);
const currentBaseVersionNumber = ref(props.initialVersionNumber);
const lastSuccessfulSaveVersionId = ref(props.initialVersionId); // Track user's last successful save base
const isSaving = ref(false); // General saving state
const isProcessingChoice = ref(false); // For modal button loading states

// --- Stale Version / Conflict Handling ---
const showStaleVersionModal = ref(false);
const userAcknowledgedConflict = ref(false); // NEW: Track if user chose "Continue Editing"
const staleVersionData = ref({ // Holds data from 409 response
    current_version_id: null,
    current_version_number: null,
    diff_base_vs_current: '',
    diff_user_vs_current: '',
    current_content: '',
    current_version_creator: '',
    current_version_updated_at: '',
    base_version_number: null, // Added: number of the version the user started from
});

// --- Autosave Status ---
const autosaveStatus = ref(null);
const localLastSaved = ref(props.lastSaved ? new Date(props.lastSaved) : null);

// --- Mobile View ---
const isMobile = ref(false);
const updateMobileStatus = () => {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value && showPreviewPane.value) {
        showPreviewPane.value = false; // Hide preview by default on mobile
    }
};

// --- Form ---
const form = useForm({
    title: props.page.title,
    content: props.content, // Initialize with draft/initial content
    category_ids: props.page.category_ids || [],
    tag_ids: props.page.tag_ids || [],
    comment: '',
    version_id: currentBaseVersionId.value, // Critical: Track the base version for saves
    force_conflict: false, // Moved this flag to form for easier management during force save
});

// --- Computed Properties ---
// Determine if the editor component itself should be editable
const computedEditorIsEditable = computed(() => {
    // Cannot edit if not allowed by permissions, or if saving, or if *currently choosing* in the modal
    if (!props.editorIsEditable || isSaving.value || isProcessingChoice.value) {
        return false;
    }
    // If the modal is showing, disable the main editor
    if (showStaleVersionModal.value) {
        return false;
    }
    // If the user acknowledged the conflict and is continuing, they *can* edit
    if (userAcknowledgedConflict.value) {
        return true;
    }
    // Otherwise, rely on the prop (which factors in locks etc.)
    return props.editorIsEditable;
});

// Logic for displaying status messages below the editor
const editorStatusBar = computed(() => {
    if (isSaving.value) {
        return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: '正在保存...' };
    }
    if (isProcessingChoice.value) {
        return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: '正在处理您的选择...' };
    }
    if (showStaleVersionModal.value) {
        return { class: 'text-yellow-500 dark:text-yellow-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '检测到版本冲突，请在弹窗中选择操作。' };
    }
    if (userAcknowledgedConflict.value) {
        return { class: 'text-red-500 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-triangle'], spin: false, message: '注意：正在编辑旧版本，保存将触发冲突！' };
    }
    // Prioritize general errors not related to stale versions
    if (form.errors.general) {
        return { class: 'text-red-600 dark:text-red-400 font-semibold', icon: ['fas', 'exclamation-circle'], spin: false, message: form.errors.general };
    }
    if (autosaveStatus.value) {
        switch (autosaveStatus.value.type) {
            case 'success': return { class: 'text-green-600 dark:text-green-400', icon: ['fas', 'check-circle'], spin: false, message: autosaveStatus.value.message };
            case 'error': return { class: 'text-red-600 dark:text-red-400', icon: ['fas', 'exclamation-circle'], spin: false, message: autosaveStatus.value.message };
            case 'pending': return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'spinner'], spin: true, message: autosaveStatus.value.message };
            // case 'info': return { class: 'text-blue-600 dark:text-blue-400', icon: ['fas', 'info-circle'], spin: false, message: autosaveStatus.value.message };
            // case 'warning': return { class: 'text-yellow-600 dark:text-yellow-400', icon: ['fas', 'exclamation-triangle'], spin: false, message: autosaveStatus.value.message };
            default: return { class: 'text-gray-500 dark:text-gray-400', icon: ['fas', 'info-circle'], spin: false, message: autosaveStatus.value.message };
        }
    }
    if (hasUnsavedChanges.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '有未保存的更改' };
    }
    if (localLastSaved.value) {
        return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'save'], spin: false, message: `草稿已于 ${formatDateTime(localLastSaved.value)} 自动保存` };
    }
    return { class: 'text-gray-400 dark:text-gray-500 italic', icon: ['fas', 'circle-info'], spin: false, message: '编辑 v' + (currentBaseVersionNumber.value || '?') };
});

// Use form.isDirty to track if user has made changes since the last successful save/load
const hasUnsavedChanges = computed(() => form.isDirty);

const editorPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'w-full md:w-1/2' : 'w-full';
});

const previewPaneClass = computed(() => {
    return showPreviewPane.value && !isMobile.value ? 'hidden md:flex md:w-1/2' : 'hidden';
});

// --- Methods ---

const togglePreviewPane = () => {
    showPreviewPane.value = !showPreviewPane.value;
};

// Called by Editor component when autosave finishes
const onDraftSaved = (status) => {
    // Don't update status if conflict modal is showing or conflict is acknowledged
    if (showStaleVersionModal.value || userAcknowledgedConflict.value) return;

    if (status && status.saved_at) {
        localLastSaved.value = new Date(status.saved_at);
    }
    // Clear general errors *only if* the save was successful or pending (not error)
    if (form.errors.general && (status?.type === 'success' || status?.type === 'pending')) {
        form.clearErrors('general');
    }
    autosaveStatus.value = status;

    // Handle display timeout for non-pending, non-error statuses
    if (status?.type !== 'pending') {
        setTimeout(() => {
            // Clear status only if it hasn't changed in the meantime
            if (autosaveStatus.value === status) {
                autosaveStatus.value = null;
            }
        }, status?.type === 'error' ? 5000 : 3000);
    }
};

// Also allow editor to report status updates directly
const handleEditorStatusUpdate = (status) => {
    // Don't update status if conflict modal is showing or conflict is acknowledged
    if (showStaleVersionModal.value || userAcknowledgedConflict.value) return;
    autosaveStatus.value = status;
};

const savePage = async () => {
    if (isSaving.value) return;
    isSaving.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在提交保存...' };
    form.clearErrors(); // Clear previous errors

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

    // Always update form content and base version ID before sending
    form.content = currentContent;
    form.version_id = currentBaseVersionId.value;
    // 'force_conflict' is handled within the modal actions

    console.log(`Submitting save request for page ${props.page.id} based on version ID: ${form.version_id}. Force conflict: ${form.force_conflict}. User Acknowledged Conflict: ${userAcknowledgedConflict.value}`);

    // --- Submit Request ---
    try {
        // Pass the entire form data, which now includes `force_conflict` when set
        const response = await axios.put(route('wiki.update', props.page.slug), form.data());
        console.log("Save/Update attempt response:", response.data);
        // Server will now handle the conflict if base version ID doesn't match current DB version
        handleSaveResponse(response.data);

    } catch (error) {
        // Handle 409 Conflict and other errors
        handleSaveError(error);
    } finally {
        isSaving.value = false;
        form.force_conflict = false; // Reset force flag after submission attempt
        // Don't clear pending status immediately if a modal popped up
        if (autosaveStatus.value?.type === 'pending' && !showStaleVersionModal.value) {
            autosaveStatus.value = null;
        }
        // Reset acknowledge flag only if save wasn't a conflict scenario and didn't open modal
        if (!showStaleVersionModal.value) {
            userAcknowledgedConflict.value = false;
        }
    }
};

// Consolidated response handler
const handleSaveResponse = (responseData) => {
    if (responseData.status === 'success' || responseData.status === 'conflict_resolved') {
        handleSaveSuccess(responseData);
    } else if (responseData.status === 'no_changes') {
        handleNoChanges(responseData);
    } else if (responseData.status === 'conflict_forced') {
        handleConflictForced(responseData);
    } else {
        console.warn("Received unexpected success status:", responseData.status);
        autosaveStatus.value = { type: 'info', message: responseData.message || '操作完成，但状态未知。' };
        flashMessageRef.value?.addMessage('info', responseData.message || '操作完成，但状态未知。');
        userAcknowledgedConflict.value = false; // Reset flag tentatively
        if (responseData.redirect_url) {
            router.visit(responseData.redirect_url);
        }
    }
}

// Handle success or resolved conflict
const handleSaveSuccess = (responseData) => {
    const newVersionId = responseData.new_version_id;
    const newVersionNumber = responseData.new_version_number;
    console.log('Save successful / Conflict resolved:', responseData.message);

    if (newVersionId != null && newVersionNumber != null) {
        currentBaseVersionId.value = newVersionId;
        currentBaseVersionNumber.value = newVersionNumber;
        lastSuccessfulSaveVersionId.value = newVersionId; // Update last saved ref
        form.version_id = currentBaseVersionId.value; // Sync form
        userAcknowledgedConflict.value = false; // Reset flag on successful save
        console.log(`Save success! New base version set to ID: ${currentBaseVersionId.value}, Number: ${currentBaseVersionNumber.value}`);
    } else {
        console.warn("Success response missing new version details. Base version not updated.");
    }

    form.comment = ''; // Clear comment field
    form.defaults(); // Set current state as default (mark as clean)
    form.reset();    // Actually reset dirty state based on defaults
    form.clearErrors();
    localLastSaved.value = null; // Draft is saved, clear indicator
    autosaveStatus.value = { type: 'success', message: responseData.message || '页面保存成功！' };
    flashMessageRef.value?.addMessage('success', responseData.message || '页面保存成功！');

    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        // Optional: Stay on page, clear status after timeout
        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 3000);
    }
};

// Handle no changes detected
const handleNoChanges = (responseData) => {
    console.log("No changes detected by server.");
    autosaveStatus.value = { type: 'info', message: responseData.message || '未检测到更改。' };
    flashMessageRef.value?.addMessage('info', responseData.message || '未检测到更改，页面未更新。');
    form.defaults(); // Mark current state as clean
    form.reset();
    localLastSaved.value = null; // Draft likely irrelevant now
    userAcknowledgedConflict.value = false; // Reset flag
    setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 3000);
};

// Handle forced conflict response
const handleConflictForced = (responseData) => {
    console.log("Conflict forced response received:", responseData);
    autosaveStatus.value = { type: 'warning', message: responseData.message || '更改已保存但标记为冲突。' };
    flashMessageRef.value?.addMessage('warning', responseData.message || '您的更改已保存，但由于版本冲突，页面已被标记并锁定。');
    form.defaults(); // Treat the forced state as 'clean' locally for now
    form.reset();
    // Redirect user away as page is now locked for them
    if (responseData.redirect_url) {
        router.visit(responseData.redirect_url);
    } else {
        router.visit(route('wiki.show', props.page.slug));
    }
};

const handleSaveError = (error) => {
    console.error("Save error handler triggered:", error);
    // Reset the 'continue editing' state on *any* error during save
    userAcknowledgedConflict.value = false;

    if (error.response) {
        if (error.response.status === 409 && error.response.data?.status === 'stale_version') {
            console.log("Stale version detected via 409 response:", error.response.data);
            staleVersionData.value = {
                current_version_id: error.response.data.current_version_id ?? null,
                current_version_number: error.response.data.current_version_number ?? null,
                // Use component state as fallback for base number if not in response
                base_version_number: error.response.data.base_version_number ?? currentBaseVersionNumber.value,
                diff_base_vs_current: error.response.data.diff_base_vs_current || '<p class="p-2 text-red-400">无法加载基础版本与最新版本的差异。</p>',
                diff_user_vs_current: error.response.data.diff_user_vs_current || '<p class="p-2 text-red-400">无法加载您的编辑与最新版本的差异。</p>',
                current_content: error.response.data.current_content ?? '无法加载最新版本的内容。',
                current_version_creator: error.response.data.current_version_creator ?? '未知用户',
                current_version_updated_at: error.response.data.current_version_updated_at ?? '',
            };
            showStaleVersionModal.value = true; // Show the modal
            autosaveStatus.value = { type: 'error', message: '版本冲突，请选择操作' };
            // Make editor non-editable while modal is open (handled by computedEditorIsEditable)
        } else if (error.response.status === 422) {
            form.errors = error.response.data.errors;
            const firstError = Object.values(error.response.data.errors).flat()[0];
            autosaveStatus.value = { type: 'error', message: firstError || '提交的内容有误' };
            flashMessageRef.value?.addMessage('error', firstError || '提交的内容有误，请检查表单。');
        } else {
            const errorMsg = error.response.data?.message || error.message || '保存时发生未知服务器错误。';
            form.setError('general', errorMsg);
            autosaveStatus.value = { type: 'error', message: errorMsg };
            flashMessageRef.value?.addMessage('error', errorMsg);
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
        }
    } else {
        // Network error or request setup error
        const netErrorMsg = '网络连接错误或请求无法发送。';
        form.setError('general', netErrorMsg);
        autosaveStatus.value = { type: 'error', message: '网络错误' };
        flashMessageRef.value?.addMessage('error', netErrorMsg);
        setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
    }
};


// --- Conflict Modal Button Handlers ---

// NEW: Handler for "Continue Editing" button
const handleContinueEditing = () => {
    if (isProcessingChoice.value) return;
    console.log("User chose to continue editing the outdated version.");
    showStaleVersionModal.value = false;
    userAcknowledgedConflict.value = true; // Set the flag
    autosaveStatus.value = { type: 'warning', message: '注意：正在编辑旧版本，保存将触发冲突！' };
    // Re-enable editor via computed property change, then focus
    nextTick(() => {
        tiptapEditorRef.value?.editor?.commands?.focus();
    });
    // Status message timeout
    setTimeout(() => { if (autosaveStatus.value?.type === 'warning') autosaveStatus.value = null; }, 4000);
};

// *MODIFIED* Handler for "Discard & Edit Latest"
const handleDiscardAndLoadLatest = async () => {
    if (isProcessingChoice.value) return;
    isProcessingChoice.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃更改并加载最新...' };
    showStaleVersionModal.value = false; // Close modal immediately

    try {
        // Attempt to delete draft first
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft deleted for page ${props.page.id}`);
            localLastSaved.value = null; // Clear local draft time indicator
        } catch (draftError) {
            console.warn("Failed to delete draft while discarding, proceeding anyway.", draftError);
            // Optionally inform the user
        }

        // Get the latest content and version details from staleVersionData
        const latestContent = staleVersionData.value.current_content || '<p></p>';
        const latestVersionId = staleVersionData.value.current_version_id;
        const latestVersionNumber = staleVersionData.value.current_version_number;

        // 1. Update the form content directly
        form.content = latestContent;
        // Reset title and metadata? Keep user's edits for now unless requested otherwise
        // form.title = props.page.title;
        // form.category_ids = props.page.category_ids || [];
        // form.tag_ids = props.page.tag_ids || [];
        form.comment = ''; // Clear comment

        // 2. Update the Tiptap editor content WITHOUT marking it as dirty immediately
        if (tiptapEditorRef.value?.editor) {
            tiptapEditorRef.value.editor.commands.setContent(latestContent, false); // false prevents firing 'update' event
        } else {
            console.error("Editor instance not found when discarding.");
            flashMessageRef.value?.addMessage('error', '编辑器实例出错，请尝试刷新。');
            isProcessingChoice.value = false;
            autosaveStatus.value = { type: 'error', message: '编辑器错误' };
            return; // Stop processing
        }

        // 3. Update the base version tracking state
        currentBaseVersionId.value = latestVersionId;
        currentBaseVersionNumber.value = latestVersionNumber;
        lastSuccessfulSaveVersionId.value = latestVersionId; // Important: base is now this version
        form.version_id = currentBaseVersionId.value; // Sync form

        userAcknowledgedConflict.value = false; // Reset conflict flag

        // 4. Reset form state AFTER setting new content and base version
        form.defaults({ // Explicitly set new defaults based on latest loaded content
            title: form.title, // Keep potential title edits
            content: latestContent,
            category_ids: form.category_ids, // Keep potential category edits
            tag_ids: form.tag_ids,           // Keep potential tag edits
            comment: '',
            version_id: latestVersionId
        });
        form.reset(); // Resets fields to defaults and clears dirty state/errors

        // Update status and notify user
        const successMsg = `已加载最新版本 v${latestVersionNumber || '?'}. 之前的更改已放弃。`;
        autosaveStatus.value = { type: 'success', message: successMsg };
        flashMessageRef.value?.addMessage('success', successMsg);

        // Re-enable editor via computed property change & focus
        nextTick(() => {
            tiptapEditorRef.value?.editor?.setEditable(true); // Should be handled by computed prop change, but belt-and-suspenders
            tiptapEditorRef.value?.editor?.commands.focus();
        });

        setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 4000);

    } catch (error) {
        console.error("Error during 'Discard & Edit Latest':", error);
        const errorMsg = '加载最新版本时出错，请尝试手动刷新页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        // Attempt to ensure editor is editable according to props if error occurs
        nextTick(() => {
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.setEditable(props.editorIsEditable);
            }
        });
    } finally {
        isProcessingChoice.value = false;
        // Ensure status clears if stuck on pending
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

// Handler for "Discard & Exit"
const handleDiscardAndExit = async () => {
    if (isProcessingChoice.value) return;
    isProcessingChoice.value = true;
    autosaveStatus.value = { type: 'pending', message: '正在放弃编辑并退出...' };
    showStaleVersionModal.value = false;

    try {
        // Attempt to delete draft first
        try {
            await axios.delete(route('wiki.draft.delete', { page: props.page.slug }));
            console.log(`User draft deleted for page ${props.page.id} before exiting.`);
        } catch (draftError) {
            console.warn("Failed to delete draft before exiting.", draftError);
        }

        // Redirect back to the show page
        router.visit(route('wiki.show', props.page.slug), {
            onFinish: () => {
                isProcessingChoice.value = false;
                autosaveStatus.value = null;
            }
        });

    } catch (error) {
        console.error("Error during 'Discard & Exit':", error);
        const errorMsg = '退出时出错，请手动返回页面。';
        autosaveStatus.value = { type: 'error', message: errorMsg };
        flashMessageRef.value?.addMessage('error', errorMsg);
        isProcessingChoice.value = false;
        // Ensure status clears if stuck on pending
        if (autosaveStatus.value?.type === 'pending') autosaveStatus.value = null;
    }
};

// *NEW* Handler for "Force Save" button (moved logic here)
const handleForceSave = () => {
    if (isProcessingChoice.value || isSaving.value) return;
    isProcessingChoice.value = true; // Use processing state to disable modal buttons
    showStaleVersionModal.value = false; // Close the modal

    // Set the flag and trigger the regular save process
    form.force_conflict = true;
    savePage() // This will now submit with force_conflict=true
        .finally(() => {
            form.force_conflict = false; // Reset the flag regardless of outcome
            isProcessingChoice.value = false; // Release processing state
        });
};


const refreshPage = () => {
    if (isSaving.value || isProcessingChoice.value) {
        flashMessageRef.value?.addMessage('warning', "请等待当前操作完成后再刷新。");
        return;
    }
    if (form.isDirty || userAcknowledgedConflict.value) {
        if (!confirm("刷新页面将丢失未保存的更改或编辑状态，确定要刷新吗？")) {
            return;
        }
    }

    console.log("Refreshing page data via router.reload...");
    autosaveStatus.value = { type: 'pending', message: '正在刷新页面数据...' };

    router.reload({
        // Specify only the props that might change relevantly for the edit page
        only: [
            'page', 'content', 'categories', 'tags', 'hasDraft',
            'lastSaved', 'isConflict', 'editorIsEditable',
            'initialVersionId', 'initialVersionNumber', 'errors' // Ensure errors are refetched
        ],
        preserveState: false, // Re-fetch fresh data
        preserveScroll: true, // Keep scroll position if possible
        onSuccess: (pageResponse) => {
            console.log("Page reload via router.reload successful.");
            const newProps = pageResponse.props;

            // Reset core form data
            form.defaults({ // Update defaults before resetting
                title: newProps.page.title,
                content: newProps.content,
                category_ids: newProps.page.category_ids || [],
                tag_ids: newProps.page.tag_ids || [],
                comment: '',
                version_id: newProps.initialVersionId, // Reset base version!
                force_conflict: false,
            });
            form.reset(); // Resets fields to defaults and clears dirty state/errors

            // Reset version tracking state
            currentBaseVersionId.value = newProps.initialVersionId;
            currentBaseVersionNumber.value = newProps.initialVersionNumber;
            lastSuccessfulSaveVersionId.value = newProps.initialVersionId;
            userAcknowledgedConflict.value = false; // Reset flag
            showStaleVersionModal.value = false; // Ensure modal is closed

            // Update editor content and state
            if (tiptapEditorRef.value?.editor) {
                tiptapEditorRef.value.editor.commands.setContent(newProps.content, false);
                tiptapEditorRef.value.editor.setEditable(newProps.editorIsEditable); // Reflect current editability
            } else {
                console.warn("Editor ref not available after reload for content update.");
            }

            // Update other local state
            localLastSaved.value = newProps.lastSaved ? new Date(newProps.lastSaved) : null;
            // Check for persistent errors after reload
            if (newProps.errors && Object.keys(newProps.errors).length > 0) {
                form.errors = newProps.errors;
                if (newProps.errors.general) {
                    flashMessageRef.value?.addMessage('error', newProps.errors.general);
                }
            } else {
                form.clearErrors();
            }

            autosaveStatus.value = { type: 'success', message: '页面数据已刷新！' };
            flashMessageRef.value?.addMessage('success', '页面数据已刷新！');
            setTimeout(() => { if (autosaveStatus.value?.type === 'success') autosaveStatus.value = null; }, 2000);
        },
        onError: (errors) => {
            console.error("Page reload failed:", errors);
            form.errors = errors; // Assign errors from reload
            autosaveStatus.value = { type: 'error', message: '刷新失败，请稍后重试或手动刷新' };
            flashMessageRef.value?.addMessage('error', '刷新页面数据失败，请尝试手动刷新浏览器。');
            setTimeout(() => { if (autosaveStatus.value?.type === 'error') autosaveStatus.value = null; }, 5000);
        }
    });
};


// --- Realtime Handling (Placeholder, assuming backend broadcasts correctly) ---
let echoChannel = null;

const handleRealtimeVersionUpdate = (newVersionId) => {
    // Ignore if the update is from the user's own save
    if (newVersionId != null && newVersionId === lastSuccessfulSaveVersionId.value) {
        console.log(`Ignoring Echo update for own saved version ${newVersionId}.`);
        return;
    }
    // Ignore if modal is already open, let the modal handle the state
    if (showStaleVersionModal.value) {
        console.log("Conflict modal already showing, ignoring Echo update for now.");
        return;
    }

    // If received version is newer than current base
    if (newVersionId != null && newVersionId !== currentBaseVersionId.value) {
        console.warn(`Page outdated detected via Echo! Current base: ${currentBaseVersionId.value}, Newest DB: ${newVersionId}. Setting userAcknowledgedConflict=false.`);
        userAcknowledgedConflict.value = false; // Mark as outdated, needs user action via modal trigger
        autosaveStatus.value = { type: 'error', message: '注意：页面已被其他用户更新！点击保存处理。' };
        flashMessageRef.value?.addMessage('error', '页面已被其他用户更新！如果您编辑了内容，点击保存将显示处理选项。', 10000);
        // Do NOT show modal directly from Echo, user triggers it on *next* save attempt
    } else {
        console.log(`Echo update received for version ${newVersionId}, which matches current base or is invalid. No action.`);
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


// --- Preview in New Tab ---
const openPreviewInNewTab = () => {
    const currentContent = tiptapEditorRef.value?.editor?.getHTML() || form.content;
    if (!form.title.trim()) { alert('请先输入页面标题再进行预览！'); return; }
    if (!currentContent || currentContent === '<p></p>' || currentContent.trim() === '') { alert('请先输入页面内容再进行预览！'); return; }
    const url = route('wiki.preview');
    const csrfToken = pageProps.csrf;
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = url;
    tempForm.target = '_blank';
    tempForm.style.display = 'none';
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    tempForm.appendChild(csrfInput);
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
                value.forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${index}]`;
                    input.value = item;
                    tempForm.appendChild(input);
                });
            } else {
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
    console.log("Edit.vue mounted. Initial Base:", currentBaseVersionId.value, `(v${currentBaseVersionNumber.value})`, "Editable:", props.editorIsEditable);
    setupEchoListener();

    // Watch Inertia errors (e.g., from form validation or redirects with errors)
    watch(() => pageProps.errors, (newErrors) => {
        if (newErrors && Object.keys(newErrors).length > 0) {
            form.errors = newErrors; // Assign validation errors to form
            if (newErrors.general && newErrors.general !== autosaveStatus.value?.message) {
                flashMessageRef.value?.addMessage('error', newErrors.general);
                // Set a specific status if needed
                autosaveStatus.value = { type: 'error', message: newErrors.general };
                setTimeout(() => { if (autosaveStatus.value?.message === newErrors.general) autosaveStatus.value = null; }, 5000);
            } else if (!newErrors.general) {
                // If validation errors are specific to fields, maybe show a generic message?
                // flashMessageRef.value?.addMessage('error', '请检查表单中的错误');
            }
        } else if (Object.keys(form.errors).length > 0) {
            // Clear form errors if Inertia errors are cleared
            form.clearErrors();
        }
    }, { deep: true, immediate: true });


    updateMobileStatus();
    window.addEventListener('resize', updateMobileStatus);

    // Initial setup of autosave status based on loaded draft
    if (props.hasDraft && props.lastSaved) {
        autosaveStatus.value = { type: 'info', message: `已加载 ${formatDateTime(localLastSaved.value)} 的草稿` };
        setTimeout(() => { if (autosaveStatus.value?.type === 'info') autosaveStatus.value = null; }, 4000);
    }

    // Beforeunload prompt if dirty (and not in conflict modal)
    window.onbeforeunload = (event) => {
        if (form.isDirty && !isSaving.value && !isProcessingChoice.value && !showStaleVersionModal.value) {
            event.preventDefault();
            // Standard browser mechanism, cannot customize message reliably
            event.returnValue = '您有未保存的更改，确定要离开吗？草稿可能无法完全保存。';
            return event.returnValue;
        }
    };
});

onBeforeUnmount(() => {
    console.log("Edit.vue unmounting...");
    cleanupEchoListener();
    window.removeEventListener('resize', updateMobileStatus);
    window.onbeforeunload = null; // Clean up listener

    // Attempt draft save on unmount *only* if safe and logical
    const canAutosaveOnUnload = computedEditorIsEditable.value // Check current editable state
        && form.isDirty                     // Have changes?
        && !isSaving.value                  // Not currently saving?
        && !isProcessingChoice.value       // Not currently processing modal choice?
        && !showStaleVersionModal.value    // Modal not open?
        && !userAcknowledgedConflict.value; // User isn't intentionally editing outdated?

    if (canAutosaveOnUnload && tiptapEditorRef.value?.editor) {
        if (navigator.sendBeacon) {
            console.log("Attempting to save draft on unmount via sendBeacon (safe state check passed).");
            // Prepare data
            const url = route('wiki.save-draft', props.page.id);
            // Use Blob for sendBeacon payload as FormData might not be reliable in all browsers on unload
            const payload = JSON.stringify({
                content: tiptapEditorRef.value.editor.getHTML(),
                _token: pageProps.csrf // Still need CSRF for stateful requests potentially
            });
            const blob = new Blob([payload], { type: 'application/json' });

            try {
                const success = navigator.sendBeacon(url, blob);
                console.log("sendBeacon call returned:", success ? "Queued" : "Failed to queue");
            } catch (e) {
                console.error("sendBeacon call threw an error:", e);
            }
        } else {
            console.warn("sendBeacon not supported, cannot reliably save draft on unmount/unload.");
        }
    } else {
        console.log("Skipping draft save on unmount. State:", {
            computedEditable: computedEditorIsEditable.value,
            isDirty: form.isDirty,
            isSaving: isSaving.value,
            isProcessingChoice: isProcessingChoice.value,
            isModalOpen: showStaleVersionModal.value,
            acknowledgedConflict: userAcknowledgedConflict.value,
            editorExists: !!tiptapEditorRef.value?.editor
        });
    }
});


</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑: ${page.title}`" />
        <!-- Main Container -->
        <div class="container mx-auto py-6 px-4 flex flex-col h-[calc(100vh-theme(space.24))]">
            <!-- Page Wrapper -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-4 md:p-6 flex flex-col flex-grow overflow-hidden">
                <!-- Header Row -->
                <div class="flex flex-col md:flex-row justify-between md:items-start mb-3 gap-y-2 flex-shrink-0 z-10">
                    <!-- Left: Title & Version Info -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑: {{ form.title || page.title
                        }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                            :title="`服务器最新版本ID: ${props.initialVersionId ?? 'N/A'}\n最后成功保存时基于的版本ID: ${lastSuccessfulSaveVersionId ?? 'N/A'}`">
                            当前编辑基于版本: v{{ currentBaseVersionNumber || '?' }}
                        </p>
                    </div>
                    <!-- Right: Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                        <button @click="togglePreviewPane" class="btn-secondary text-xs px-2 py-1 md:hidden">
                            <font-awesome-icon :icon="['fas', showPreviewPane ? 'eye-slash' : 'eye']"
                                class="mr-1 h-3 w-3" /> {{ showPreviewPane ? '隐藏' : '显示' }}预览
                        </button>
                        <button @click="refreshPage" class="btn-secondary text-sm" title="刷新页面数据（将丢失未保存的更改！）">
                            <font-awesome-icon :icon="['fas', 'sync-alt']" :spin="false" class="h-3 w-3" /> 刷新
                        </button>
                        <button @click="openPreviewInNewTab" type="button" class="btn-secondary text-sm"
                            title="在新标签页中预览">
                            <font-awesome-icon :icon="['fas', 'external-link-alt']" class="mr-1 h-3 w-3" /> 外部预览
                        </button>
                        <Link :href="route('wiki.show', page.slug)" class="btn-secondary text-sm"> 取消 </Link>
                        <button @click="savePage" class="btn-primary text-sm"
                            :disabled="!computedEditorIsEditable || form.processing || isSaving || showStaleVersionModal || isProcessingChoice"
                            :title="computedEditorIsEditable ? '保存更改' : '当前状态不可保存'">
                            <font-awesome-icon v-if="form.processing || isSaving" :icon="['fas', 'spinner']" spin
                                class="mr-1 h-3 w-3" /> {{ isSaving ? '正在保存...' : '保存页面' }}
                        </button>
                    </div>
                </div>

                <!-- Status Bar & Editors List -->
                <div class="mb-3 flex-shrink-0 space-y-2">
                    <!-- Alert Messages (Simplified examples, refine as needed) -->
                    <div v-if="isConflict && !canResolveConflict && !showStaleVersionModal && !userAcknowledgedConflict"
                        class="alert alert-error">
                        页面冲突，您无权解决，无法编辑。
                    </div>
                    <div v-else-if="isConflict && canResolveConflict && !showStaleVersionModal && !userAcknowledgedConflict"
                        class="alert alert-warning">
                        页面冲突，请提交编辑以解决。
                    </div>
                    <!-- New button to re-open conflict modal -->
                    <div v-if="userAcknowledgedConflict && !showStaleVersionModal"
                        class="alert alert-warning text-center">
                        你选择了继续编辑旧版本。
                        <button @click="refreshPage" class="btn-secondary text-xs px-2 py-0.5 ml-2">刷新以获取最新</button>
                        <button @click="handleForceSave" class="btn-danger text-xs px-2 py-0.5 ml-2">强制保存(当前版本)</button>
                        <button @click="handleDiscardAndExit"
                            class="btn-secondary text-xs px-2 py-0.5 ml-2">放弃编辑并退出</button>
                    </div>

                    <!-- Editors List -->
                    <EditorsList :pageId="page.id" />
                </div>

                <!-- Editor & Preview Pane Layout -->
                <div class="flex-grow flex flex-col md:flex-row gap-6 min-h-0">
                    <!-- Editor Pane -->
                    <div :class="[editorPaneClass, 'flex flex-col']">
                        <form @submit.prevent="savePage"
                            class="space-y-5 flex-grow flex flex-col overflow-y-auto pr-2 editor-pane-scrollbar">
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
                            <!-- Category & Tag Selections -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">分类
                                        <span class="text-red-500">*</span></label>
                                    <div class="checkbox-group"
                                        :class="{ 'disabled-group': !computedEditorIsEditable }">
                                        <div v-for="category in categories" :key="category.id"
                                            class="flex items-center">
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
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">标签</label>
                                    <div class="checkbox-group"
                                        :class="{ 'disabled-group': !computedEditorIsEditable }">
                                        <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                            <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id"
                                                v-model="form.tag_ids" :disabled="!computedEditorIsEditable"
                                                class="checkbox" />
                                            <label :for="`tag-${tag.id}`"
                                                class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ tag.name
                                                }}</label>
                                        </div>
                                    </div>
                                    <InputError class="mt-1" :message="form.errors.tag_ids" />
                                </div>
                            </div>
                            <!-- Comment Input -->
                            <div>
                                <label for="comment"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">提交说明 <span
                                        class="text-xs text-gray-500 dark:text-gray-400">(可选)</span></label>
                                <textarea id="comment" v-model="form.comment" rows="2"
                                    :disabled="!computedEditorIsEditable" class="textarea-field"
                                    placeholder="例如：修正了XX数据..."></textarea>
                                <InputError class="mt-1" :message="form.errors.comment" />
                            </div>
                            <!-- Status Bar (inside form's flex container to stick near bottom) -->
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
                    <div :class="[previewPaneClass, 'hidden md:flex md:flex-col min-h-0']">
                        <WikiPreviewPane
                            class="h-full border-gray-300 dark:border-gray-600 border rounded-lg overflow-hidden"
                            :form="form" :categories="categories" :tags="tags" :page="page"
                            :currentVersion="page.current_version" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Stale Version / Conflict Modal -->
        <Modal :show="showStaleVersionModal" @close="handleContinueEditing" maxWidth="4xl" :closeable="true">
            <!-- Allow closing via overlay click or Esc, handled by handleContinueEditing -->
            <div class="p-6 bg-gray-800 text-gray-200 rounded-lg shadow-xl">
                <h2 class="text-xl font-bold mb-4 text-yellow-400 flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" /> 页面版本冲突
                </h2>
                <p class="mb-4 text-sm">
                    在你编辑期间 (从 v{{ staleVersionData.base_version_number ?? currentBaseVersionNumber }} 开始)，页面已被 <strong
                        class="text-white">{{ staleVersionData.current_version_creator || '其他用户' }}</strong> 更新至
                    <strong>v{{ staleVersionData.current_version_number || '新' }}</strong> 版本。
                    <span v-if="staleVersionData.current_version_updated_at"> (更新于 {{ formatDateTime(new
                        Date(staleVersionData.current_version_updated_at)) }})</span>
                </p>
                <p class="mb-6 text-sm">请检查以下内容差异，并选择如何处理您的编辑：</p>

                <!-- Diff Views -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 max-h-[50vh] overflow-y-auto p-3 bg-gray-900/70 rounded-md border border-gray-700">
                    <div
                        class="md:border-r border-gray-700 pr-0 md:pr-4 pb-4 md:pb-0 mb-4 md:mb-0 border-b md:border-b-0">
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-blue-400">您的编辑</span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本 v{{ staleVersionData.current_version_number }}</span>
                        </h3>
                        <!-- Using v-html here requires trusting the source or sanitizing the output -->
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_user_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载您的编辑与最新版本的差异。</p>'">
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold mb-2 text-gray-400 uppercase tracking-wider">
                            <span class="text-red-400">您基于的版本 v{{ staleVersionData.base_version_number }}</span>
                            <span class="text-white mx-1"> vs </span>
                            <span class="text-green-400">最新版本 v{{ staleVersionData.current_version_number }}</span>
                        </h3>
                        <!-- Using v-html here requires trusting the source or sanitizing the output -->
                        <div class="diff-modal"
                            v-html="staleVersionData.diff_base_vs_current || '<p class=\'p-2 text-gray-500 italic\'>无法加载基础版本与最新版本的差异。</p>'">
                        </div>
                    </div>
                </div>


                <!-- Modal Actions -->
                <div
                    class="flex flex-col sm:flex-row justify-end items-center gap-3 mt-6 pt-4 border-t border-gray-700">
                    <button @click="handleContinueEditing" class="btn-secondary w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="关闭此弹窗，继续在当前（旧版本）内容上编辑。下次保存时将自动触发页面冲突状态。">
                        继续编辑 (忽略)
                    </button>
                    <button @click="handleForceSave" class="btn-warning w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="强行保存当前编辑的内容，这将会覆盖其他用户的更改并将页面标记为冲突，需手动解决。">
                        <font-awesome-icon v-if="isProcessingChoice && form.force_conflict" :icon="['fas', 'spinner']"
                            spin class="mr-1" />
                        强制保存 (我的版本)
                    </button>
                    <button @click="handleDiscardAndLoadLatest" class="btn-secondary w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="丢弃当前编辑和草稿，加载页面最新版本的内容，然后您可以继续编辑。">
                        <font-awesome-icon
                            v-if="isProcessingChoice && !form.force_conflict && !userAcknowledgedConflict"
                            :icon="['fas', 'spinner']" spin class="mr-1" />
                        放弃编辑，加载最新
                    </button>
                    <button @click="handleDiscardAndExit" class="btn-danger w-full sm:w-auto text-sm"
                        :disabled="isProcessingChoice" title="丢弃当前编辑和草稿，并退出编辑页面。">
                        <font-awesome-icon
                            v-if="isProcessingChoice && !form.force_conflict && !userAcknowledgedConflict"
                            :icon="['fas', 'spinner']" spin class="mr-1" />
                        放弃编辑并退出
                    </button>
                </div>
            </div>
        </Modal>
        <FlashMessage ref="flashMessageRef" />
    </MainLayout>
</template>

<!-- Styles section copied directly from 旧的Edit.vue.txt to ensure correct diff display -->
<style scoped>
.h-\[calc\(100vh-theme\(space\.24\)\)\] {
    height: calc(100vh - 6rem);
}

.container {
    height: 100%;
}

.flex-grow.bg-white\/80 {
    display: flex;
    flex-direction: column;
}

.flex-grow.flex.flex-col.md\:flex-row {
    flex-grow: 1;
    min-height: 0;
}

.editor-pane-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #a0aec0 #e2e8f0;
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

:deep(.tiptap-editor) {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

:deep(.editor-content) {
    flex-grow: 1;
    overflow-y: auto;
    min-height: 250px;
}

.input-field,
.textarea-field,
select {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800/60 disabled:cursor-not-allowed dark:disabled:text-gray-500;
}

.textarea-field {
    min-height: 5rem;
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

.editor-status-bar {
    @apply text-xs min-h-[16px];
}

/* Crucial part: Ensure styles target the elements inside the modal diff container */
.diff-modal {
    max-height: 30vh;
    overflow-y: auto;
    background-color: #1f2937;
    /* Dark background for modal content area */
    border: 1px solid #4b5563;
    /* Border for the modal diff area */
    border-radius: 0.25rem;
    scrollbar-width: thin;
    scrollbar-color: #4b5563 #1f2937;
}

.diff-modal::-webkit-scrollbar {
    width: 5px;
}

.diff-modal::-webkit-scrollbar-track {
    background: #1f2937;
    /* Scrollbar track matching background */
}

.diff-modal::-webkit-scrollbar-thumb {
    background-color: #4b5563;
    /* Scrollbar thumb */
    border-radius: 2.5px;
}

/* Use :deep() to pierce Shadow DOM or scope boundary for v-html content */
/* Targeting the table generated by the diff library */
.diff-modal :deep(table.diff) {
    font-family: monospace;
    /* Ensure monospace font for alignment */
    font-size: 0.7rem;
    /* Small font size for diffs */
    line-height: 1.3;
    /* Adjust line height */
    width: 100%;
    border-collapse: collapse;
    /* Important for borders */
    table-layout: fixed;
    /* Helps with column widths */
}

/* General cell styling */
.diff-modal :deep(td),
.diff-modal :deep(th) {
    padding: 0.1rem 0.3rem !important;
    /* Use important to override library potentially */
    border: 1px solid #374151 !important;
    /* Darker border */
    vertical-align: top;
    word-break: break-word;
    /* Allow long lines to break */
    white-space: pre-wrap;
    /* Preserve whitespace and wrap */
    color: #d1d5db;
    /* Light text for dark mode */
}

/* Header styling */
.diff-modal :deep(th) {
    background-color: #2d3748 !important;
    /* Darker header */
    border-color: #4b5563 !important;
    text-align: center;
    color: #9ca3af;
    /* Muted header text */
}

/* Line number column */
.diff-modal :deep(td.lines-no) {
    background-color: #2d3748 !important;
    /* Match header */
    border-right: 1px solid #4b5563 !important;
    /* Divider */
    color: #6b7280;
    /* Muted line numbers */
    text-align: right;
    padding-right: 0.5rem;
    width: 35px !important;
    /* Fixed width for line numbers */
    min-width: 35px !important;
    user-select: none;
    /* Prevent selecting line numbers */
}

/* Deletion styling */
.diff-modal :deep(.ChangeDelete .Left) {
    /* Row background for deletion */
    background-color: rgba(153, 27, 27, 0.25) !important;
    /* Transparent red */
}

.diff-modal :deep(.ChangeDelete .Left del),
/* Highlight actual deleted text */
.diff-modal :deep(td.Left .ChangeReplace del) {
    /* Deletion part in replace */
    background-color: rgba(220, 38, 38, 0.35) !important;
    /* Stronger red highlight */
    color: #fecaca !important;
    /* Lighter red text */
    text-decoration: line-through;
    /* Strike through */
    border-radius: 0.125rem;
    /* Slight rounding */
    padding: 0 0.1rem;
}

/* Insertion styling */
.diff-modal :deep(.ChangeInsert .Right) {
    /* Row background for insertion */
    background-color: rgba(4, 100, 65, 0.25) !important;
    /* Transparent green */
}

.diff-modal :deep(.ChangeInsert .Right ins),
/* Highlight actual inserted text */
.diff-modal :deep(td.Right .ChangeReplace ins) {
    /* Insertion part in replace */
    background-color: rgba(5, 150, 105, 0.35) !important;
    /* Stronger green highlight */
    color: #a7f3d0 !important;
    /* Lighter green text */
    text-decoration: none;
    /* Remove default underline from ins */
    border-radius: 0.125rem;
    /* Slight rounding */
    padding: 0 0.1rem;
}

/* Replacement styling (combination) */
.diff-modal :deep(.ChangeReplace .Left) {
    /* Row background on left for replacement */
    background-color: rgba(153, 27, 27, 0.25) !important;
}

.diff-modal :deep(.ChangeReplace .Right) {
    /* Row background on right for replacement */
    background-color: rgba(4, 100, 65, 0.25) !important;
}


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
        /* Reduce gap on smaller screens if needed */
    }

    .diff-modal :deep(table.diff) {
        font-size: 0.65rem;
        /* Even smaller font on mobile */
    }

    .diff-modal :deep(td.lines-no) {
        width: 30px !important;
        min-width: 30px !important;
        padding-right: 0.3rem;
    }

    .diff-modal :deep(td),
    .diff-modal :deep(th) {
        padding: 0.05rem 0.2rem !important;
        /* Adjust padding */
    }
}
</style>