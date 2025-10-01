@props(['type' => 'default', 'items' => [], 'columns' => [], 'actions' => []])

@php
    $mobileService = app(\App\Services\MobileResponsivenessService::class);
    $config = $mobileService->getMobileTableConfig($type);
    $cssClasses = $mobileService->getMobileCSSClasses();
@endphp

<div class="overflow-hidden">
    <!-- Desktop Table View -->
    <div class="hidden sm:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($columns as $column)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                    @if(count($actions) > 0)
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50">
                        @foreach($columns as $column)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if(isset($column['component']))
                                    @include($column['component'], ['item' => $item])
                                @else
                                    {{ data_get($item, $column['field']) }}
                                @endif
                            </td>
                        @endforeach
                        @if(count($actions) > 0)
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    @foreach($actions as $action)
                                        @if(isset($action['component']))
                                            @include($action['component'], ['item' => $item])
                                        @else
                                            <a href="{{ str_replace('{id}', $item->id, $action['url']) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                {{ $action['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="sm:hidden space-y-4">
        @foreach($items as $item)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <!-- Main Content -->
                <div class="space-y-3">
                    @foreach($config['mobile_columns'] as $columnKey)
                        @php
                            $column = collect($columns)->firstWhere('field', $columnKey);
                        @endphp
                        @if($column)
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                        {{ $column['label'] }}
                                    </div>
                                    <div class="text-sm text-gray-900">
                                        @if(isset($column['component']))
                                            @include($column['component'], ['item' => $item])
                                        @else
                                            {{ data_get($item, $column['field']) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Expandable Details -->
                @if($config['expandable'] && count($config['hidden_columns']) > 0)
                    <div class="mt-4">
                        <button class="expand-details-btn text-indigo-600 text-sm font-medium hover:text-indigo-900 focus:outline-none" 
                                data-target="details-{{ $item->id }}">
                            <span class="expand-text">Show Details</span>
                            <span class="collapse-text hidden">Hide Details</span>
                            <svg class="inline-block w-4 h-4 ml-1 transform transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        <div id="details-{{ $item->id }}" class="hidden mt-3 pt-3 border-t border-gray-200 space-y-2">
                            @foreach($config['hidden_columns'] as $columnKey)
                                @php
                                    $column = collect($columns)->firstWhere('field', $columnKey);
                                @endphp
                                @if($column)
                                    <div class="flex justify-between">
                                        <span class="text-xs font-medium text-gray-500">{{ $column['label'] }}:</span>
                                        <span class="text-xs text-gray-900 text-right">
                                            @if(isset($column['component']))
                                                @include($column['component'], ['item' => $item])
                                            @else
                                                {{ data_get($item, $column['field']) }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                @if(count($actions) > 0)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex flex-wrap gap-2">
                            @foreach($actions as $action)
                                @if(isset($action['component']))
                                    @include($action['component'], ['item' => $item])
                                @else
                                    <a href="{{ str_replace('{id}', $item->id, $action['url']) }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        {{ $action['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Empty State -->
    @if(count($items) === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 00-2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No items found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new item.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle expandable details
        document.querySelectorAll('.expand-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const details = document.getElementById(targetId);
                const expandText = this.querySelector('.expand-text');
                const collapseText = this.querySelector('.collapse-text');
                const icon = this.querySelector('svg');
                
                if (details.classList.contains('hidden')) {
                    details.classList.remove('hidden');
                    expandText.classList.add('hidden');
                    collapseText.classList.remove('hidden');
                    icon.classList.add('rotate-180');
                } else {
                    details.classList.add('hidden');
                    expandText.classList.remove('hidden');
                    collapseText.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
            });
        });
    });
</script>