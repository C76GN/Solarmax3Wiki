/**
 * 格式化日期为本地化字符串
 * @param {string|Date} date - 要格式化的日期
 * @param {Object} options - 格式化选项
 * @returns {string} 格式化后的日期字符串
 */
export const formatDate = (date, options = {}) => {
    if (!date) return '';

    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };

    const mergedOptions = { ...defaultOptions, ...options };
    return new Date(date).toLocaleString('zh-CN', mergedOptions);
};

/**
 * 格式化日期为简短格式
 * @param {string|Date} date - 要格式化的日期
 * @returns {string} 格式化后的日期字符串
 */
export const formatDateShort = (date) => {
    if (!date) return '';
    return formatDate(date, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};


export const formatDateTime = (date, options = {}) => {
    if (!date) return '';
    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const mergedOptions = { ...defaultOptions, ...options };
    return new Date(date).toLocaleString('zh-CN', mergedOptions);
};