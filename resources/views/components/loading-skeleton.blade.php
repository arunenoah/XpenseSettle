<!-- Loading Skeleton Component -->
<div class="animate-pulse space-y-4">
    @if($type === 'card')
        <!-- Card Skeleton -->
        <div class="bg-gray-200 rounded-xl h-32"></div>
    @elseif($type === 'list')
        <!-- List Skeleton -->
        @for($i = 0; $i < ($count ?? 3); $i++)
            <div class="flex items-center gap-4 p-4 bg-gray-100 rounded-xl">
                <div class="w-12 h-12 bg-gray-300 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                </div>
                <div class="h-6 bg-gray-300 rounded w-20"></div>
            </div>
        @endfor
    @elseif($type === 'stats')
        <!-- Stats Skeleton -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-gray-200 rounded-xl p-6 space-y-3">
                    <div class="h-4 bg-gray-300 rounded w-1/2"></div>
                    <div class="h-8 bg-gray-300 rounded w-3/4"></div>
                </div>
            @endfor
        </div>
    @else
        <!-- Default Skeleton -->
        <div class="space-y-3">
            <div class="h-4 bg-gray-300 rounded w-full"></div>
            <div class="h-4 bg-gray-300 rounded w-5/6"></div>
            <div class="h-4 bg-gray-300 rounded w-4/6"></div>
        </div>
    @endif
</div>
