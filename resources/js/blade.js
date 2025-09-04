import Alpine from 'alpinejs';
import 'flowbite';

// TW Elements imports
import { Collapse, Dropdown, Modal, Tooltip, Tab, Ripple } from 'tw-elements';

// Initialize Alpine.js
window.Alpine = Alpine;

// Alpine.js Data Components
Alpine.data('missionForm', () => ({
    form: {
        property_address: '',
        mission_type: '',
        assigned_agent_id: '',
        scheduled_date: ''
    },
    errors: {},
    loading: false,

    validateField(field) {
        if (!this.form[field]) {
            this.errors[field] = `${field.replace('_', ' ')} is required`;
        } else {
            delete this.errors[field];
        }
    },

    async submitForm() {
        this.loading = true;
        this.errors = {};

        // Validate all fields
        Object.keys(this.form).forEach(field => this.validateField(field));

        if (Object.keys(this.errors).length === 0) {
            try {
                const response = await fetch('/missions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    window.location.href = '/missions';
                } else {
                    const data = await response.json();
                    this.errors = data.errors || { general: 'An error occurred' };
                }
            } catch (error) {
                this.errors = { general: 'Network error occurred' };
            }
        }

        this.loading = false;
    }
}));

Alpine.data('checklistForm', () => ({
    items: {},
    signatures: {},
    photos: {},
    loading: false,
    errors: {},

    addPhoto(itemId, files) {
        if (!this.photos[itemId]) {
            this.photos[itemId] = [];
        }
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                this.photos[itemId].push({
                    file,
                    url: URL.createObjectURL(file),
                    name: file.name
                });
            }
        });
    },

    removePhoto(itemId, index) {
        if (this.photos[itemId]) {
            URL.revokeObjectURL(this.photos[itemId][index].url);
            this.photos[itemId].splice(index, 1);
        }
    },

    async submitChecklist(missionId) {
        this.loading = true;
        const formData = new FormData();
        
        formData.append('items', JSON.stringify(this.items));
        formData.append('signatures', JSON.stringify(this.signatures));
        
        // Add photos
        Object.entries(this.photos).forEach(([itemId, photoArray]) => {
            photoArray.forEach((photo, index) => {
                formData.append(`photos[${itemId}][${index}]`, photo.file);
            });
        });

        try {
            const response = await fetch(`/checklists/${missionId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            if (response.ok) {
                window.location.href = `/missions/${missionId}`;
            } else {
                const data = await response.json();
                this.errors = data.errors || { general: 'An error occurred' };
            }
        } catch (error) {
            this.errors = { general: 'Network error occurred' };
        }

        this.loading = false;
    }
}));

Alpine.data('signaturePad', () => ({
    canvas: null,
    ctx: null,
    drawing: false,
    signature: null,

    init() {
        this.canvas = this.$refs.canvas;
        this.ctx = this.canvas.getContext('2d');
        this.setupCanvas();
    },

    setupCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width * window.devicePixelRatio;
        this.canvas.height = rect.height * window.devicePixelRatio;
        this.ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
        this.ctx.lineCap = 'round';
        this.ctx.lineWidth = 2;
        this.ctx.strokeStyle = '#000';
    },

    startDrawing(e) {
        this.drawing = true;
        const pos = this.getMousePos(e);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    },

    draw(e) {
        if (!this.drawing) return;
        const pos = this.getMousePos(e);
        this.ctx.lineTo(pos.x, pos.y);
        this.ctx.stroke();
    },

    stopDrawing() {
        if (this.drawing) {
            this.drawing = false;
            this.signature = this.canvas.toDataURL();
        }
    },

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.signature = null;
    },

    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }
}));

Alpine.data('dashboard', () => ({
    stats: {},
    chartData: {},
    loading: true,

    async init() {
        await this.fetchDashboardData();
        this.initCharts();
    },

    async fetchDashboardData() {
        try {
            const response = await fetch('/api/dashboard');
            const data = await response.json();
            this.stats = data.stats || {};
            this.chartData = data.chartData || {};
        } catch (error) {
            console.error('Failed to fetch dashboard data:', error);
        } finally {
            this.loading = false;
        }
    },

    initCharts() {
        // Initialize Chart.js charts here
        if (window.Chart && this.chartData.missions) {
            // Mission completion chart
            const ctx = document.getElementById('missionsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: this.chartData.missions,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }
    }
}));

Alpine.data('notifications', () => ({
    notifications: [],
    unreadCount: 0,

    async init() {
        await this.fetchNotifications();
        this.setupEventListeners();
    },

    async fetchNotifications() {
        try {
            const response = await fetch('/api/notifications');
            const data = await response.json();
            this.notifications = data.notifications || [];
            this.unreadCount = data.unreadCount || 0;
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    },

    async markAsRead(notificationId) {
        try {
            await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification && !notification.read_at) {
                notification.read_at = new Date().toISOString();
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    },

    setupEventListeners() {
        // Setup WebSocket or Server-Sent Events for real-time notifications
        if (window.Echo) {
            window.Echo.private(`user.${window.userId}`)
                .notification((notification) => {
                    this.notifications.unshift(notification);
                    this.unreadCount++;
                });
        }
    }
}));

// Global Alpine.js stores
Alpine.store('app', {
    loading: false,
    user: null,
    
    setLoading(value) {
        this.loading = value;
    },
    
    setUser(user) {
        this.user = user;
    }
});

// Initialize TW Elements
document.addEventListener('DOMContentLoaded', () => {
    // Flowbite initializes automatically
    
    // Initialize TW Elements
    Collapse.init();
    Dropdown.init();
    Modal.init();
    Tooltip.init();
    Tab.init();
    Ripple.init();
});

// Global helper functions
window.showToast = function(message, type = 'success') {
    // Create and show toast notification
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
};

window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

// Initialize Alpine.js
Alpine.start();

console.log('Blade.js loaded with Alpine.js, Flowbite, and TW Elements');
