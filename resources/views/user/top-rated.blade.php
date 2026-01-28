@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        🏆 Top 5 Rated Teas
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        Highest-rated teas based on user ratings and reviews
    </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($topTeas as $index => $tea)
        <div class="tea-card relative overflow-hidden">
            <!-- Ranking Badge -->
            <div class="absolute top-4 left-4 z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-lg" 
                     style="background: {{ $index === 0 ? 'var(--accent-green)' : ($index === 1 ? 'var(--primary-green)' : ($index === 2 ? 'var(--light-green)' : 'var(--cream-green)')) }};">
                    {{ $index + 1 }}
                </div>
            </div>
            
            @php
                $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                $imgSrc = $tea->image
                    ? (str_starts_with($tea->image, 'http') ? $tea->image
                        : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                        : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                    : $fallbackImage;
            @endphp
            
            <img src="{{ $imgSrc }}" class="w-full h-48 object-cover">
            
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg" style="color: var(--text-dark);">
                        {{ $tea->name }}
                    </h3>
                    <div class="text-right">
                        <div class="text-base font-bold" style="color: var(--accent-green);">
                            {{ number_format($tea->averageRating, 1) }}
                        </div>
                        <div class="text-xs" style="color: var(--text-light);">
                            ({{ $tea->ratings_count }} reviews)
                        </div>
                    </div>
                </div>
            
            <div class="space-y-1 mb-3">
                    <div class="flex items-center text-xs" style="color: var(--text-medium);">
                        <span class="mr-2">🍃</span> Flavor: {{ $tea->flavor }}
                    </div>
                    <div class="flex items-center text-xs" style="color: var(--text-medium);">
                        <span class="mr-2">⚡</span> Caffeine: {{ $tea->caffeine_level }}
                    </div>
                    <div class="flex items-center text-xs" style="color: var(--text-medium);">
                        <span class="mr-2">🌿</span> {{ $tea->health_benefit }}
                    </div>
                </div>
                
                <!-- Recent Raters -->
                @if($tea->ratings->isNotEmpty())
                    <div class="border-t pt-2" style="border-color: var(--border-color);">
                        <h4 class="font-semibold text-xs mb-2" style="color: var(--text-dark);">Recent Ratings:</h4>
                        <div class="space-y-1">
                            @foreach($tea->ratings->take(3) as $rating)
                                <div class="flex justify-between items-center p-1 rounded" style="background: var(--pale-green);">
                                    <span class="text-xs font-medium" style="color: var(--text-dark);">{{ $rating->user->name }}</span>
                                    <span class="text-xs font-bold" style="color: var(--accent-green);">{{ $rating->rating }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
        </div>
    @empty
        <div class="col-span-full">
            <p class="text-center text-gray-500 py-8">
                No rated teas yet. Be the first to rate some teas!
            </p>
        </div>
    @endforelse
</div>

@endsection
