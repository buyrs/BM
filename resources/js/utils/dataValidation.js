/**
 * Data validation and sanitization utilities
 */

/**
 * Safely get a nested property from an object
 */
export function safeGet(obj, path, defaultValue = null) {
    if (!obj || typeof obj !== 'object') return defaultValue;
    
    const keys = path.split('.');
    let current = obj;
    
    for (const key of keys) {
        if (current === null || current === undefined || typeof current !== 'object') {
            return defaultValue;
        }
        current = current[key];
    }
    
    return current !== undefined ? current : defaultValue;
}

/**
 * Validate and sanitize statistics object
 */
export function validateStats(stats) {
    if (!stats || typeof stats !== 'object') {
        return {
            totalMissions: 0,
            assignedMissions: 0,
            completedMissions: 0,
            totalCheckers: 0,
            activeCheckers: 0,
            onlineCheckers: 0
        };
    }

    return {
        totalMissions: Number(stats.totalMissions) || 0,
        assignedMissions: Number(stats.assignedMissions) || 0,
        completedMissions: Number(stats.completedMissions) || 0,
        totalCheckers: stats.totalCheckers !== undefined ? Number(stats.totalCheckers) || 0 : undefined,
        activeCheckers: Number(stats.activeCheckers) || 0,
        onlineCheckers: Number(stats.onlineCheckers) || 0
    };
}

/**
 * Validate and sanitize missions array
 */
export function validateMissions(missions) {
    if (!Array.isArray(missions)) {
        return [];
    }

    return missions.filter(mission => {
        return mission && 
               typeof mission === 'object' && 
               mission.id !== undefined;
    }).map(mission => ({
        id: mission.id,
        address: String(mission.address || 'N/A'),
        status: String(mission.status || 'unknown'),
        type: String(mission.type || 'unknown'),
        tenant_name: String(mission.tenant_name || 'N/A'),
        scheduled_at: mission.scheduled_at || null,
        created_at: mission.created_at || null,
        completed_at: mission.completed_at || null,
        agent: mission.agent || null,
        bail_mobilite: mission.bail_mobilite || null
    }));
}

/**
 * Validate and sanitize user object
 */
export function validateUser(user) {
    if (!user || typeof user !== 'object') {
        return {
            id: null,
            name: 'Unknown User',
            email: '',
            roles: []
        };
    }

    return {
        id: user.id || null,
        name: String(user.name || 'Unknown User'),
        email: String(user.email || ''),
        roles: Array.isArray(user.roles) ? user.roles : []
    };
}

/**
 * Validate date string and return formatted date
 */
export function validateAndFormatDate(dateString, fallback = 'N/A') {
    if (!dateString) return fallback;
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return fallback;
        
        return date.toLocaleDateString("en-US", {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (error) {
        return fallback;
    }
}

/**
 * Validate and format time string
 */
export function validateAndFormatTime(dateString, fallback = 'N/A') {
    if (!dateString) return fallback;
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return fallback;
        
        return date.toLocaleTimeString("en-US", {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return fallback;
    }
}

/**
 * Sanitize HTML content to prevent XSS
 */
export function sanitizeHtml(html) {
    if (typeof html !== 'string') return '';
    
    // Basic HTML sanitization - in production, use a proper library like DOMPurify
    return html
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/\//g, '&#x2F;');
}

/**
 * Validate numeric value with bounds
 */
export function validateNumber(value, min = -Infinity, max = Infinity, fallback = 0) {
    const num = Number(value);
    if (isNaN(num)) return fallback;
    return Math.min(Math.max(num, min), max);
}

/**
 * Validate percentage value (0-100)
 */
export function validatePercentage(value, fallback = 0) {
    return validateNumber(value, 0, 100, fallback);
}

/**
 * Validate array and ensure it's not empty
 */
export function validateArray(arr, fallback = []) {
    return Array.isArray(arr) ? arr : fallback;
}

/**
 * Validate string and provide fallback
 */
export function validateString(str, fallback = '') {
    return typeof str === 'string' ? str : String(str || fallback);
}

/**
 * Validate boolean value
 */
export function validateBoolean(value, fallback = false) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'string') {
        return value.toLowerCase() === 'true' || value === '1';
    }
    if (typeof value === 'number') {
        return value !== 0;
    }
    return fallback;
}

/**
 * Deep clone object safely
 */
export function safeClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    
    try {
        return JSON.parse(JSON.stringify(obj));
    } catch (error) {
        console.warn('Failed to clone object:', error);
        return obj;
    }
}

/**
 * Merge objects safely
 */
export function safeMerge(target, source) {
    if (!target || typeof target !== 'object') target = {};
    if (!source || typeof source !== 'object') return target;
    
    const result = { ...target };
    
    for (const key in source) {
        if (source.hasOwnProperty(key)) {
            if (typeof source[key] === 'object' && source[key] !== null && !Array.isArray(source[key])) {
                result[key] = safeMerge(result[key], source[key]);
            } else {
                result[key] = source[key];
            }
        }
    }
    
    return result;
}