<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Backup Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Health Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">System Health</h3>
                        <button id="refresh-health" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                            Refresh
                        </button>
                    </div>
                    <div id="health-status" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Health status will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Database Backups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Database Backups</h3>
                        <div id="db-stats">
                            <!-- Database stats will be loaded here -->
                        </div>
                        <div class="mt-4">
                            <button id="create-db-backup" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm mr-2">
                                Create Backup
                            </button>
                            <button id="list-db-backups" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                View All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Backups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">File Backups</h3>
                        <div id="file-stats">
                            <!-- File stats will be loaded here -->
                        </div>
                        <div class="mt-4">
                            <button id="create-file-backup" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm mr-2">
                                Create Backup
                            </button>
                            <button id="list-file-backups" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                View All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Storage Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Storage</h3>
                        <div id="storage-stats">
                            <!-- Storage stats will be loaded here -->
                        </div>
                        <div class="mt-4">
                            <button id="cleanup-backups" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm mr-2">
                                Cleanup Old
                            </button>
                            <button id="test-system" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Test System
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Lists -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Database Backups List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Database Backups</h3>
                        <div id="db-backups-list" class="space-y-2">
                            <!-- Database backups list will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- File Backups List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent File Backups</h3>
                        <div id="file-backups-list" class="space-y-2">
                            <!-- File backups list will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Create Database Backup Modal -->
    <div id="db-backup-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create Database Backup</h3>
                <form id="db-backup-form">
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="compress" checked class="mr-2">
                            Compress backup
                        </label>
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="encrypt" class="mr-2">
                            Encrypt backup
                        </label>
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="verify" checked class="mr-2">
                            Verify after creation
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-db-backup" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Create Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create File Backup Modal -->
    <div id="file-backup-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create File Backup</h3>
                <form id="file-backup-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                        <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="incremental">Incremental</option>
                            <option value="full">Full</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="encrypt" class="mr-2">
                            Encrypt backup
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-file-backup" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Create Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            
            // Event listeners
            document.getElementById('refresh-health').addEventListener('click', loadDashboard);
            document.getElementById('create-db-backup').addEventListener('click', showDbBackupModal);
            document.getElementById('create-file-backup').addEventListener('click', showFileBackupModal);
            document.getElementById('list-db-backups').addEventListener('click', loadDbBackups);
            document.getElementById('list-file-backups').addEventListener('click', loadFileBackups);
            document.getElementById('cleanup-backups').addEventListener('click', showCleanupOptions);
            document.getElementById('test-system').addEventListener('click', testBackupSystem);
            
            // Modal event listeners
            document.getElementById('cancel-db-backup').addEventListener('click', hideDbBackupModal);
            document.getElementById('cancel-file-backup').addEventListener('click', hideFileBackupModal);
            document.getElementById('db-backup-form').addEventListener('submit', createDbBackup);
            document.getElementById('file-backup-form').addEventListener('submit', createFileBackup);
        });

        async function loadDashboard() {
            try {
                const response = await fetch('/admin/backups/dashboard');
                const result = await response.json();
                
                if (result.success) {
                    updateHealthStatus(result.data.health);
                    updateStatistics(result.data.statistics);
                    loadDbBackups();
                    loadFileBackups();
                }
            } catch (error) {
                console.error('Failed to load dashboard:', error);
            }
        }

        function updateHealthStatus(health) {
            const container = document.getElementById('health-status');
            if (!health) {
                container.innerHTML = '<div class="text-red-500">Failed to load health status</div>';
                return;
            }

            const statusColor = {
                'healthy': 'text-green-500',
                'warning': 'text-yellow-500',
                'error': 'text-red-500'
            };

            const statusIcon = {
                'healthy': '✓',
                'warning': '⚠',
                'error': '✗'
            };

            container.innerHTML = `
                <div class="text-center">
                    <div class="text-2xl ${statusColor[health.overall_status] || 'text-gray-500'}">
                        ${statusIcon[health.overall_status] || '?'}
                    </div>
                    <div class="text-sm font-medium">Overall</div>
                    <div class="text-xs text-gray-500">${health.overall_status}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl ${statusColor[health.database_backups?.status] || 'text-gray-500'}">
                        ${statusIcon[health.database_backups?.status] || '?'}
                    </div>
                    <div class="text-sm font-medium">Database</div>
                    <div class="text-xs text-gray-500">${health.database_backups?.status || 'unknown'}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl ${statusColor[health.file_backups?.status] || 'text-gray-500'}">
                        ${statusIcon[health.file_backups?.status] || '?'}
                    </div>
                    <div class="text-sm font-medium">Files</div>
                    <div class="text-xs text-gray-500">${health.file_backups?.status || 'unknown'}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl ${statusColor[health.storage_health?.status] || 'text-gray-500'}">
                        ${statusIcon[health.storage_health?.status] || '?'}
                    </div>
                    <div class="text-sm font-medium">Storage</div>
                    <div class="text-xs text-gray-500">${health.storage_health?.status || 'unknown'}</div>
                </div>
            `;
        }

        function updateStatistics(stats) {
            if (!stats) return;

            // Database stats
            const dbStatsContainer = document.getElementById('db-stats');
            if (stats.database && !stats.database.error) {
                const db = stats.database;
                dbStatsContainer.innerHTML = `
                    <div class="text-sm text-gray-600">
                        <div>Total: ${db.total_count} backups</div>
                        <div>Size: ${db.total_size_human}</div>
                        ${db.latest_backup ? `<div>Latest: ${db.latest_backup.age_hours}h ago</div>` : ''}
                    </div>
                `;
            } else {
                dbStatsContainer.innerHTML = '<div class="text-red-500 text-sm">Error loading stats</div>';
            }

            // File stats
            const fileStatsContainer = document.getElementById('file-stats');
            if (stats.files && !stats.files.error) {
                const files = stats.files;
                fileStatsContainer.innerHTML = `
                    <div class="text-sm text-gray-600">
                        <div>Total: ${files.total_count} backups</div>
                        <div>Size: ${files.total_size_human}</div>
                        ${files.latest_backup ? `<div>Latest: ${files.latest_backup.age_hours}h ago</div>` : ''}
                    </div>
                `;
            } else {
                fileStatsContainer.innerHTML = '<div class="text-red-500 text-sm">Error loading stats</div>';
            }

            // Storage stats
            const storageStatsContainer = document.getElementById('storage-stats');
            if (stats.storage && stats.storage.accessible) {
                const storage = stats.storage;
                storageStatsContainer.innerHTML = `
                    <div class="text-sm text-gray-600">
                        <div>Disk: ${storage.disk}</div>
                        ${storage.used_percent ? `<div>Used: ${storage.used_percent}%</div>` : ''}
                        ${storage.free_space_human ? `<div>Free: ${storage.free_space_human}</div>` : ''}
                    </div>
                `;
            } else {
                storageStatsContainer.innerHTML = '<div class="text-red-500 text-sm">Storage not accessible</div>';
            }
        }

        async function loadDbBackups() {
            try {
                const response = await fetch('/admin/backups/database');
                const result = await response.json();
                
                const container = document.getElementById('db-backups-list');
                
                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.slice(0, 5).map(backup => `
                        <div class="flex justify-between items-center p-2 border rounded">
                            <div>
                                <div class="font-medium text-sm">${backup.filename}</div>
                                <div class="text-xs text-gray-500">${backup.size_human} • ${backup.created_at}</div>
                            </div>
                            <div class="flex space-x-1">
                                <button onclick="verifyBackup('${backup.path}', 'database')" class="text-blue-500 text-xs">Verify</button>
                                <button onclick="downloadBackup('${backup.path}')" class="text-green-500 text-xs">Download</button>
                                <button onclick="deleteBackup('${backup.path}')" class="text-red-500 text-xs">Delete</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="text-gray-500 text-sm">No backups found</div>';
                }
            } catch (error) {
                console.error('Failed to load database backups:', error);
            }
        }

        async function loadFileBackups() {
            try {
                const response = await fetch('/admin/backups/files');
                const result = await response.json();
                
                const container = document.getElementById('file-backups-list');
                
                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.slice(0, 5).map(backup => `
                        <div class="flex justify-between items-center p-2 border rounded">
                            <div>
                                <div class="font-medium text-sm">${backup.filename}</div>
                                <div class="text-xs text-gray-500">${backup.size_human} • ${backup.type} • ${backup.created_at}</div>
                            </div>
                            <div class="flex space-x-1">
                                <button onclick="verifyBackup('${backup.path}', 'file')" class="text-blue-500 text-xs">Verify</button>
                                <button onclick="downloadBackup('${backup.path}')" class="text-green-500 text-xs">Download</button>
                                <button onclick="deleteBackup('${backup.path}')" class="text-red-500 text-xs">Delete</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="text-gray-500 text-sm">No backups found</div>';
                }
            } catch (error) {
                console.error('Failed to load file backups:', error);
            }
        }

        // Modal functions
        function showDbBackupModal() {
            document.getElementById('db-backup-modal').classList.remove('hidden');
        }

        function hideDbBackupModal() {
            document.getElementById('db-backup-modal').classList.add('hidden');
        }

        function showFileBackupModal() {
            document.getElementById('file-backup-modal').classList.remove('hidden');
        }

        function hideFileBackupModal() {
            document.getElementById('file-backup-modal').classList.add('hidden');
        }

        async function createDbBackup(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                compress: formData.has('compress'),
                encrypt: formData.has('encrypt'),
                verify: formData.has('verify')
            };

            try {
                const response = await fetch('/admin/backups/database', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Database backup created successfully!');
                    hideDbBackupModal();
                    loadDashboard();
                } else {
                    alert('Failed to create backup: ' + result.message);
                }
            } catch (error) {
                alert('Error creating backup: ' + error.message);
            }
        }

        async function createFileBackup(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                type: formData.get('type'),
                encrypt: formData.has('encrypt')
            };

            try {
                const response = await fetch('/admin/backups/files', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('File backup created successfully!');
                    hideFileBackupModal();
                    loadDashboard();
                } else {
                    alert('Failed to create backup: ' + result.message);
                }
            } catch (error) {
                alert('Error creating backup: ' + error.message);
            }
        }

        async function verifyBackup(path, type) {
            try {
                const response = await fetch('/admin/backups/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ path, type })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Backup verification successful!');
                } else {
                    alert('Backup verification failed: ' + result.message);
                }
            } catch (error) {
                alert('Error verifying backup: ' + error.message);
            }
        }

        function downloadBackup(path) {
            const url = `/admin/backups/download?path=${encodeURIComponent(path)}`;
            window.open(url, '_blank');
        }

        async function deleteBackup(path) {
            if (!confirm('Are you sure you want to delete this backup?')) {
                return;
            }

            try {
                const response = await fetch('/admin/backups/delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ path })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Backup deleted successfully!');
                    loadDashboard();
                } else {
                    alert('Failed to delete backup: ' + result.message);
                }
            } catch (error) {
                alert('Error deleting backup: ' + error.message);
            }
        }

        function showCleanupOptions() {
            const days = prompt('Delete backups older than how many days?', '30');
            if (days && !isNaN(days)) {
                cleanupBackups(parseInt(days));
            }
        }

        async function cleanupBackups(retentionDays) {
            const type = confirm('Clean up database backups? (Cancel for file backups)') ? 'database' : 'file';
            
            try {
                const response = await fetch('/admin/backups/cleanup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ type, retention_days: retentionDays })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(`Cleanup completed: ${result.data.deleted_count} files deleted (${result.data.deleted_size_human})`);
                    loadDashboard();
                } else {
                    alert('Cleanup failed: ' + result.message);
                }
            } catch (error) {
                alert('Error during cleanup: ' + error.message);
            }
        }

        async function testBackupSystem() {
            if (!confirm('This will create and delete test backups. Continue?')) {
                return;
            }

            try {
                const response = await fetch('/admin/backups/test', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    const status = data.all_tests_passed ? 'All tests passed!' : 'Some tests failed!';
                    alert(`${status}\n\nResults:\n${Object.entries(data.results).map(([k, v]) => `${k}: ${v ? 'PASS' : 'FAIL'}`).join('\n')}`);
                } else {
                    alert('Test failed: ' + result.message);
                }
            } catch (error) {
                alert('Error running test: ' + error.message);
            }
        }
    </script>
</x-app-layout>