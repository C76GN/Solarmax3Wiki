import { ref } from 'vue'

export const useEditor = () => {
    const init = {
        language: 'zh_CN',
        selector: 'textarea#content',
        base_url: '/tinymce', // 指向我们复制的 tinymce 文件目录
        suffix: '.min',
        height: 500,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.withCredentials = false;

                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.upload.onprogress = function (e) {
                    progress(e.loaded / e.total * 100);
                };

                xhr.onload = function () {
                    if (xhr.status === 403) {
                        reject('无权限上传图片');
                        return;
                    }

                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('上传失败');
                        return;
                    }

                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location != 'string') {
                            reject('无效的上传响应');
                            return;
                        }
                        resolve(json.location);
                    } catch (e) {
                        reject('无效的上传响应');
                    }
                };

                xhr.onerror = function () {
                    reject('上传失败');
                };

                xhr.open('POST', '/api/upload-image');
                xhr.send(formData);
            });
        },
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | bold italic underline strikethrough | ' +
            'fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | ' +
            'outdent indent | numlist bullist | forecolor backcolor removeformat | ' +
            'charmap emoticons | code fullscreen preview | ' +
            'image media table link anchor | ltr rtl | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
        branding: false,
        promotion: false,
        // 使用 GPL 许可证
        license_key: 'gpl',
    }

    const content = ref('')

    return {
        init,
        content
    }
}