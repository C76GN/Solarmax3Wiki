/**
 * 格式化日期和时间
 * @param {string|Date} date 要格式化的日期
 * @param {Object} options 格式化选项
 * @param {boolean} includeTime 是否包含时间
 * @returns {string} 格式化后的日期字符串
 */
export const formatDate = (date, options = {}, includeTime = true) => {
    if (!date) return '';

    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        ...(includeTime ? {
            hour: '2-digit',
            minute: '2-digit'
        } : {})
    };

    const mergedOptions = { ...defaultOptions, ...options };
    return new Date(date).toLocaleString('zh-CN', mergedOptions);
};

/**
 * 格式化短日期（不含时间）
 * @param {string|Date} date 要格式化的日期
 * @returns {string} 格式化后的日期字符串
 */
export const formatDateShort = (date) => {
    return formatDate(date, {}, false);
};

/**
 * 格式化日期和时间（包含秒）
 * @param {string|Date} date 要格式化的日期
 * @param {Object} options 格式化选项
 * @returns {string} 格式化后的日期时间字符串
 */
export const formatDateTime = (date, options = {}) => {
    if (!date) return '';

    const defaultOptions = {
        second: '2-digit'
    };

    return formatDate(date, { ...defaultOptions, ...options });
};