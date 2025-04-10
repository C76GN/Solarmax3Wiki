import { nextTick } from 'vue';
import axios from 'axios';
export const scrollToMessageBottom = (selector = '.chat-messages') => {
    nextTick(() => {
        const container = document.querySelector(selector);
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
};
export const savePageDraft = async (pageId, content, callback, errorCallback) => {
    if (!pageId) return Promise.reject(new Error('缺少页面ID'));
    try {
        const response = await axios.post(route('wiki.save-draft', pageId), {
            content: content
        });
        if (typeof callback === 'function') {
            callback(response.data);
        }
        return response.data;
    } catch (error) {
        console.error('保存草稿失败:', error);
        if (typeof errorCallback === 'function') {
            errorCallback(error);
        }
        throw error;
    }
};
export const debounce = (func, delay) => {
    let timer = null;
    return function (...args) {
        const context = this;
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => {
            func.apply(context, args);
            timer = null;
        }, delay);
    };
};
export const throttle = (func, limit) => {
    let lastFunc;
    let lastRan;
    return function (...args) {
        const context = this;
        if (!lastRan) {
            func.apply(context, args);
            lastRan = Date.now();
        } else {
            clearTimeout(lastFunc);
            lastFunc = setTimeout(() => {
                if ((Date.now() - lastRan) >= limit) {
                    func.apply(context, args);
                    lastRan = Date.now();
                }
            }, limit - (Date.now() - lastRan));
        }
    };
};
export const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
export const isImageFile = (file) => {
    return file && file.type.startsWith('image/');
};
export const stripHtml = (html) => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};
/**
 * 生成内容摘要
 * @param {string} content - 内容（HTML或纯文本）
 * @param {number} length - 摘要长度
 * @returns {string} - 摘要
 */
export const createExcerpt = (content, length = 150) => {
    const text = stripHtml(content);
    if (text.length <= length) {
        return text;
    }
    return text.substring(0, length) + '...';
};