<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class MobileResponsivenessService extends BaseService
{
    /**
     * Detect if the request is from a mobile device
     */
    public function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');
        
        if (!$userAgent) {
            return false;
        }

        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
            'Windows Phone', 'Opera Mini', 'IEMobile', 'webOS'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if the request is from a tablet device
     */
    public function isTabletDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');
        
        if (!$userAgent) {
            return false;
        }

        $tabletKeywords = ['iPad', 'Android.*Tablet', 'Kindle', 'Silk', 'PlayBook'];

        foreach ($tabletKeywords as $keyword) {
            if (preg_match("/{$keyword}/i", $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get device type
     */
    public function getDeviceType(Request $request): string
    {
        if ($this->isTabletDevice($request)) {
            return 'tablet';
        } elseif ($this->isMobileDevice($request)) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * Get responsive breakpoints configuration
     */
    public function getBreakpoints(): array
    {
        return [
            'sm' => '640px',   // Small devices (landscape phones)
            'md' => '768px',   // Medium devices (tablets)
            'lg' => '1024px',  // Large devices (desktops)
            'xl' => '1280px',  // Extra large devices (large desktops)
            '2xl' => '1536px'  // 2X Extra large devices (larger desktops)
        ];
    }

    /**
     * Get mobile-optimized navigation items
     */
    public function getMobileNavigationItems(string $userRole): array
    {
        $baseItems = [
            [
                'name' => 'Dashboard',
                'icon' => 'home',
                'route' => $userRole . '.dashboard',
                'active_pattern' => $userRole . '.dashboard'
            ]
        ];

        switch ($userRole) {
            case 'admin':
                return array_merge($baseItems, [
                    [
                        'name' => 'Properties',
                        'icon' => 'building',
                        'route' => 'admin.properties.index',
                        'active_pattern' => 'admin.properties.*'
                    ],
                    [
                        'name' => 'Missions',
                        'icon' => 'clipboard-list',
                        'route' => 'admin.missions.index',
                        'active_pattern' => 'admin.missions.*'
                    ],
                    [
                        'name' => 'Users',
                        'icon' => 'users',
                        'route' => 'admin.users.index',
                        'active_pattern' => 'admin.users.*'
                    ],
                    [
                        'name' => 'Analytics',
                        'icon' => 'chart-bar',
                        'route' => 'admin.analytics.dashboard',
                        'active_pattern' => 'admin.analytics.*'
                    ],
                    [
                        'name' => 'Reports',
                        'icon' => 'document-report',
                        'route' => 'admin.reports.index',
                        'active_pattern' => 'admin.reports.*'
                    ],
                    [
                        'name' => 'Files',
                        'icon' => 'folder',
                        'route' => 'admin.file-manager.index',
                        'active_pattern' => 'admin.file-manager.*'
                    ],
                    [
                        'name' => 'Bulk Ops',
                        'icon' => 'cog',
                        'route' => 'admin.bulk-operations.index',
                        'active_pattern' => 'admin.bulk-operations.*'
                    ],
                    [
                        'name' => 'Search',
                        'icon' => 'search',
                        'route' => 'admin.advanced-search.index',
                        'active_pattern' => 'admin.advanced-search.*'
                    ]
                ]);

            case 'ops':
                return array_merge($baseItems, [
                    [
                        'name' => 'Properties',
                        'icon' => 'building',
                        'route' => 'ops.properties.index',
                        'active_pattern' => 'ops.properties.*'
                    ],
                    [
                        'name' => 'Missions',
                        'icon' => 'clipboard-list',
                        'route' => 'ops.missions.index',
                        'active_pattern' => 'ops.missions.*'
                    ],
                    [
                        'name' => 'Users',
                        'icon' => 'users',
                        'route' => 'ops.users.index',
                        'active_pattern' => 'ops.users.*'
                    ],
                    [
                        'name' => 'Maintenance',
                        'icon' => 'wrench',
                        'route' => 'ops.maintenance-requests.index',
                        'active_pattern' => 'ops.maintenance-requests.*'
                    ]
                ]);

            case 'checker':
                return array_merge($baseItems, [
                    [
                        'name' => 'My Missions',
                        'icon' => 'clipboard-list',
                        'route' => 'checker.dashboard',
                        'active_pattern' => 'checker.*'
                    ]
                ]);

            default:
                return $baseItems;
        }
    }

    /**
     * Get mobile-optimized table configuration
     */
    public function getMobileTableConfig(string $tableType): array
    {
        $configs = [
            'missions' => [
                'mobile_columns' => ['title', 'status', 'checker'],
                'hidden_columns' => ['description', 'created_at', 'property_address'],
                'expandable' => true,
                'card_view' => true
            ],
            'users' => [
                'mobile_columns' => ['name', 'role'],
                'hidden_columns' => ['email', 'created_at', 'last_login_at'],
                'expandable' => true,
                'card_view' => true
            ],
            'properties' => [
                'mobile_columns' => ['property_address', 'property_type'],
                'hidden_columns' => ['owner_name', 'owner_address', 'description'],
                'expandable' => true,
                'card_view' => true
            ],
            'maintenance_requests' => [
                'mobile_columns' => ['description', 'status', 'priority'],
                'hidden_columns' => ['created_at', 'assigned_to', 'estimated_cost'],
                'expandable' => true,
                'card_view' => true
            ]
        ];

        return $configs[$tableType] ?? [
            'mobile_columns' => [],
            'hidden_columns' => [],
            'expandable' => false,
            'card_view' => false
        ];
    }

    /**
     * Get touch-friendly button configuration
     */
    public function getTouchButtonConfig(): array
    {
        return [
            'min_height' => '44px',  // Apple's recommended minimum touch target
            'min_width' => '44px',
            'padding' => '12px 16px',
            'margin' => '8px',
            'border_radius' => '8px',
            'font_size' => '16px'    // Prevents zoom on iOS
        ];
    }

    /**
     * Get mobile form configuration
     */
    public function getMobileFormConfig(): array
    {
        return [
            'input_height' => '48px',
            'input_font_size' => '16px',  // Prevents zoom on iOS
            'label_font_size' => '14px',
            'spacing' => '16px',
            'border_radius' => '8px',
            'focus_ring' => '2px',
            'touch_padding' => '12px'
        ];
    }

    /**
     * Generate Progressive Web App manifest
     */
    public function generatePWAManifest(): array
    {
        return [
            'name' => config('app.name', 'Property Management System'),
            'short_name' => 'PropMgmt',
            'description' => 'Property Management System for efficient property operations',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#4f46e5',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => '/images/icons/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ],
            'categories' => ['business', 'productivity'],
            'lang' => 'en',
            'dir' => 'ltr'
        ];
    }

    /**
     * Get mobile-optimized CSS classes
     */
    public function getMobileCSSClasses(): array
    {
        return [
            'container' => 'px-4 sm:px-6 lg:px-8',
            'grid' => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6',
            'card' => 'bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6',
            'button_primary' => 'w-full sm:w-auto bg-indigo-600 text-white py-3 px-6 rounded-lg font-medium text-base hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200',
            'button_secondary' => 'w-full sm:w-auto bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-medium text-base hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200',
            'input' => 'w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
            'select' => 'w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
            'textarea' => 'w-full px-4 py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none',
            'table_mobile' => 'block sm:table w-full',
            'table_row_mobile' => 'block sm:table-row border-b border-gray-200 mb-4 sm:mb-0 bg-white sm:bg-transparent rounded-lg sm:rounded-none shadow-sm sm:shadow-none p-4 sm:p-0',
            'table_cell_mobile' => 'block sm:table-cell py-2 sm:py-4 px-0 sm:px-6 text-sm',
            'navigation_mobile' => 'fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 sm:hidden z-50',
            'navigation_item' => 'flex-1 flex flex-col items-center py-2 px-1 text-xs font-medium',
            'modal_mobile' => 'fixed inset-0 z-50 overflow-y-auto',
            'modal_content_mobile' => 'min-h-screen px-4 text-center sm:min-h-0 sm:p-0',
            'modal_panel_mobile' => 'inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl sm:rounded-lg'
        ];
    }

    /**
     * Cache mobile detection result
     */
    public function cacheMobileDetection(Request $request): string
    {
        $userAgent = $request->header('User-Agent');
        $cacheKey = 'mobile_detection_' . md5($userAgent);
        
        return Cache::remember($cacheKey, 3600, function () use ($request) {
            return $this->getDeviceType($request);
        });
    }

    /**
     * Get viewport meta tag configuration
     */
    public function getViewportConfig(): string
    {
        return 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
    }

    /**
     * Get mobile-specific JavaScript configuration
     */
    public function getMobileJSConfig(): array
    {
        return [
            'touch_events' => true,
            'swipe_gestures' => true,
            'pull_to_refresh' => true,
            'infinite_scroll' => true,
            'offline_support' => true,
            'push_notifications' => true,
            'device_orientation' => true,
            'haptic_feedback' => true
        ];
    }
}